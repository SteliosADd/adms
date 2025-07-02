<?php
include('include/connect.php');
session_start();

// Check if user is admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

$message = '';
$messageType = '';

// Handle stock updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_stock'])) {
        $productId = (int)$_POST['product_id'];
        $newStock = (int)$_POST['new_stock'];
        $reason = trim($_POST['reason'] ?? '');
        
        if ($productId > 0 && $newStock >= 0) {
            // Get current stock
            $stmt = $conn->prepare("SELECT stock, name FROM products WHERE id = ?");
            $stmt->bind_param("i", $productId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($product = $result->fetch_assoc()) {
                $oldStock = $product['stock'];
                $productName = $product['name'];
                
                // Update stock
                $stmt = $conn->prepare("UPDATE products SET stock = ?, updated_at = NOW() WHERE id = ?");
                $stmt->bind_param("ii", $newStock, $productId);
                
                if ($stmt->execute()) {
                    // Log the stock change
                    $changeAmount = $newStock - $oldStock;
                    $changeType = $changeAmount > 0 ? 'increase' : 'decrease';
                    
                    $stmt = $conn->prepare("INSERT INTO stock_logs (product_id, old_stock, new_stock, change_amount, change_type, reason, admin_id, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
                    $stmt->bind_param("iiiissi", $productId, $oldStock, $newStock, $changeAmount, $changeType, $reason, $_SESSION['admin_id']);
                    $stmt->execute();
                    
                    $message = "Stock updated successfully for {$productName}";
                    $messageType = 'success';
                } else {
                    $message = "Failed to update stock";
                    $messageType = 'error';
                }
            } else {
                $message = "Product not found";
                $messageType = 'error';
            }
        } else {
            $message = "Invalid stock value";
            $messageType = 'error';
        }
    }
    
    if (isset($_POST['bulk_update'])) {
        $updates = $_POST['bulk_updates'] ?? [];
        $successCount = 0;
        $errorCount = 0;
        
        foreach ($updates as $productId => $data) {
            $newStock = (int)($data['stock'] ?? 0);
            $reason = trim($data['reason'] ?? 'Bulk update');
            
            if ($newStock >= 0) {
                // Get current stock
                $stmt = $conn->prepare("SELECT stock FROM products WHERE id = ?");
                $stmt->bind_param("i", $productId);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($product = $result->fetch_assoc()) {
                    $oldStock = $product['stock'];
                    
                    // Update stock
                    $stmt = $conn->prepare("UPDATE products SET stock = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->bind_param("ii", $newStock, $productId);
                    
                    if ($stmt->execute()) {
                        // Log the change
                        $changeAmount = $newStock - $oldStock;
                        $changeType = $changeAmount > 0 ? 'increase' : 'decrease';
                        
                        $stmt = $conn->prepare("INSERT INTO stock_logs (product_id, old_stock, new_stock, change_amount, change_type, reason, admin_id, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
                        $stmt->bind_param("iiiissi", $productId, $oldStock, $newStock, $changeAmount, $changeType, $reason, $_SESSION['admin_id']);
                        $stmt->execute();
                        
                        $successCount++;
                    } else {
                        $errorCount++;
                    }
                }
            }
        }
        
        $message = "Bulk update completed: {$successCount} successful, {$errorCount} failed";
        $messageType = $errorCount > 0 ? 'warning' : 'success';
    }
}

// Get filter parameters
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$stockFilter = $_GET['stock_filter'] ?? '';
$sortBy = $_GET['sort_by'] ?? 'name';
$sortOrder = $_GET['sort_order'] ?? 'ASC';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

// Build query
$whereConditions = [];
$params = [];
$types = '';

if (!empty($search)) {
    $whereConditions[] = "(p.name LIKE ? OR p.brand LIKE ?)";
    $searchTerm = "%{$search}%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= 'ss';
}

if (!empty($category)) {
    $whereConditions[] = "p.type = ?";
    $params[] = $category;
    $types .= 's';
}

if ($stockFilter === 'low') {
    $whereConditions[] = "p.stock <= 10";
} elseif ($stockFilter === 'out') {
    $whereConditions[] = "p.stock = 0";
} elseif ($stockFilter === 'available') {
    $whereConditions[] = "p.stock > 0";
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Valid sort columns
$validSortColumns = ['name', 'brand', 'price', 'stock', 'created_at'];
$sortBy = in_array($sortBy, $validSortColumns) ? $sortBy : 'name';
$sortOrder = strtoupper($sortOrder) === 'DESC' ? 'DESC' : 'ASC';

// Get total count
$countQuery = "SELECT COUNT(*) as total FROM products p {$whereClause}";
$countStmt = $conn->prepare($countQuery);
if (!empty($params)) {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$totalProducts = $countStmt->get_result()->fetch_assoc()['total'];
$totalPages = ceil($totalProducts / $limit);

// Get products
$query = "SELECT p.*, 
                 (SELECT COUNT(*) FROM order_items oi WHERE oi.product_id = p.id) as total_sold,
                 (SELECT SUM(oi.quantity) FROM order_items oi WHERE oi.product_id = p.id) as units_sold
          FROM products p 
          {$whereClause} 
          ORDER BY p.{$sortBy} {$sortOrder} 
          LIMIT ? OFFSET ?";

$stmt = $conn->prepare($query);
$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$products = $stmt->get_result();

// Get categories for filter
$categoriesQuery = "SELECT DISTINCT type FROM products WHERE type IS NOT NULL ORDER BY type";
$categoriesResult = $conn->query($categoriesQuery);

// Get recent stock changes
$recentChangesQuery = "SELECT sl.*, p.name as product_name, a.username as admin_name 
                      FROM stock_logs sl 
                      JOIN products p ON sl.product_id = p.id 
                      JOIN admin a ON sl.admin_id = a.id 
                      ORDER BY sl.created_at DESC 
                      LIMIT 10";
$recentChanges = $conn->query($recentChangesQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management - SneakZone Admin</title>
    <link rel="stylesheet" href="css/admin_style.css">
    <style>
        .inventory-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .inventory-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .filters {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .filter-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            align-items: end;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
        }
        
        .filter-group label {
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }
        
        .filter-group input,
        .filter-group select {
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .filter-btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
        }
        
        .inventory-table {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .table-header {
            background: #f8f9fa;
            padding: 15px 20px;
            border-bottom: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .bulk-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
            position: sticky;
            top: 0;
        }
        
        .product-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .product-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 5px;
        }
        
        .product-details h4 {
            margin: 0 0 5px 0;
            font-size: 14px;
        }
        
        .product-details p {
            margin: 0;
            color: #666;
            font-size: 12px;
        }
        
        .stock-input {
            width: 80px;
            padding: 5px;
            border: 1px solid #ddd;
            border-radius: 3px;
            text-align: center;
        }
        
        .stock-status {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            text-align: center;
        }
        
        .stock-high {
            background: #d4edda;
            color: #155724;
        }
        
        .stock-medium {
            background: #fff3cd;
            color: #856404;
        }
        
        .stock-low {
            background: #f8d7da;
            color: #721c24;
        }
        
        .stock-out {
            background: #f5c6cb;
            color: #721c24;
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        
        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-weight: 600;
        }
        
        .btn-primary {
            background: #007bff;
            color: white;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-warning {
            background: #ffc107;
            color: #212529;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 20px;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .pagination a,
        .pagination span {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            text-decoration: none;
            color: #333;
        }
        
        .pagination .current {
            background: #007bff;
            color: white;
            border-color: #007bff;
        }
        
        .recent-changes {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-top: 20px;
        }
        
        .change-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        
        .change-details {
            flex: 1;
        }
        
        .change-amount {
            font-weight: 600;
        }
        
        .change-increase {
            color: #28a745;
        }
        
        .change-decrease {
            color: #dc3545;
        }
        
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        .alert-error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        
        .alert-warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            color: #007bff;
        }
        
        .stat-label {
            color: #666;
            margin-top: 5px;
        }
        
        @media (max-width: 768px) {
            .inventory-header {
                flex-direction: column;
                gap: 15px;
            }
            
            .filter-row {
                grid-template-columns: 1fr;
            }
            
            .table-responsive {
                font-size: 14px;
            }
            
            .product-info {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <?php include('include/admin_header.php'); ?>
    
    <div class="inventory-container">
        <div class="inventory-header">
            <div>
                <h1>Inventory Management</h1>
                <p>Manage product stock levels and track inventory changes</p>
            </div>
            <div>
                <button onclick="exportInventory()" class="btn-primary">Export CSV</button>
            </div>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <!-- Statistics -->
        <div class="stats-grid">
            <?php
            $statsQuery = "SELECT 
                COUNT(*) as total_products,
                SUM(CASE WHEN stock = 0 THEN 1 ELSE 0 END) as out_of_stock,
                SUM(CASE WHEN stock <= 10 AND stock > 0 THEN 1 ELSE 0 END) as low_stock,
                SUM(stock) as total_units
                FROM products";
            $statsResult = $conn->query($statsQuery);
            $stats = $statsResult->fetch_assoc();
            ?>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($stats['total_products']); ?></div>
                <div class="stat-label">Total Products</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($stats['total_units']); ?></div>
                <div class="stat-label">Total Units</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" style="color: #dc3545;"><?php echo number_format($stats['out_of_stock']); ?></div>
                <div class="stat-label">Out of Stock</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" style="color: #ffc107;"><?php echo number_format($stats['low_stock']); ?></div>
                <div class="stat-label">Low Stock</div>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="filters">
            <form method="GET">
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="search">Search Products</label>
                        <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Product name or brand...">
                    </div>
                    
                    <div class="filter-group">
                        <label for="category">Category</label>
                        <select id="category" name="category">
                            <option value="">All Categories</option>
                            <?php while ($cat = $categoriesResult->fetch_assoc()): ?>
                                <option value="<?php echo htmlspecialchars($cat['type']); ?>" <?php echo $category === $cat['type'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars(ucfirst($cat['type'])); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="stock_filter">Stock Status</label>
                        <select id="stock_filter" name="stock_filter">
                            <option value="">All Stock Levels</option>
                            <option value="available" <?php echo $stockFilter === 'available' ? 'selected' : ''; ?>>In Stock</option>
                            <option value="low" <?php echo $stockFilter === 'low' ? 'selected' : ''; ?>>Low Stock (≤10)</option>
                            <option value="out" <?php echo $stockFilter === 'out' ? 'selected' : ''; ?>>Out of Stock</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="sort_by">Sort By</label>
                        <select id="sort_by" name="sort_by">
                            <option value="name" <?php echo $sortBy === 'name' ? 'selected' : ''; ?>>Name</option>
                            <option value="brand" <?php echo $sortBy === 'brand' ? 'selected' : ''; ?>>Brand</option>
                            <option value="price" <?php echo $sortBy === 'price' ? 'selected' : ''; ?>>Price</option>
                            <option value="stock" <?php echo $sortBy === 'stock' ? 'selected' : ''; ?>>Stock</option>
                            <option value="created_at" <?php echo $sortBy === 'created_at' ? 'selected' : ''; ?>>Date Added</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="sort_order">Order</label>
                        <select id="sort_order" name="sort_order">
                            <option value="ASC" <?php echo $sortOrder === 'ASC' ? 'selected' : ''; ?>>Ascending</option>
                            <option value="DESC" <?php echo $sortOrder === 'DESC' ? 'selected' : ''; ?>>Descending</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <button type="submit" class="filter-btn">Apply Filters</button>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Inventory Table -->
        <div class="inventory-table">
            <div class="table-header">
                <h3>Products (<?php echo number_format($totalProducts); ?> total)</h3>
                <div class="bulk-actions">
                    <button onclick="selectAll()" class="btn-sm btn-primary">Select All</button>
                    <button onclick="bulkUpdate()" class="btn-sm btn-success">Bulk Update</button>
                </div>
            </div>
            
            <div class="table-responsive">
                <form id="bulkForm" method="POST">
                    <table>
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="selectAllCheckbox"></th>
                                <th>Product</th>
                                <th>Brand</th>
                                <th>Price</th>
                                <th>Current Stock</th>
                                <th>Status</th>
                                <th>Units Sold</th>
                                <th>New Stock</th>
                                <th>Reason</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($product = $products->fetch_assoc()): ?>
                                <?php
                                $stockStatus = '';
                                $stockClass = '';
                                if ($product['stock'] == 0) {
                                    $stockStatus = 'Out of Stock';
                                    $stockClass = 'stock-out';
                                } elseif ($product['stock'] <= 10) {
                                    $stockStatus = 'Low Stock';
                                    $stockClass = 'stock-low';
                                } elseif ($product['stock'] <= 50) {
                                    $stockStatus = 'Medium';
                                    $stockClass = 'stock-medium';
                                } else {
                                    $stockStatus = 'In Stock';
                                    $stockClass = 'stock-high';
                                }
                                ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" name="selected_products[]" value="<?php echo $product['id']; ?>" class="product-checkbox">
                                    </td>
                                    <td>
                                        <div class="product-info">
                                            <img src="uploaded_img/<?php echo htmlspecialchars($product['photo']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-image">
                                            <div class="product-details">
                                                <h4><?php echo htmlspecialchars($product['name']); ?></h4>
                                                <p><?php echo htmlspecialchars($product['type']); ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($product['brand']); ?></td>
                                    <td>$<?php echo number_format($product['price'], 2); ?></td>
                                    <td class="text-center"><?php echo number_format($product['stock']); ?></td>
                                    <td>
                                        <span class="stock-status <?php echo $stockClass; ?>">
                                            <?php echo $stockStatus; ?>
                                        </span>
                                    </td>
                                    <td class="text-center"><?php echo number_format($product['units_sold'] ?? 0); ?></td>
                                    <td>
                                        <input type="number" name="bulk_updates[<?php echo $product['id']; ?>][stock]" class="stock-input" min="0" placeholder="<?php echo $product['stock']; ?>">
                                    </td>
                                    <td>
                                        <input type="text" name="bulk_updates[<?php echo $product['id']; ?>][reason]" placeholder="Reason..." style="width: 120px; padding: 5px; border: 1px solid #ddd; border-radius: 3px;">
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button type="button" onclick="quickUpdate(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['name']); ?>', <?php echo $product['stock']; ?>)" class="btn-sm btn-primary">Quick Update</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <input type="hidden" name="bulk_update" value="1">
                </form>
            </div>
        </div>
        
        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">&laquo; Previous</a>
                <?php endif; ?>
                
                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                    <?php if ($i == $page): ?>
                        <span class="current"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">Next &raquo;</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <!-- Recent Stock Changes -->
        <div class="recent-changes">
            <h3>Recent Stock Changes</h3>
            <?php if ($recentChanges->num_rows > 0): ?>
                <?php while ($change = $recentChanges->fetch_assoc()): ?>
                    <div class="change-item">
                        <div class="change-details">
                            <strong><?php echo htmlspecialchars($change['product_name']); ?></strong><br>
                            <small>by <?php echo htmlspecialchars($change['admin_name']); ?> • <?php echo date('M j, Y g:i A', strtotime($change['created_at'])); ?></small>
                            <?php if ($change['reason']): ?>
                                <br><small>Reason: <?php echo htmlspecialchars($change['reason']); ?></small>
                            <?php endif; ?>
                        </div>
                        <div class="change-amount <?php echo $change['change_type'] === 'increase' ? 'change-increase' : 'change-decrease'; ?>">
                            <?php echo $change['change_type'] === 'increase' ? '+' : ''; ?><?php echo $change['change_amount']; ?>
                            <br><small><?php echo $change['old_stock']; ?> → <?php echo $change['new_stock']; ?></small>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No recent stock changes.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Quick Update Modal -->
    <div id="quickUpdateModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 30px; border-radius: 10px; width: 400px;">
            <h3>Quick Stock Update</h3>
            <form method="POST">
                <input type="hidden" id="quickProductId" name="product_id">
                <div style="margin-bottom: 15px;">
                    <label>Product:</label>
                    <div id="quickProductName" style="font-weight: bold; color: #333;"></div>
                </div>
                <div style="margin-bottom: 15px;">
                    <label>Current Stock:</label>
                    <div id="quickCurrentStock" style="font-weight: bold; color: #007bff;"></div>
                </div>
                <div style="margin-bottom: 15px;">
                    <label for="quickNewStock">New Stock:</label>
                    <input type="number" id="quickNewStock" name="new_stock" min="0" required style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 5px;">
                </div>
                <div style="margin-bottom: 20px;">
                    <label for="quickReason">Reason:</label>
                    <input type="text" id="quickReason" name="reason" placeholder="Optional reason for stock change" style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 5px;">
                </div>
                <div style="display: flex; gap: 10px;">
                    <button type="submit" name="update_stock" style="flex: 1; background: #28a745; color: white; border: none; padding: 12px; border-radius: 5px; cursor: pointer; font-weight: 600;">Update Stock</button>
                    <button type="button" onclick="closeQuickUpdate()" style="flex: 1; background: #6c757d; color: white; border: none; padding: 12px; border-radius: 5px; cursor: pointer; font-weight: 600;">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function selectAll() {
            const selectAllCheckbox = document.getElementById('selectAllCheckbox');
            const checkboxes = document.querySelectorAll('.product-checkbox');
            
            selectAllCheckbox.checked = !selectAllCheckbox.checked;
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAllCheckbox.checked;
            });
        }
        
        function bulkUpdate() {
            const selectedCheckboxes = document.querySelectorAll('.product-checkbox:checked');
            if (selectedCheckboxes.length === 0) {
                alert('Please select at least one product to update.');
                return;
            }
            
            if (confirm(`Update stock for ${selectedCheckboxes.length} selected products?`)) {
                document.getElementById('bulkForm').submit();
            }
        }
        
        function quickUpdate(productId, productName, currentStock) {
            document.getElementById('quickProductId').value = productId;
            document.getElementById('quickProductName').textContent = productName;
            document.getElementById('quickCurrentStock').textContent = currentStock + ' units';
            document.getElementById('quickNewStock').value = currentStock;
            document.getElementById('quickUpdateModal').style.display = 'block';
        }
        
        function closeQuickUpdate() {
            document.getElementById('quickUpdateModal').style.display = 'none';
        }
        
        function exportInventory() {
            const params = new URLSearchParams(window.location.search);
            params.set('export', 'csv');
            window.location.href = 'export_inventory.php?' + params.toString();
        }
        
        // Close modal when clicking outside
        document.getElementById('quickUpdateModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeQuickUpdate();
            }
        });
    </script>
    
    <?php include('include/admin_footer.php'); ?>
</body>
</html>
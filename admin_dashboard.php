<?php
include('include/connect.php');
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    // Redirect to login page with return URL
    header("Location: login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

$message = '';
$messageType = '';

// Handle user deletion
if (isset($_POST['delete_user']) && isset($_POST['user_id'])) {
    $userId = (int)$_POST['user_id'];
    
    // Don't allow admin to delete themselves
    if ($userId == $_SESSION['user_id']) {
        $message = "You cannot delete your own account!";
        $messageType = "error";
    } else {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        
        if ($stmt->execute()) {
            $message = "User deleted successfully!";
            $messageType = "success";
        } else {
            $message = "Failed to delete user: " . $conn->error;
            $messageType = "error";
        }
    }
}

// Handle product deletion
if (isset($_POST['delete_product']) && isset($_POST['product_id'])) {
    $productId = (int)$_POST['product_id'];
    
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $productId);
    
    if ($stmt->execute()) {
        $message = "Product deleted successfully!";
        $messageType = "success";
    } else {
        $message = "Failed to delete product: " . $conn->error;
        $messageType = "error";
    }
}

// Fetch users
$stmt = $conn->prepare("SELECT id, username, email, role, fullname, created_at FROM users ORDER BY created_at DESC");
$stmt->execute();
$result = $stmt->get_result();
$users = [];

while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

// Fetch products
$stmt = $conn->prepare("SELECT p.*, u.username as seller_name 
                      FROM products p 
                      JOIN users u ON p.seller_id = u.id 
                      ORDER BY p.created_at DESC");
$stmt->execute();
$result = $stmt->get_result();
$products = [];

while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}

// Fetch recent orders
$stmt = $conn->prepare("SELECT o.*, u.username 
                      FROM orders o 
                      JOIN users u ON o.user_id = u.id 
                      ORDER BY o.order_date DESC LIMIT 10");
$stmt->execute();
$result = $stmt->get_result();
$orders = [];

while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
}

// Count statistics
$userCount = count($users);
$productCount = count($products);

$stmt = $conn->prepare("SELECT COUNT(*) as order_count FROM orders");
$stmt->execute();
$result = $stmt->get_result();
$orderCount = $result->fetch_assoc()['order_count'];

$stmt = $conn->prepare("SELECT SUM(total_amount) as total_sales FROM orders");
$stmt->execute();
$result = $stmt->get_result();
$totalSales = $result->fetch_assoc()['total_sales'] ?: 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Online Store</title>
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #858796;
            --success-color: #1cc88a;
            --info-color: #36b9cc;
            --warning-color: #f6c23e;
            --danger-color: #e74a3b;
            --light-color: #f8f9fc;
            --dark-color: #5a5c69;
        }
        
        body {
            font-family: 'Nunito', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f8f9fc;
            margin: 0;
            padding: 0;
        }
        
        .container {
            max-width: 1320px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .page-title {
            margin-bottom: 30px;
            color: var(--dark-color);
        }
        
        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background-color: white;
            border-radius: 5px;
            padding: 20px;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            border-left: 4px solid;
        }
        
        .stat-card.primary { border-left-color: var(--primary-color); }
        .stat-card.success { border-left-color: var(--success-color); }
        .stat-card.info { border-left-color: var(--info-color); }
        .stat-card.warning { border-left-color: var(--warning-color); }
        
        .stat-card .stat-title {
            text-transform: uppercase;
            font-size: 0.7rem;
            font-weight: bold;
            color: var(--secondary-color);
            margin-bottom: 5px;
        }
        
        .stat-card .stat-value {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--dark-color);
            margin-bottom: 0;
        }
        
        .tabs {
            display: flex;
            border-bottom: 1px solid #ddd;
            margin-bottom: 20px;
        }
        
        .tab-button {
            padding: 10px 20px;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            color: var(--secondary-color);
        }
        
        .tab-button.active {
            color: var(--primary-color);
            border-bottom: 3px solid var(--primary-color);
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            border-radius: 5px;
            overflow: hidden;
        }
        
        .data-table th {
            background-color: #f8f9fc;
            padding: 12px 15px;
            text-align: left;
            font-weight: bold;
            color: var(--dark-color);
            border-bottom: 1px solid #e3e6f0;
        }
        
        .data-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #e3e6f0;
            color: var(--secondary-color);
        }
        
        .data-table tr:hover {
            background-color: #f8f9fc;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-active {
            background-color: #e8f5e9;
            color: #2e7d32;
        }
        
        .status-inactive {
            background-color: #ffebee;
            color: #c62828;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-processing {
            background-color: #cce5ff;
            color: #004085;
        }
        
        .status-shipped {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-delivered {
            background-color: #d1e7dd;
            color: #0f5132;
        }
        
        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .action-button {
            display: inline-block;
            padding: 6px 12px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 0.9em;
        }
        
        .action-button.danger {
            background-color: var(--danger-color);
        }
        
        .action-button:hover {
            opacity: 0.9;
        }
        
        #message {
            padding: 10px;
            margin: 20px 0;
            border-radius: 4px;
            text-align: center;
        }
        
        .success {
            background-color: #d4edda;
            color: #155724;
        }
        
        .error {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .product-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
        }
        
        .truncate {
            max-width: 200px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
    </style>
</head>
<body>
    <?php include 'include/header.php'; ?>

    <div class="container">
        <h1 class="page-title">Admin Dashboard</h1>
        
        <?php if(!empty($message)): ?>
            <div id="message" class="<?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>
            <script>
                // Auto-hide message after 5 seconds
                setTimeout(function() {
                    document.getElementById('message').style.display = 'none';
                }, 5000);
            </script>
        <?php endif; ?>
        
        <div class="dashboard-stats">
            <div class="stat-card primary">
                <div class="stat-title">Total Users</div>
                <div class="stat-value"><?php echo $userCount; ?></div>
            </div>
            <div class="stat-card success">
                <div class="stat-title">Total Products</div>
                <div class="stat-value"><?php echo $productCount; ?></div>
            </div>
            <div class="stat-card info">
                <div class="stat-title">Total Orders</div>
                <div class="stat-value"><?php echo $orderCount; ?></div>
            </div>
            <div class="stat-card warning">
                <div class="stat-title">Total Sales</div>
                <div class="stat-value">$<?php echo number_format($totalSales, 2); ?></div>
            </div>
        </div>
        
        <div class="tabs">
            <button class="tab-button active" onclick="openTab(event, 'users')">Users</button>
            <button class="tab-button" onclick="openTab(event, 'products')">Products</button>
            <button class="tab-button" onclick="openTab(event, 'orders')">Recent Orders</button>
        </div>
        
        <div id="users" class="tab-content active">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Full Name</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($users as $user): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo ucfirst(htmlspecialchars($user['role'])); ?></td>
                            <td><?php echo htmlspecialchars($user['fullname'] ?? 'N/A'); ?></td>
                            <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                            <td>
                                <?php if($user['id'] != $_SESSION['user_id']): ?>
                                <form method="post" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <button type="submit" name="delete_user" class="action-button danger">
                                        Delete
                                    </button>
                                </form>
                                <?php else: ?>
                                <span class="status-badge status-active">Current User</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div id="products" class="tab-content">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Price</th>
                        <th>Type</th>
                        <th>Seller</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($products as $product): ?>
                        <tr>
                            <td><?php echo $product['id']; ?></td>
                            <td>
                                <?php $imagePath = !empty($product['photo']) ? $product['photo'] : 'img/air.png'; ?>
                                <img src="<?php echo htmlspecialchars($imagePath); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-image">
                            </td>
                            <td class="truncate" title="<?php echo htmlspecialchars($product['name']); ?>">
                                <?php echo htmlspecialchars($product['name']); ?>
                            </td>
                            <td>$<?php echo number_format($product['price'], 2); ?></td>
                            <td><?php echo ucfirst(htmlspecialchars($product['type'])); ?></td>
                            <td><?php echo htmlspecialchars($product['seller_name']); ?></td>
                            <td>
                                <form method="post" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this product?');">
                                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                    <button type="submit" name="delete_product" class="action-button danger">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div id="orders" class="tab-content">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($orders as $order): ?>
                        <tr>
                            <td>#<?php echo $order['id']; ?></td>
                            <td><?php echo htmlspecialchars($order['username']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                            <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </td>
                            <td>
                                <a href="admin_view_order.php?order_id=<?php echo $order['id']; ?>" class="action-button">View Details</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php include 'include/footer.php'; ?>
    
    <script>
        function openTab(evt, tabName) {
            var i, tabContent, tabButtons;
            
            // Hide all tab content
            tabContent = document.getElementsByClassName("tab-content");
            for (i = 0; i < tabContent.length; i++) {
                tabContent[i].style.display = "none";
            }
            
            // Remove active class from all tab buttons
            tabButtons = document.getElementsByClassName("tab-button");
            for (i = 0; i < tabButtons.length; i++) {
                tabButtons[i].className = tabButtons[i].className.replace(" active", "");
            }
            
            // Show the current tab and add active class to the button
            document.getElementById(tabName).style.display = "block";
            evt.currentTarget.className += " active";
        }
    </script>
</body>
</html>
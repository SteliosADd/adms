<?php
session_start();
include 'include/connect.php';

// Get search parameters
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
$brand_filter = isset($_GET['brand']) ? $_GET['brand'] : '';
$min_price = isset($_GET['min_price']) ? (float)$_GET['min_price'] : 0;
$max_price = isset($_GET['max_price']) ? (float)$_GET['max_price'] : 10000;
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'name';
$in_stock_only = isset($_GET['in_stock']) ? true : false;
$featured_only = isset($_GET['featured']) ? true : false;

// Build the WHERE clause
$where_conditions = [];
$params = [];
$param_types = '';

if (!empty($search_query)) {
    $where_conditions[] = "(p.name LIKE ? OR p.description LIKE ? OR p.brand LIKE ?)";
    $search_param = "%$search_query%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $param_types .= 'sss';
}

if (!empty($category_filter)) {
    $where_conditions[] = "p.type = ?";
    $params[] = $category_filter;
    $param_types .= 's';
}

if (!empty($brand_filter)) {
    $where_conditions[] = "p.brand = ?";
    $params[] = $brand_filter;
    $param_types .= 's';
}

if ($min_price > 0) {
    $where_conditions[] = "p.price >= ?";
    $params[] = $min_price;
    $param_types .= 'd';
}

if ($max_price < 10000) {
    $where_conditions[] = "p.price <= ?";
    $params[] = $max_price;
    $param_types .= 'd';
}

if ($in_stock_only) {
    $where_conditions[] = "p.stock_quantity > 0";
}

if ($featured_only) {
    $where_conditions[] = "p.featured = 1";
}

// Build ORDER BY clause
$order_by = "ORDER BY ";
switch ($sort_by) {
    case 'price_low':
        $order_by .= "p.price ASC";
        break;
    case 'price_high':
        $order_by .= "p.price DESC";
        break;
    case 'newest':
        $order_by .= "p.id DESC";
        break;
    case 'popular':
        $order_by .= "p.views DESC";
        break;
    case 'rating':
        $order_by .= "avg_rating DESC";
        break;
    default:
        $order_by .= "p.name ASC";
}

// Build the main query
$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

$query = "SELECT p.*, 
          COALESCE(AVG(pr.rating), 0) as avg_rating,
          COUNT(pr.id) as review_count
          FROM products p 
          LEFT JOIN product_reviews pr ON p.id = pr.product_id AND pr.status = 'approved'
          $where_clause
          GROUP BY p.id
          $order_by";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Get available categories
$categories_query = "SELECT DISTINCT type FROM products WHERE type IS NOT NULL AND type != '' ORDER BY type";
$categories_result = $conn->query($categories_query);

// Get available brands
$brands_query = "SELECT DISTINCT brand FROM products WHERE brand IS NOT NULL AND brand != '' ORDER BY brand";
$brands_result = $conn->query($brands_query);

// Get price range
$price_range_query = "SELECT MIN(price) as min_price, MAX(price) as max_price FROM products";
$price_range_result = $conn->query($price_range_query);
$price_range = $price_range_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advanced Search - SneakZone</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .search-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .search-filters {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .filter-row {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
            flex-wrap: wrap;
            align-items: center;
        }
        .filter-group {
            display: flex;
            flex-direction: column;
            min-width: 150px;
        }
        .filter-group label {
            font-weight: bold;
            margin-bottom: 5px;
            color: #333;
        }
        .filter-group input,
        .filter-group select {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        .price-range {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .price-range input {
            width: 80px;
        }
        .checkbox-group {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        .checkbox-group label {
            display: flex;
            align-items: center;
            gap: 5px;
            font-weight: normal;
        }
        .search-actions {
            display: flex;
            gap: 10px;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
        }
        .btn-primary {
            background: #007bff;
            color: white;
        }
        .btn-primary:hover {
            background: #0056b3;
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .btn-secondary:hover {
            background: #545b62;
        }
        .search-results {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .results-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }
        .product-card {
            border: 1px solid #ddd;
            border-radius: 10px;
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
            background: white;
        }
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .product-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        .product-info {
            padding: 15px;
        }
        .product-name {
            font-weight: bold;
            margin-bottom: 5px;
            color: #333;
        }
        .product-brand {
            color: #666;
            font-size: 14px;
            margin-bottom: 5px;
        }
        .product-price {
            font-size: 18px;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 10px;
        }
        .product-rating {
            display: flex;
            align-items: center;
            gap: 5px;
            margin-bottom: 10px;
        }
        .stars {
            display: flex;
            gap: 1px;
        }
        .star {
            color: #ddd;
            font-size: 14px;
        }
        .star.filled {
            color: #ffc107;
        }
        .product-stock {
            font-size: 12px;
            margin-bottom: 10px;
        }
        .in-stock {
            color: #28a745;
        }
        .low-stock {
            color: #ffc107;
        }
        .out-of-stock {
            color: #dc3545;
        }
        .product-actions {
            display: flex;
            gap: 10px;
        }
        .btn-small {
            padding: 5px 10px;
            font-size: 12px;
            flex: 1;
            text-align: center;
        }
        .no-results {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        @media (max-width: 768px) {
            .filter-row {
                flex-direction: column;
                align-items: stretch;
            }
            .filter-group {
                min-width: auto;
            }
            .price-range {
                flex-direction: column;
                align-items: stretch;
            }
            .checkbox-group {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <?php include 'include/header.php'; ?>
    
    <div class="search-container">
        <div class="search-filters">
            <h2>Advanced Search</h2>
            <form method="GET" action="">
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="search">Search Products:</label>
                        <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search_query); ?>" placeholder="Product name, description, brand...">
                    </div>
                    
                    <div class="filter-group">
                        <label for="category">Category:</label>
                        <select id="category" name="category">
                            <option value="">All Categories</option>
                            <?php while ($category = $categories_result->fetch_assoc()): ?>
                                <option value="<?php echo htmlspecialchars($category['type']); ?>" 
                                        <?php echo $category_filter == $category['type'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars(ucfirst($category['type'])); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="brand">Brand:</label>
                        <select id="brand" name="brand">
                            <option value="">All Brands</option>
                            <?php while ($brand = $brands_result->fetch_assoc()): ?>
                                <option value="<?php echo htmlspecialchars($brand['brand']); ?>" 
                                        <?php echo $brand_filter == $brand['brand'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($brand['brand']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                
                <div class="filter-row">
                    <div class="filter-group">
                        <label>Price Range:</label>
                        <div class="price-range">
                            <input type="number" name="min_price" value="<?php echo $min_price; ?>" min="0" step="0.01" placeholder="Min">
                            <span>to</span>
                            <input type="number" name="max_price" value="<?php echo $max_price; ?>" min="0" step="0.01" placeholder="Max">
                        </div>
                    </div>
                    
                    <div class="filter-group">
                        <label for="sort">Sort By:</label>
                        <select id="sort" name="sort">
                            <option value="name" <?php echo $sort_by == 'name' ? 'selected' : ''; ?>>Name (A-Z)</option>
                            <option value="price_low" <?php echo $sort_by == 'price_low' ? 'selected' : ''; ?>>Price (Low to High)</option>
                            <option value="price_high" <?php echo $sort_by == 'price_high' ? 'selected' : ''; ?>>Price (High to Low)</option>
                            <option value="newest" <?php echo $sort_by == 'newest' ? 'selected' : ''; ?>>Newest First</option>
                            <option value="popular" <?php echo $sort_by == 'popular' ? 'selected' : ''; ?>>Most Popular</option>
                            <option value="rating" <?php echo $sort_by == 'rating' ? 'selected' : ''; ?>>Highest Rated</option>
                        </select>
                    </div>
                </div>
                
                <div class="filter-row">
                    <div class="checkbox-group">
                        <label>
                            <input type="checkbox" name="in_stock" <?php echo $in_stock_only ? 'checked' : ''; ?>>
                            In Stock Only
                        </label>
                        <label>
                            <input type="checkbox" name="featured" <?php echo $featured_only ? 'checked' : ''; ?>>
                            Featured Products
                        </label>
                    </div>
                </div>
                
                <div class="search-actions">
                    <button type="submit" class="btn btn-primary">Search</button>
                    <a href="advanced_search.php" class="btn btn-secondary">Clear Filters</a>
                </div>
            </form>
        </div>
        
        <div class="search-results">
            <?php
            $products = [];
            while ($row = $result->fetch_assoc()) {
                $products[] = $row;
            }
            $total_results = count($products);
            ?>
            
            <div class="results-header">
                <h3>Search Results (<?php echo $total_results; ?> products found)</h3>
                <?php if (!empty($search_query) || !empty($category_filter) || !empty($brand_filter) || $min_price > 0 || $max_price < 10000 || $in_stock_only || $featured_only): ?>
                    <div class="active-filters">
                        <strong>Active Filters:</strong>
                        <?php if (!empty($search_query)): ?>
                            <span class="filter-tag">Search: "<?php echo htmlspecialchars($search_query); ?>"</span>
                        <?php endif; ?>
                        <?php if (!empty($category_filter)): ?>
                            <span class="filter-tag">Category: <?php echo htmlspecialchars(ucfirst($category_filter)); ?></span>
                        <?php endif; ?>
                        <?php if (!empty($brand_filter)): ?>
                            <span class="filter-tag">Brand: <?php echo htmlspecialchars($brand_filter); ?></span>
                        <?php endif; ?>
                        <?php if ($min_price > 0 || $max_price < 10000): ?>
                            <span class="filter-tag">Price: $<?php echo $min_price; ?> - $<?php echo $max_price; ?></span>
                        <?php endif; ?>
                        <?php if ($in_stock_only): ?>
                            <span class="filter-tag">In Stock Only</span>
                        <?php endif; ?>
                        <?php if ($featured_only): ?>
                            <span class="filter-tag">Featured Only</span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if ($total_results > 0): ?>
                <div class="products-grid">
                    <?php foreach ($products as $product): ?>
                        <div class="product-card">
                            <img src="<?php echo htmlspecialchars($product['photo']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-image">
                            <div class="product-info">
                                <div class="product-name"><?php echo htmlspecialchars($product['name']); ?></div>
                                <?php if ($product['brand']): ?>
                                    <div class="product-brand"><?php echo htmlspecialchars($product['brand']); ?></div>
                                <?php endif; ?>
                                <div class="product-price">$<?php echo number_format($product['price'], 2); ?></div>
                                
                                <?php if ($product['review_count'] > 0): ?>
                                    <div class="product-rating">
                                        <div class="stars">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <span class="star <?php echo $i <= round($product['avg_rating']) ? 'filled' : ''; ?>">★</span>
                                            <?php endfor; ?>
                                        </div>
                                        <span>(<?php echo $product['review_count']; ?>)</span>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="product-stock">
                                    <?php if ($product['stock_quantity'] > $product['low_stock_threshold']): ?>
                                        <span class="in-stock">✓ In Stock (<?php echo $product['stock_quantity']; ?>)</span>
                                    <?php elseif ($product['stock_quantity'] > 0): ?>
                                        <span class="low-stock">⚠ Low Stock (<?php echo $product['stock_quantity']; ?>)</span>
                                    <?php else: ?>
                                        <span class="out-of-stock">✗ Out of Stock</span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="product-actions">
                                    <a href="product_reviews.php?product_id=<?php echo $product['id']; ?>" class="btn btn-primary btn-small">View Details</a>
                                    <?php if ($product['stock_quantity'] > 0): ?>
                                        <a href="add_to_cart.php?product_id=<?php echo $product['id']; ?>" class="btn btn-secondary btn-small">Add to Cart</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-results">
                    <h3>No products found</h3>
                    <p>Try adjusting your search criteria or browse our <a href="product_list.php">full product catalog</a>.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php include 'include/footer.php'; ?>
</body>
</html>
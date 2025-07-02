<?php
include('include/connect.php');
session_start();

// Check if user is admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Get dashboard statistics
$today = date('Y-m-d');
$thisMonth = date('Y-m');
$lastMonth = date('Y-m', strtotime('-1 month'));

// Sales statistics
$salesQuery = "SELECT 
    COUNT(*) as total_orders,
    SUM(total_price) as total_revenue,
    AVG(total_price) as avg_order_value,
    SUM(CASE WHEN DATE(order_date) = ? THEN 1 ELSE 0 END) as today_orders,
    SUM(CASE WHEN DATE(order_date) = ? THEN total_price ELSE 0 END) as today_revenue,
    SUM(CASE WHEN DATE_FORMAT(order_date, '%Y-%m') = ? THEN 1 ELSE 0 END) as month_orders,
    SUM(CASE WHEN DATE_FORMAT(order_date, '%Y-%m') = ? THEN total_price ELSE 0 END) as month_revenue
    FROM orders";

$stmt = $conn->prepare($salesQuery);
$stmt->bind_param("ssss", $today, $today, $thisMonth, $thisMonth);
$stmt->execute();
$salesStats = $stmt->get_result()->fetch_assoc();

// Product statistics
$productQuery = "SELECT 
    COUNT(*) as total_products,
    SUM(CASE WHEN stock <= 10 THEN 1 ELSE 0 END) as low_stock_products,
    SUM(CASE WHEN stock = 0 THEN 1 ELSE 0 END) as out_of_stock_products,
    AVG(price) as avg_product_price
    FROM products";

$productStats = $conn->query($productQuery)->fetch_assoc();

// Customer statistics
$customerQuery = "SELECT 
    COUNT(*) as total_customers,
    SUM(CASE WHEN DATE(created_at) = ? THEN 1 ELSE 0 END) as new_customers_today,
    SUM(CASE WHEN DATE_FORMAT(created_at, '%Y-%m') = ? THEN 1 ELSE 0 END) as new_customers_month
    FROM users";

$stmt = $conn->prepare($customerQuery);
$stmt->bind_param("ss", $today, $thisMonth);
$stmt->execute();
$customerStats = $stmt->get_result()->fetch_assoc();

// Review statistics
$reviewQuery = "SELECT 
    COUNT(*) as total_reviews,
    AVG(rating) as avg_rating,
    SUM(CASE WHEN DATE(created_at) = ? THEN 1 ELSE 0 END) as new_reviews_today
    FROM product_reviews";

$stmt = $conn->prepare($reviewQuery);
$stmt->bind_param("s", $today);
$stmt->execute();
$reviewStats = $stmt->get_result()->fetch_assoc();

// Recent orders
$recentOrdersQuery = "SELECT o.*, u.name as customer_name 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    ORDER BY o.order_date DESC 
    LIMIT 10";

$recentOrders = $conn->query($recentOrdersQuery)->fetch_all(MYSQLI_ASSOC);

// Top selling products
$topProductsQuery = "SELECT p.name, p.brand, p.photo, SUM(oi.quantity) as total_sold, SUM(oi.quantity * oi.price) as revenue
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    JOIN orders o ON oi.order_id = o.id
    WHERE DATE_FORMAT(o.order_date, '%Y-%m') = ?
    GROUP BY oi.product_id
    ORDER BY total_sold DESC
    LIMIT 5";

$stmt = $conn->prepare($topProductsQuery);
$stmt->bind_param("s", $thisMonth);
$stmt->execute();
$topProducts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Low stock alerts
$lowStockQuery = "SELECT name, brand, stock, photo FROM products WHERE stock <= 10 ORDER BY stock ASC LIMIT 5";
$lowStockProducts = $conn->query($lowStockQuery)->fetch_all(MYSQLI_ASSOC);

// Recent reviews
$recentReviewsQuery = "SELECT pr.*, p.name as product_name, u.name as customer_name
    FROM product_reviews pr
    JOIN products p ON pr.product_id = p.id
    JOIN users u ON pr.user_id = u.id
    ORDER BY pr.created_at DESC
    LIMIT 5";

$recentReviews = $conn->query($recentReviewsQuery)->fetch_all(MYSQLI_ASSOC);

// Sales chart data (last 7 days)
$chartQuery = "SELECT 
    DATE(order_date) as date,
    COUNT(*) as orders,
    SUM(total_price) as revenue
    FROM orders 
    WHERE DATE(order_date) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(order_date)
    ORDER BY date";

$chartData = $conn->query($chartQuery)->fetch_all(MYSQLI_ASSOC);

// Order status distribution
$statusQuery = "SELECT status, COUNT(*) as count FROM orders GROUP BY status";
$statusData = $conn->query($statusQuery)->fetch_all(MYSQLI_ASSOC);

// Calculate growth rates
$lastMonthSalesQuery = "SELECT 
    COUNT(*) as orders,
    SUM(total_price) as revenue
    FROM orders 
    WHERE DATE_FORMAT(order_date, '%Y-%m') = ?";

$stmt = $conn->prepare($lastMonthSalesQuery);
$stmt->bind_param("s", $lastMonth);
$stmt->execute();
$lastMonthStats = $stmt->get_result()->fetch_assoc();

$revenueGrowth = $lastMonthStats['revenue'] > 0 ? 
    (($salesStats['month_revenue'] - $lastMonthStats['revenue']) / $lastMonthStats['revenue']) * 100 : 0;

$orderGrowth = $lastMonthStats['orders'] > 0 ? 
    (($salesStats['month_orders'] - $lastMonthStats['orders']) / $lastMonthStats['orders']) * 100 : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enhanced Admin Dashboard - SneakZone</title>
    <link rel="stylesheet" href="css/admin_style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
            color: #333;
        }
        
        .admin-container {
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: 280px;
            background: linear-gradient(135deg, #2c3e50, #34495e);
            color: white;
            padding: 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            box-shadow: 4px 0 15px rgba(0,0,0,0.1);
        }
        
        .sidebar-header {
            padding: 30px 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-header h2 {
            font-size: 1.5em;
            margin-bottom: 5px;
        }
        
        .sidebar-header p {
            opacity: 0.8;
            font-size: 14px;
        }
        
        .sidebar-nav {
            padding: 20px 0;
        }
        
        .nav-section {
            margin-bottom: 30px;
        }
        
        .nav-section-title {
            padding: 0 20px 10px;
            font-size: 12px;
            text-transform: uppercase;
            opacity: 0.6;
            font-weight: 600;
        }
        
        .nav-item {
            display: block;
            padding: 15px 20px;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }
        
        .nav-item:hover {
            background: rgba(255,255,255,0.1);
            border-left-color: #3498db;
            padding-left: 25px;
        }
        
        .nav-item.active {
            background: rgba(52, 152, 219, 0.2);
            border-left-color: #3498db;
        }
        
        .nav-item i {
            width: 20px;
            margin-right: 10px;
        }
        
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 30px;
        }
        
        .dashboard-header {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .dashboard-title {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .dashboard-title h1 {
            font-size: 2.5em;
            color: #2c3e50;
            margin: 0;
        }
        
        .dashboard-actions {
            display: flex;
            gap: 15px;
        }
        
        .action-btn {
            background: #3498db;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        
        .action-btn:hover {
            background: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
        }
        
        .action-btn.success {
            background: #27ae60;
        }
        
        .action-btn.success:hover {
            background: #229954;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.15);
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #3498db, #2ecc71);
        }
        
        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .stat-title {
            color: #7f8c8d;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }
        
        .stat-value {
            font-size: 2.5em;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .stat-growth {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }
        
        .growth-positive {
            color: #27ae60;
        }
        
        .growth-negative {
            color: #e74c3c;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .chart-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .chart-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 25px;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .data-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 25px;
        }
        
        .data-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .data-header {
            background: #f8f9fa;
            padding: 20px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .data-title {
            font-weight: 600;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .data-content {
            padding: 20px;
            max-height: 400px;
            overflow-y: auto;
        }
        
        .data-item {
            display: flex;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #f1f2f6;
        }
        
        .data-item:last-child {
            border-bottom: none;
        }
        
        .item-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 10px;
            margin-right: 15px;
        }
        
        .item-details {
            flex: 1;
        }
        
        .item-name {
            font-weight: 600;
            margin-bottom: 5px;
            color: #2c3e50;
        }
        
        .item-meta {
            color: #7f8c8d;
            font-size: 14px;
        }
        
        .item-value {
            font-weight: 600;
            color: #3498db;
        }
        
        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #f1f2f6;
        }
        
        .order-info {
            flex: 1;
        }
        
        .order-number {
            font-weight: 600;
            color: #3498db;
        }
        
        .order-customer {
            color: #7f8c8d;
            font-size: 14px;
        }
        
        .order-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-processing {
            background: #cce5ff;
            color: #004085;
        }
        
        .status-shipped {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .status-delivered {
            background: #d4edda;
            color: #155724;
        }
        
        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }
        
        .alert-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            overflow: hidden;
            border-left: 5px solid #f39c12;
        }
        
        .alert-header {
            background: #fef9e7;
            padding: 20px;
            border-bottom: 1px solid #f7dc6f;
        }
        
        .alert-title {
            color: #b7950b;
            font-weight: 600;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .rating-stars {
            color: #f39c12;
        }
        
        .quick-actions {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .quick-actions h3 {
            margin-bottom: 20px;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .quick-action-btn {
            background: #ecf0f1;
            border: 2px solid #bdc3c7;
            color: #2c3e50;
            padding: 20px;
            border-radius: 12px;
            text-decoration: none;
            text-align: center;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }
        
        .quick-action-btn:hover {
            background: #3498db;
            border-color: #3498db;
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(52, 152, 219, 0.3);
        }
        
        .action-icon {
            font-size: 24px;
        }
        
        .action-text {
            font-weight: 600;
        }
        
        @media (max-width: 1024px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            
            .sidebar.open {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .data-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .dashboard-header {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .actions-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2>🛡️ SneakZone Admin</h2>
                <p>Management Dashboard</p>
            </div>
            
            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-section-title">Dashboard</div>
                    <a href="enhanced_admin_dashboard.php" class="nav-item active">
                        <i>📊</i> Overview
                    </a>
                    <a href="admin_analytics.php" class="nav-item">
                        <i>📈</i> Analytics
                    </a>
                </div>
                
                <div class="nav-section">
                    <div class="nav-section-title">Products</div>
                    <a href="product_list.php" class="nav-item">
                        <i>👟</i> All Products
                    </a>
                    <a href="add_product.php" class="nav-item">
                        <i>➕</i> Add Product
                    </a>
                    <a href="inventory_management.php" class="nav-item">
                        <i>📦</i> Inventory
                    </a>
                </div>
                
                <div class="nav-section">
                    <div class="nav-section-title">Orders</div>
                    <a href="admin_view_order.php" class="nav-item">
                        <i>🛒</i> All Orders
                    </a>
                    <a href="seller_orders.php" class="nav-item">
                        <i>📋</i> Order Management
                    </a>
                </div>
                
                <div class="nav-section">
                    <div class="nav-section-title">Customers</div>
                    <a href="customer.php" class="nav-item">
                        <i>👥</i> All Customers
                    </a>
                    <a href="product_reviews.php" class="nav-item">
                        <i>⭐</i> Reviews
                    </a>
                </div>
                
                <div class="nav-section">
                    <div class="nav-section-title">Marketing</div>
                    <a href="coupon_management.php" class="nav-item">
                        <i>🎫</i> Coupons
                    </a>
                    <a href="advanced_search.php" class="nav-item">
                        <i>🔍</i> Search Tools
                    </a>
                </div>
                
                <div class="nav-section">
                    <div class="nav-section-title">System</div>
                    <a href="notification_system.php" class="nav-item">
                        <i>🔔</i> Notifications
                    </a>
                    <a href="logout.php" class="nav-item">
                        <i>🚪</i> Logout
                    </a>
                </div>
            </nav>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Dashboard Header -->
            <div class="dashboard-header">
                <div class="dashboard-title">
                    <h1>📊 Dashboard</h1>
                    <span style="color: #7f8c8d; font-size: 16px;">Welcome back, Admin!</span>
                </div>
                <div class="dashboard-actions">
                    <a href="add_product.php" class="action-btn success">
                        ➕ Add Product
                    </a>
                    <a href="admin_analytics.php" class="action-btn">
                        📈 View Analytics
                    </a>
                </div>
            </div>
            
            <!-- Key Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-title">Total Revenue</div>
                        <div class="stat-icon" style="background: #e8f4fd; color: #3498db;">💰</div>
                    </div>
                    <div class="stat-value">$<?php echo number_format($salesStats['total_revenue'], 2); ?></div>
                    <div class="stat-growth <?php echo $revenueGrowth >= 0 ? 'growth-positive' : 'growth-negative'; ?>">
                        <?php echo $revenueGrowth >= 0 ? '📈' : '📉'; ?>
                        <?php echo abs(round($revenueGrowth, 1)); ?>% vs last month
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-title">Total Orders</div>
                        <div class="stat-icon" style="background: #e8f8f5; color: #27ae60;">📦</div>
                    </div>
                    <div class="stat-value"><?php echo number_format($salesStats['total_orders']); ?></div>
                    <div class="stat-growth <?php echo $orderGrowth >= 0 ? 'growth-positive' : 'growth-negative'; ?>">
                        <?php echo $orderGrowth >= 0 ? '📈' : '📉'; ?>
                        <?php echo abs(round($orderGrowth, 1)); ?>% vs last month
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-title">Total Customers</div>
                        <div class="stat-icon" style="background: #fef5e7; color: #f39c12;">👥</div>
                    </div>
                    <div class="stat-value"><?php echo number_format($customerStats['total_customers']); ?></div>
                    <div class="stat-growth">
                        ➕ <?php echo $customerStats['new_customers_today']; ?> new today
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-title">Products</div>
                        <div class="stat-icon" style="background: #f4e6ff; color: #9b59b6;">👟</div>
                    </div>
                    <div class="stat-value"><?php echo number_format($productStats['total_products']); ?></div>
                    <div class="stat-growth">
                        ⚠️ <?php echo $productStats['low_stock_products']; ?> low stock
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-title">Today's Sales</div>
                        <div class="stat-icon" style="background: #e8f4fd; color: #3498db;">📅</div>
                    </div>
                    <div class="stat-value">$<?php echo number_format($salesStats['today_revenue'], 2); ?></div>
                    <div class="stat-growth">
                        📦 <?php echo $salesStats['today_orders']; ?> orders today
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-title">Average Rating</div>
                        <div class="stat-icon" style="background: #fff3e0; color: #ff9800;">⭐</div>
                    </div>
                    <div class="stat-value"><?php echo number_format($reviewStats['avg_rating'], 1); ?>/5</div>
                    <div class="stat-growth">
                        📝 <?php echo number_format($reviewStats['total_reviews']); ?> total reviews
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="quick-actions">
                <h3>⚡ Quick Actions</h3>
                <div class="actions-grid">
                    <a href="add_product.php" class="quick-action-btn">
                        <div class="action-icon">➕</div>
                        <div class="action-text">Add Product</div>
                    </a>
                    <a href="coupon_management.php" class="quick-action-btn">
                        <div class="action-icon">🎫</div>
                        <div class="action-text">Create Coupon</div>
                    </a>
                    <a href="inventory_management.php" class="quick-action-btn">
                        <div class="action-icon">📦</div>
                        <div class="action-text">Manage Inventory</div>
                    </a>
                    <a href="admin_view_order.php" class="quick-action-btn">
                        <div class="action-icon">🛒</div>
                        <div class="action-text">View Orders</div>
                    </a>
                    <a href="customer.php" class="quick-action-btn">
                        <div class="action-icon">👥</div>
                        <div class="action-text">Manage Customers</div>
                    </a>
                    <a href="admin_analytics.php" class="quick-action-btn">
                        <div class="action-icon">📈</div>
                        <div class="action-text">View Analytics</div>
                    </a>
                </div>
            </div>
            
            <!-- Charts and Data -->
            <div class="dashboard-grid">
                <div class="chart-card">
                    <div class="chart-title">📈 Sales Trend (Last 7 Days)</div>
                    <canvas id="salesChart" width="400" height="200"></canvas>
                </div>
                
                <div class="chart-card">
                    <div class="chart-title">📊 Order Status Distribution</div>
                    <canvas id="statusChart" width="300" height="300"></canvas>
                </div>
            </div>
            
            <!-- Data Tables -->
            <div class="data-grid">
                <div class="data-card">
                    <div class="data-header">
                        <div class="data-title">🔥 Top Selling Products</div>
                        <a href="product_list.php" style="color: #3498db; text-decoration: none; font-size: 14px;">View All →</a>
                    </div>
                    <div class="data-content">
                        <?php foreach ($topProducts as $product): ?>
                            <div class="data-item">
                                <img src="uploaded_img/<?php echo htmlspecialchars($product['photo']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="item-image">
                                <div class="item-details">
                                    <div class="item-name"><?php echo htmlspecialchars($product['name']); ?></div>
                                    <div class="item-meta"><?php echo htmlspecialchars($product['brand']); ?> • <?php echo $product['total_sold']; ?> sold</div>
                                </div>
                                <div class="item-value">$<?php echo number_format($product['revenue'], 2); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="data-card">
                    <div class="data-header">
                        <div class="data-title">🛒 Recent Orders</div>
                        <a href="admin_view_order.php" style="color: #3498db; text-decoration: none; font-size: 14px;">View All →</a>
                    </div>
                    <div class="data-content">
                        <?php foreach ($recentOrders as $order): ?>
                            <div class="order-item">
                                <div class="order-info">
                                    <div class="order-number">Order #<?php echo $order['id']; ?></div>
                                    <div class="order-customer"><?php echo htmlspecialchars($order['customer_name']); ?></div>
                                </div>
                                <div class="item-value">$<?php echo number_format($order['total_price'], 2); ?></div>
                                <div class="order-status status-<?php echo $order['status']; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="data-card">
                    <div class="data-header">
                        <div class="data-title">⭐ Recent Reviews</div>
                        <a href="product_reviews.php" style="color: #3498db; text-decoration: none; font-size: 14px;">View All →</a>
                    </div>
                    <div class="data-content">
                        <?php foreach ($recentReviews as $review): ?>
                            <div class="data-item">
                                <div class="stat-icon" style="background: #fff3e0; color: #ff9800; margin-right: 15px;">⭐</div>
                                <div class="item-details">
                                    <div class="item-name"><?php echo htmlspecialchars($review['product_name']); ?></div>
                                    <div class="item-meta">by <?php echo htmlspecialchars($review['customer_name']); ?></div>
                                </div>
                                <div class="item-value">
                                    <span class="rating-stars"><?php echo str_repeat('⭐', $review['rating']); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Low Stock Alert -->
            <?php if (!empty($lowStockProducts)): ?>
                <div class="alert-card">
                    <div class="alert-header">
                        <h3 class="alert-title">⚠️ Low Stock Alert</h3>
                    </div>
                    <div class="data-content">
                        <?php foreach ($lowStockProducts as $product): ?>
                            <div class="data-item">
                                <img src="uploaded_img/<?php echo htmlspecialchars($product['photo']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="item-image">
                                <div class="item-details">
                                    <div class="item-name"><?php echo htmlspecialchars($product['name']); ?></div>
                                    <div class="item-meta"><?php echo htmlspecialchars($product['brand']); ?></div>
                                </div>
                                <div class="item-value" style="color: <?php echo $product['stock'] == 0 ? '#e74c3c' : '#f39c12'; ?>;">
                                    <?php echo $product['stock']; ?> left
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Sales Chart
        const salesCtx = document.getElementById('salesChart').getContext('2d');
        const salesData = <?php echo json_encode($chartData); ?>;
        
        new Chart(salesCtx, {
            type: 'line',
            data: {
                labels: salesData.map(item => new Date(item.date).toLocaleDateString()),
                datasets: [{
                    label: 'Revenue ($)',
                    data: salesData.map(item => parseFloat(item.revenue)),
                    borderColor: '#3498db',
                    backgroundColor: 'rgba(52, 152, 219, 0.1)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'Orders',
                    data: salesData.map(item => parseInt(item.orders)),
                    borderColor: '#27ae60',
                    backgroundColor: 'rgba(39, 174, 96, 0.1)',
                    tension: 0.4,
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Revenue ($)'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Orders'
                        },
                        grid: {
                            drawOnChartArea: false,
                        },
                    }
                }
            }
        });
        
        // Status Chart
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        const statusData = <?php echo json_encode($statusData); ?>;
        
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: statusData.map(item => item.status.charAt(0).toUpperCase() + item.status.slice(1).replace('_', ' ')),
                datasets: [{
                    data: statusData.map(item => parseInt(item.count)),
                    backgroundColor: [
                        '#f39c12',
                        '#3498db',
                        '#95a5a6',
                        '#17a2b8',
                        '#fd7e14',
                        '#27ae60',
                        '#e74c3c'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
        
        // Mobile sidebar toggle
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            sidebar.classList.toggle('open');
        }
        
        // Add mobile menu button if needed
        if (window.innerWidth <= 1024) {
            const header = document.querySelector('.dashboard-header');
            const menuBtn = document.createElement('button');
            menuBtn.innerHTML = '☰';
            menuBtn.style.cssText = 'background: none; border: none; font-size: 24px; cursor: pointer;';
            menuBtn.onclick = toggleSidebar;
            header.insertBefore(menuBtn, header.firstChild);
        }
    </script>
</body>
</html>
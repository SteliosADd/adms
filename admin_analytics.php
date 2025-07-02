<?php
include('include/connect.php');
session_start();

// Check if user is admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Get date range for analytics
$dateRange = $_GET['range'] ?? '30';
$startDate = date('Y-m-d', strtotime("-{$dateRange} days"));
$endDate = date('Y-m-d');

// Custom date range
if (isset($_GET['start_date']) && isset($_GET['end_date'])) {
    $startDate = $_GET['start_date'];
    $endDate = $_GET['end_date'];
    $dateRange = 'custom';
}

// Sales Analytics
$salesQuery = "SELECT 
    COUNT(*) as total_orders,
    SUM(total_price) as total_revenue,
    AVG(total_price) as avg_order_value,
    SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as completed_orders,
    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders
    FROM orders 
    WHERE DATE(order_date) BETWEEN ? AND ?";

$stmt = $conn->prepare($salesQuery);
$stmt->bind_param("ss", $startDate, $endDate);
$stmt->execute();
$salesData = $stmt->get_result()->fetch_assoc();

// Daily sales for chart
$dailySalesQuery = "SELECT 
    DATE(order_date) as date,
    COUNT(*) as orders,
    SUM(total_price) as revenue
    FROM orders 
    WHERE DATE(order_date) BETWEEN ? AND ?
    GROUP BY DATE(order_date)
    ORDER BY date";

$stmt = $conn->prepare($dailySalesQuery);
$stmt->bind_param("ss", $startDate, $endDate);
$stmt->execute();
$dailySales = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Top selling products
$topProductsQuery = "SELECT 
    p.name,
    p.brand,
    p.photo,
    SUM(oi.quantity) as units_sold,
    SUM(oi.quantity * oi.price) as revenue
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    JOIN orders o ON oi.order_id = o.id
    WHERE DATE(o.order_date) BETWEEN ? AND ?
    GROUP BY oi.product_id
    ORDER BY units_sold DESC
    LIMIT 10";

$stmt = $conn->prepare($topProductsQuery);
$stmt->bind_param("ss", $startDate, $endDate);
$stmt->execute();
$topProducts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Customer analytics
$customerQuery = "SELECT 
    COUNT(DISTINCT user_id) as total_customers,
    COUNT(DISTINCT CASE WHEN DATE(order_date) BETWEEN ? AND ? THEN user_id END) as active_customers,
    AVG(order_count) as avg_orders_per_customer
    FROM (
        SELECT user_id, COUNT(*) as order_count
        FROM orders
        GROUP BY user_id
    ) as customer_orders";

$stmt = $conn->prepare($customerQuery);
$stmt->bind_param("ss", $startDate, $endDate);
$stmt->execute();
$customerData = $stmt->get_result()->fetch_assoc();

// Top customers
$topCustomersQuery = "SELECT 
    u.name,
    u.email,
    COUNT(o.id) as total_orders,
    SUM(o.total_price) as total_spent,
    MAX(o.order_date) as last_order
    FROM users u
    JOIN orders o ON u.id = o.user_id
    WHERE DATE(o.order_date) BETWEEN ? AND ?
    GROUP BY u.id
    ORDER BY total_spent DESC
    LIMIT 10";

$stmt = $conn->prepare($topCustomersQuery);
$stmt->bind_param("ss", $startDate, $endDate);
$stmt->execute();
$topCustomers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Product category performance
$categoryQuery = "SELECT 
    p.type as category,
    COUNT(DISTINCT p.id) as product_count,
    SUM(oi.quantity) as units_sold,
    SUM(oi.quantity * oi.price) as revenue
    FROM products p
    LEFT JOIN order_items oi ON p.id = oi.product_id
    LEFT JOIN orders o ON oi.order_id = o.id
    WHERE o.id IS NULL OR DATE(o.order_date) BETWEEN ? AND ?
    GROUP BY p.type
    ORDER BY revenue DESC";

$stmt = $conn->prepare($categoryQuery);
$stmt->bind_param("ss", $startDate, $endDate);
$stmt->execute();
$categoryData = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Inventory alerts
$inventoryQuery = "SELECT 
    name,
    brand,
    stock,
    photo,
    (SELECT SUM(quantity) FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE oi.product_id = p.id AND DATE(o.order_date) BETWEEN ? AND ?) as recent_sales
    FROM products p
    WHERE stock <= 10
    ORDER BY stock ASC
    LIMIT 10";

$stmt = $conn->prepare($inventoryQuery);
$stmt->bind_param("ss", $startDate, $endDate);
$stmt->execute();
$lowStockProducts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Recent activity
$recentActivityQuery = "SELECT 
    'order' as type,
    CONCAT('New order #', o.id, ' from ', u.name) as description,
    o.total_price as amount,
    o.order_date as created_at
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE DATE(o.order_date) >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    
    UNION ALL
    
    SELECT 
        'user' as type,
        CONCAT('New user registration: ', name) as description,
        NULL as amount,
        created_at
    FROM users
    WHERE DATE(created_at) >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    
    ORDER BY created_at DESC
    LIMIT 20";

$recentActivity = $conn->query($recentActivityQuery)->fetch_all(MYSQLI_ASSOC);

// Order status distribution
$statusQuery = "SELECT 
    status,
    COUNT(*) as count
    FROM orders
    WHERE DATE(order_date) BETWEEN ? AND ?
    GROUP BY status";

$stmt = $conn->prepare($statusQuery);
$stmt->bind_param("ss", $startDate, $endDate);
$stmt->execute();
$statusData = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Calculate growth rates
$previousPeriodStart = date('Y-m-d', strtotime($startDate . ' -' . (strtotime($endDate) - strtotime($startDate)) / 86400 . ' days'));
$previousPeriodEnd = date('Y-m-d', strtotime($startDate . ' -1 day'));

$previousSalesQuery = "SELECT 
    COUNT(*) as total_orders,
    SUM(total_price) as total_revenue
    FROM orders 
    WHERE DATE(order_date) BETWEEN ? AND ?";

$stmt = $conn->prepare($previousSalesQuery);
$stmt->bind_param("ss", $previousPeriodStart, $previousPeriodEnd);
$stmt->execute();
$previousSalesData = $stmt->get_result()->fetch_assoc();

// Calculate growth percentages
$revenueGrowth = $previousSalesData['total_revenue'] > 0 ? 
    (($salesData['total_revenue'] - $previousSalesData['total_revenue']) / $previousSalesData['total_revenue']) * 100 : 0;

$orderGrowth = $previousSalesData['total_orders'] > 0 ? 
    (($salesData['total_orders'] - $previousSalesData['total_orders']) / $previousSalesData['total_orders']) * 100 : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics Dashboard - SneakZone Admin</title>
    <link rel="stylesheet" href="css/admin_style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .analytics-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .analytics-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .date-filters {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .date-filters select,
        .date-filters input {
            padding: 8px 12px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .filter-btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #007bff, #28a745);
        }
        
        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .stat-title {
            color: #666;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }
        
        .stat-value {
            font-size: 2.5em;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }
        
        .stat-growth {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 14px;
        }
        
        .growth-positive {
            color: #28a745;
        }
        
        .growth-negative {
            color: #dc3545;
        }
        
        .charts-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .chart-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .chart-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #333;
        }
        
        .data-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .data-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .data-header {
            background: #f8f9fa;
            padding: 20px;
            border-bottom: 1px solid #ddd;
        }
        
        .data-content {
            padding: 20px;
            max-height: 400px;
            overflow-y: auto;
        }
        
        .data-item {
            display: flex;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }
        
        .data-item:last-child {
            border-bottom: none;
        }
        
        .item-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 8px;
            margin-right: 15px;
        }
        
        .item-details {
            flex: 1;
        }
        
        .item-name {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .item-meta {
            color: #666;
            font-size: 14px;
        }
        
        .item-value {
            font-weight: 600;
            color: #007bff;
        }
        
        .activity-item {
            display: flex;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }
        
        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 16px;
        }
        
        .activity-order {
            background: #e3f2fd;
            color: #1976d2;
        }
        
        .activity-user {
            background: #e8f5e8;
            color: #388e3c;
        }
        
        .activity-details {
            flex: 1;
        }
        
        .activity-description {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .activity-time {
            color: #666;
            font-size: 12px;
        }
        
        .activity-amount {
            font-weight: 600;
            color: #007bff;
        }
        
        .alert-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
            border-left: 4px solid #ffc107;
        }
        
        .alert-header {
            background: #fff3cd;
            padding: 15px 20px;
            border-bottom: 1px solid #ffeaa7;
        }
        
        .alert-title {
            color: #856404;
            font-weight: 600;
            margin: 0;
        }
        
        .progress-bar {
            width: 100%;
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
            margin-top: 10px;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #007bff, #28a745);
            transition: width 0.3s ease;
        }
        
        @media (max-width: 768px) {
            .analytics-header {
                flex-direction: column;
                gap: 15px;
            }
            
            .date-filters {
                flex-wrap: wrap;
            }
            
            .charts-grid {
                grid-template-columns: 1fr;
            }
            
            .data-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            }
        }
    </style>
</head>
<body>
    <?php include('include/admin_header.php'); ?>
    
    <div class="analytics-container">
        <div class="analytics-header">
            <div>
                <h1>Analytics Dashboard</h1>
                <p>Business insights and performance metrics</p>
            </div>
            <form method="GET" class="date-filters">
                <select name="range" onchange="toggleCustomDates(this.value)">
                    <option value="7" <?php echo $dateRange === '7' ? 'selected' : ''; ?>>Last 7 days</option>
                    <option value="30" <?php echo $dateRange === '30' ? 'selected' : ''; ?>>Last 30 days</option>
                    <option value="90" <?php echo $dateRange === '90' ? 'selected' : ''; ?>>Last 90 days</option>
                    <option value="365" <?php echo $dateRange === '365' ? 'selected' : ''; ?>>Last year</option>
                    <option value="custom" <?php echo $dateRange === 'custom' ? 'selected' : ''; ?>>Custom range</option>
                </select>
                <div id="customDates" style="display: <?php echo $dateRange === 'custom' ? 'flex' : 'none'; ?>; gap: 10px;">
                    <input type="date" name="start_date" value="<?php echo $startDate; ?>">
                    <input type="date" name="end_date" value="<?php echo $endDate; ?>">
                </div>
                <button type="submit" class="filter-btn">Apply</button>
            </form>
        </div>
        
        <!-- Key Metrics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-title">Total Revenue</div>
                    <div class="stat-icon" style="background: #e3f2fd; color: #1976d2;">💰</div>
                </div>
                <div class="stat-value">$<?php echo number_format($salesData['total_revenue'], 2); ?></div>
                <div class="stat-growth <?php echo $revenueGrowth >= 0 ? 'growth-positive' : 'growth-negative'; ?>">
                    <?php echo $revenueGrowth >= 0 ? '↗️' : '↘️'; ?>
                    <?php echo abs(round($revenueGrowth, 1)); ?>% vs previous period
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-title">Total Orders</div>
                    <div class="stat-icon" style="background: #e8f5e8; color: #388e3c;">📦</div>
                </div>
                <div class="stat-value"><?php echo number_format($salesData['total_orders']); ?></div>
                <div class="stat-growth <?php echo $orderGrowth >= 0 ? 'growth-positive' : 'growth-negative'; ?>">
                    <?php echo $orderGrowth >= 0 ? '↗️' : '↘️'; ?>
                    <?php echo abs(round($orderGrowth, 1)); ?>% vs previous period
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-title">Average Order Value</div>
                    <div class="stat-icon" style="background: #fff3e0; color: #f57c00;">💳</div>
                </div>
                <div class="stat-value">$<?php echo number_format($salesData['avg_order_value'], 2); ?></div>
                <div class="stat-growth">
                    📊 Based on <?php echo number_format($salesData['total_orders']); ?> orders
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-title">Active Customers</div>
                    <div class="stat-icon" style="background: #f3e5f5; color: #7b1fa2;">👥</div>
                </div>
                <div class="stat-value"><?php echo number_format($customerData['active_customers']); ?></div>
                <div class="stat-growth">
                    📈 <?php echo number_format($customerData['total_customers']); ?> total customers
                </div>
            </div>
        </div>
        
        <!-- Charts -->
        <div class="charts-grid">
            <div class="chart-card">
                <div class="chart-title">Daily Sales Trend</div>
                <canvas id="salesChart" width="400" height="200"></canvas>
            </div>
            
            <div class="chart-card">
                <div class="chart-title">Order Status Distribution</div>
                <canvas id="statusChart" width="300" height="300"></canvas>
            </div>
        </div>
        
        <!-- Data Tables -->
        <div class="data-grid">
            <div class="data-card">
                <div class="data-header">
                    <h3>Top Selling Products</h3>
                </div>
                <div class="data-content">
                    <?php foreach ($topProducts as $product): ?>
                        <div class="data-item">
                            <img src="uploaded_img/<?php echo htmlspecialchars($product['photo']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="item-image">
                            <div class="item-details">
                                <div class="item-name"><?php echo htmlspecialchars($product['name']); ?></div>
                                <div class="item-meta"><?php echo htmlspecialchars($product['brand']); ?> • <?php echo number_format($product['units_sold']); ?> sold</div>
                            </div>
                            <div class="item-value">$<?php echo number_format($product['revenue'], 2); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="data-card">
                <div class="data-header">
                    <h3>Top Customers</h3>
                </div>
                <div class="data-content">
                    <?php foreach ($topCustomers as $customer): ?>
                        <div class="data-item">
                            <div class="stat-icon" style="background: #e3f2fd; color: #1976d2; margin-right: 15px;">👤</div>
                            <div class="item-details">
                                <div class="item-name"><?php echo htmlspecialchars($customer['name']); ?></div>
                                <div class="item-meta"><?php echo number_format($customer['total_orders']); ?> orders • Last: <?php echo date('M j', strtotime($customer['last_order'])); ?></div>
                            </div>
                            <div class="item-value">$<?php echo number_format($customer['total_spent'], 2); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="data-card">
                <div class="data-header">
                    <h3>Category Performance</h3>
                </div>
                <div class="data-content">
                    <?php foreach ($categoryData as $category): ?>
                        <div class="data-item">
                            <div class="stat-icon" style="background: #e8f5e8; color: #388e3c; margin-right: 15px;">📂</div>
                            <div class="item-details">
                                <div class="item-name"><?php echo htmlspecialchars(ucfirst($category['category'])); ?></div>
                                <div class="item-meta"><?php echo number_format($category['product_count']); ?> products • <?php echo number_format($category['units_sold'] ?? 0); ?> sold</div>
                            </div>
                            <div class="item-value">$<?php echo number_format($category['revenue'] ?? 0, 2); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="data-card">
                <div class="data-header">
                    <h3>Recent Activity</h3>
                </div>
                <div class="data-content">
                    <?php foreach ($recentActivity as $activity): ?>
                        <div class="activity-item">
                            <div class="activity-icon activity-<?php echo $activity['type']; ?>">
                                <?php echo $activity['type'] === 'order' ? '🛒' : '👤'; ?>
                            </div>
                            <div class="activity-details">
                                <div class="activity-description"><?php echo htmlspecialchars($activity['description']); ?></div>
                                <div class="activity-time"><?php echo date('M j, g:i A', strtotime($activity['created_at'])); ?></div>
                            </div>
                            <?php if ($activity['amount']): ?>
                                <div class="activity-amount">$<?php echo number_format($activity['amount'], 2); ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- Inventory Alerts -->
        <?php if (!empty($lowStockProducts)): ?>
            <div class="alert-card">
                <div class="alert-header">
                    <h3 class="alert-title">⚠️ Low Stock Alerts</h3>
                </div>
                <div class="data-content">
                    <?php foreach ($lowStockProducts as $product): ?>
                        <div class="data-item">
                            <img src="uploaded_img/<?php echo htmlspecialchars($product['photo']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="item-image">
                            <div class="item-details">
                                <div class="item-name"><?php echo htmlspecialchars($product['name']); ?></div>
                                <div class="item-meta"><?php echo htmlspecialchars($product['brand']); ?> • <?php echo number_format($product['recent_sales'] ?? 0); ?> recent sales</div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo min(100, ($product['stock'] / 50) * 100); ?>%;"></div>
                                </div>
                            </div>
                            <div class="item-value" style="color: <?php echo $product['stock'] == 0 ? '#dc3545' : '#ffc107'; ?>;">
                                <?php echo $product['stock']; ?> left
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        // Sales Chart
        const salesCtx = document.getElementById('salesChart').getContext('2d');
        const salesData = <?php echo json_encode($dailySales); ?>;
        
        new Chart(salesCtx, {
            type: 'line',
            data: {
                labels: salesData.map(item => new Date(item.date).toLocaleDateString()),
                datasets: [{
                    label: 'Revenue',
                    data: salesData.map(item => parseFloat(item.revenue)),
                    borderColor: '#007bff',
                    backgroundColor: 'rgba(0, 123, 255, 0.1)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'Orders',
                    data: salesData.map(item => parseInt(item.orders)),
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
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
                        '#ffc107',
                        '#007bff',
                        '#6c757d',
                        '#17a2b8',
                        '#fd7e14',
                        '#28a745',
                        '#dc3545'
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
        
        function toggleCustomDates(value) {
            const customDates = document.getElementById('customDates');
            customDates.style.display = value === 'custom' ? 'flex' : 'none';
        }
    </script>
    
    <?php include('include/admin_footer.php'); ?>
</body>
</html>
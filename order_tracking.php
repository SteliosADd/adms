<?php
include('include/connect.php');
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

$userId = $_SESSION['user_id'];
$trackingNumber = $_GET['tracking'] ?? '';
$orderId = $_GET['order_id'] ?? '';
$error = '';
$order = null;
$orderItems = [];
$trackingHistory = [];

// If tracking number is provided, find the order
if (!empty($trackingNumber)) {
    $stmt = $conn->prepare("SELECT * FROM orders WHERE tracking_number = ? AND user_id = ?");
    $stmt->bind_param("si", $trackingNumber, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $order = $result->fetch_assoc();
        $orderId = $order['id'];
    } else {
        $error = "Order not found or you don't have permission to view this order.";
    }
}

// If order ID is provided, get order details
if (!empty($orderId) && empty($error)) {
    if (!$order) {
        $stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $orderId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $order = $result->fetch_assoc();
        } else {
            $error = "Order not found or you don't have permission to view this order.";
        }
    }
    
    if ($order) {
        // Get order items
        $stmt = $conn->prepare("SELECT oi.*, p.name, p.photo as image, p.brand 
                              FROM order_items oi 
                              JOIN products p ON oi.product_id = p.id 
                              WHERE oi.order_id = ?");
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $orderItems = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Get tracking history
        $stmt = $conn->prepare("SELECT * FROM order_tracking WHERE order_id = ? ORDER BY created_at ASC");
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $trackingHistory = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}

// Get user's recent orders for quick access
$stmt = $conn->prepare("SELECT id, tracking_number, total_price, status, order_date 
                      FROM orders 
                      WHERE user_id = ? 
                      ORDER BY order_date DESC 
                      LIMIT 5");
$stmt->bind_param("i", $userId);
$stmt->execute();
$recentOrders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Define order status steps
$statusSteps = [
    'pending' => ['label' => 'Order Placed', 'icon' => '📋', 'description' => 'Your order has been received and is being processed'],
    'confirmed' => ['label' => 'Confirmed', 'icon' => '✅', 'description' => 'Your order has been confirmed and is being prepared'],
    'processing' => ['label' => 'Processing', 'icon' => '⚙️', 'description' => 'Your order is being prepared for shipment'],
    'shipped' => ['label' => 'Shipped', 'icon' => '🚚', 'description' => 'Your order has been shipped and is on its way'],
    'out_for_delivery' => ['label' => 'Out for Delivery', 'icon' => '🚛', 'description' => 'Your order is out for delivery'],
    'delivered' => ['label' => 'Delivered', 'icon' => '📦', 'description' => 'Your order has been delivered successfully'],
    'cancelled' => ['label' => 'Cancelled', 'icon' => '❌', 'description' => 'Your order has been cancelled']
];

function getStatusIndex($status) {
    $statuses = ['pending', 'confirmed', 'processing', 'shipped', 'out_for_delivery', 'delivered'];
    return array_search($status, $statuses);
}

function isStatusCompleted($currentStatus, $checkStatus) {
    if ($currentStatus === 'cancelled') return false;
    return getStatusIndex($currentStatus) >= getStatusIndex($checkStatus);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Tracking - SneakZone</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .tracking-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .tracking-header {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            text-align: center;
        }
        
        .tracking-form {
            max-width: 500px;
            margin: 0 auto;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #007bff;
        }
        
        .track-btn {
            width: 100%;
            background: #007bff;
            color: white;
            border: none;
            padding: 12px;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .track-btn:hover {
            background: #0056b3;
        }
        
        .recent-orders {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }
        
        .order-item:last-child {
            border-bottom: none;
        }
        
        .order-info {
            flex: 1;
        }
        
        .order-number {
            font-weight: 600;
            color: #007bff;
        }
        
        .order-date {
            color: #666;
            font-size: 14px;
        }
        
        .order-status {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-confirmed {
            background: #cce5ff;
            color: #004085;
        }
        
        .status-processing {
            background: #e2e3e5;
            color: #383d41;
        }
        
        .status-shipped {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .status-out_for_delivery {
            background: #f8d7da;
            color: #721c24;
        }
        
        .status-delivered {
            background: #d4edda;
            color: #155724;
        }
        
        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }
        
        .track-link {
            background: #28a745;
            color: white;
            padding: 6px 12px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 12px;
            font-weight: 600;
        }
        
        .order-details {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-bottom: 30px;
        }
        
        .order-header {
            background: #f8f9fa;
            padding: 20px;
            border-bottom: 1px solid #ddd;
        }
        
        .order-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 15px;
        }
        
        .meta-item {
            display: flex;
            flex-direction: column;
        }
        
        .meta-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        
        .meta-value {
            font-weight: 600;
            color: #333;
        }
        
        .tracking-progress {
            padding: 30px;
        }
        
        .progress-steps {
            display: flex;
            justify-content: space-between;
            position: relative;
            margin-bottom: 40px;
        }
        
        .progress-line {
            position: absolute;
            top: 25px;
            left: 0;
            right: 0;
            height: 2px;
            background: #ddd;
            z-index: 1;
        }
        
        .progress-line-active {
            position: absolute;
            top: 25px;
            left: 0;
            height: 2px;
            background: #28a745;
            z-index: 2;
            transition: width 0.5s ease;
        }
        
        .progress-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            z-index: 3;
            background: white;
            padding: 0 10px;
        }
        
        .step-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            margin-bottom: 10px;
            border: 3px solid #ddd;
            background: white;
            transition: all 0.3s ease;
        }
        
        .step-icon.completed {
            background: #28a745;
            border-color: #28a745;
            color: white;
        }
        
        .step-icon.current {
            background: #007bff;
            border-color: #007bff;
            color: white;
            animation: pulse 2s infinite;
        }
        
        .step-label {
            font-size: 12px;
            font-weight: 600;
            text-align: center;
            color: #666;
        }
        
        .step-label.completed,
        .step-label.current {
            color: #333;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        
        .tracking-history {
            background: #f8f9fa;
            padding: 20px;
        }
        
        .history-item {
            display: flex;
            align-items: flex-start;
            padding: 15px 0;
            border-bottom: 1px solid #ddd;
        }
        
        .history-item:last-child {
            border-bottom: none;
        }
        
        .history-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #007bff;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            flex-shrink: 0;
        }
        
        .history-content {
            flex: 1;
        }
        
        .history-title {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .history-description {
            color: #666;
            margin-bottom: 5px;
        }
        
        .history-time {
            font-size: 12px;
            color: #999;
        }
        
        .order-items {
            padding: 20px;
        }
        
        .item-card {
            display: flex;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }
        
        .item-card:last-child {
            border-bottom: none;
        }
        
        .item-image {
            width: 80px;
            height: 80px;
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
        
        .item-brand {
            color: #666;
            font-size: 14px;
            margin-bottom: 5px;
        }
        
        .item-quantity {
            color: #666;
            font-size: 14px;
        }
        
        .item-price {
            font-weight: 600;
            color: #007bff;
        }
        
        .error-message {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .shipping-address {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }
        
        .address-title {
            font-weight: 600;
            margin-bottom: 10px;
            color: #333;
        }
        
        .address-content {
            color: #666;
            line-height: 1.5;
        }
        
        @media (max-width: 768px) {
            .progress-steps {
                flex-direction: column;
                align-items: center;
            }
            
            .progress-line,
            .progress-line-active {
                display: none;
            }
            
            .progress-step {
                margin-bottom: 20px;
            }
            
            .order-meta {
                grid-template-columns: 1fr;
            }
            
            .item-card {
                flex-direction: column;
                text-align: center;
            }
            
            .item-image {
                margin-right: 0;
                margin-bottom: 10px;
            }
        }
    </style>
</head>
<body>
    <?php include('include/header.php'); ?>
    
    <div class="tracking-container">
        <?php if (empty($order)): ?>
            <div class="tracking-header">
                <h1>Track Your Order</h1>
                <p>Enter your tracking number to see the current status of your order</p>
                
                <?php if ($error): ?>
                    <div class="error-message">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <form method="GET" class="tracking-form">
                    <div class="form-group">
                        <label for="tracking">Tracking Number</label>
                        <input type="text" id="tracking" name="tracking" value="<?php echo htmlspecialchars($trackingNumber); ?>" placeholder="Enter your tracking number" required>
                    </div>
                    <button type="submit" class="track-btn">Track Order</button>
                </form>
            </div>
            
            <?php if (!empty($recentOrders)): ?>
                <div class="recent-orders">
                    <h3>Your Recent Orders</h3>
                    <?php foreach ($recentOrders as $recentOrder): ?>
                        <div class="order-item">
                            <div class="order-info">
                                <div class="order-number">#<?php echo $recentOrder['id']; ?></div>
                                <div class="order-date"><?php echo date('M j, Y', strtotime($recentOrder['order_date'])); ?> • $<?php echo number_format($recentOrder['total_price'], 2); ?></div>
                            </div>
                            <div class="order-status status-<?php echo $recentOrder['status']; ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $recentOrder['status'])); ?>
                            </div>
                            <a href="?tracking=<?php echo urlencode($recentOrder['tracking_number']); ?>" class="track-link">Track</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="order-details">
                <div class="order-header">
                    <h2>Order #<?php echo $order['id']; ?></h2>
                    <div class="order-meta">
                        <div class="meta-item">
                            <div class="meta-label">Tracking Number</div>
                            <div class="meta-value"><?php echo htmlspecialchars($order['tracking_number']); ?></div>
                        </div>
                        <div class="meta-item">
                            <div class="meta-label">Order Date</div>
                            <div class="meta-value"><?php echo date('M j, Y g:i A', strtotime($order['order_date'])); ?></div>
                        </div>
                        <div class="meta-item">
                            <div class="meta-label">Total Amount</div>
                            <div class="meta-value">$<?php echo number_format($order['total_price'], 2); ?></div>
                        </div>
                        <div class="meta-item">
                            <div class="meta-label">Payment Method</div>
                            <div class="meta-value"><?php echo htmlspecialchars($order['method']); ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="tracking-progress">
                    <h3>Order Progress</h3>
                    <div class="progress-steps">
                        <div class="progress-line"></div>
                        <?php
                        $currentStatusIndex = getStatusIndex($order['status']);
                        $totalSteps = 6; // pending to delivered
                        $progressPercentage = $order['status'] === 'cancelled' ? 0 : (($currentStatusIndex + 1) / $totalSteps) * 100;
                        ?>
                        <div class="progress-line-active" style="width: <?php echo $progressPercentage; ?>%;"></div>
                        
                        <?php foreach (['pending', 'confirmed', 'processing', 'shipped', 'out_for_delivery', 'delivered'] as $status): ?>
                            <?php
                            $stepClass = '';
                            if ($order['status'] === 'cancelled') {
                                $stepClass = '';
                            } elseif (isStatusCompleted($order['status'], $status)) {
                                $stepClass = $order['status'] === $status ? 'current' : 'completed';
                            }
                            ?>
                            <div class="progress-step">
                                <div class="step-icon <?php echo $stepClass; ?>">
                                    <?php echo $statusSteps[$status]['icon']; ?>
                                </div>
                                <div class="step-label <?php echo $stepClass; ?>">
                                    <?php echo $statusSteps[$status]['label']; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div style="text-align: center; color: #666; margin-bottom: 20px;">
                        <?php echo $statusSteps[$order['status']]['description']; ?>
                    </div>
                    
                    <?php if ($order['status'] === 'cancelled'): ?>
                        <div style="text-align: center; background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px;">
                            ❌ This order has been cancelled.
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($trackingHistory)): ?>
                    <div class="tracking-history">
                        <h3>Tracking History</h3>
                        <?php foreach ($trackingHistory as $history): ?>
                            <div class="history-item">
                                <div class="history-icon">
                                    <?php echo $statusSteps[$history['status']]['icon'] ?? '📋'; ?>
                                </div>
                                <div class="history-content">
                                    <div class="history-title"><?php echo htmlspecialchars($history['title']); ?></div>
                                    <div class="history-description"><?php echo htmlspecialchars($history['description']); ?></div>
                                    <div class="history-time"><?php echo date('M j, Y g:i A', strtotime($history['created_at'])); ?></div>
                                    <?php if ($history['location']): ?>
                                        <div class="history-time">📍 <?php echo htmlspecialchars($history['location']); ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <div class="order-items">
                    <h3>Order Items</h3>
                    <?php foreach ($orderItems as $item): ?>
                        <div class="item-card">
                            <img src="uploaded_img/<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="item-image">
                            <div class="item-details">
                                <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                <div class="item-brand"><?php echo htmlspecialchars($item['brand']); ?></div>
                                <div class="item-quantity">Quantity: <?php echo $item['quantity']; ?></div>
                            </div>
                            <div class="item-price">$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="shipping-address">
                    <div class="address-title">Shipping Address</div>
                    <div class="address-content">
                        <?php echo htmlspecialchars($order['name']); ?><br>
                        <?php echo htmlspecialchars($order['address']); ?><br>
                        <?php echo htmlspecialchars($order['city']); ?>, <?php echo htmlspecialchars($order['state']); ?> <?php echo htmlspecialchars($order['zip_code']); ?><br>
                        <?php echo htmlspecialchars($order['country']); ?><br>
                        Phone: <?php echo htmlspecialchars($order['number']); ?><br>
                        Email: <?php echo htmlspecialchars($order['email']); ?>
                    </div>
                </div>
            </div>
            
            <div style="text-align: center; margin-top: 20px;">
                <a href="order_tracking.php" class="btn" style="background: #6c757d; color: white; padding: 12px 24px; border-radius: 5px; text-decoration: none; margin-right: 10px;">Track Another Order</a>
                <a href="order_history.php" class="btn" style="background: #007bff; color: white; padding: 12px 24px; border-radius: 5px; text-decoration: none;">View All Orders</a>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include('include/footer.php'); ?>
</body>
</html>
<?php
include('include/connect.php');
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page with return URL
    header("Location: login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

$userId = $_SESSION['user_id'];
$message = '';

// Fetch user's orders
$stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY order_date DESC");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$orders = [];

while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
}

// Get order details if an order is selected
$selectedOrder = null;
$orderItems = [];

if (isset($_GET['order_id']) && is_numeric($_GET['order_id'])) {
    $orderId = (int)$_GET['order_id'];
    
    // Verify this order belongs to the current user
    $stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $orderId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $selectedOrder = $result->fetch_assoc();
        
        // Get order items
        $stmt = $conn->prepare("SELECT oi.*, p.name, p.photo as image 
                              FROM order_items oi 
                              JOIN products p ON oi.product_id = p.id 
                              WHERE oi.order_id = ?");
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $orderItems[] = $row;
        }
    } else {
        $message = "Order not found or access denied";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order History - Online Store</title>
    <style>
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .page-title {
            margin-bottom: 30px;
            text-align: center;
        }
        .orders-container {
            display: flex;
            flex-wrap: wrap;
            gap: 30px;
        }
        .orders-list {
            flex: 1;
            min-width: 300px;
        }
        .order-details {
            flex: 2;
            min-width: 300px;
            background-color: #f5f5f5;
            padding: 20px;
            border-radius: 8px;
        }
        .orders-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .orders-table th {
            background-color: #f5f5f5;
            padding: 12px;
            text-align: left;
            border-bottom: 2px solid #ddd;
        }
        .orders-table td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
            vertical-align: middle;
        }
        .orders-table tr:hover {
            background-color: #f9f9f9;
            cursor: pointer;
        }
        .orders-table tr.selected {
            background-color: #e8f5e9;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: bold;
            text-transform: uppercase;
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
        .order-items {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .order-items th {
            background-color: #f9f9f9;
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .order-items td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }
        .product-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
        }
        .order-summary {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .summary-row.total {
            font-weight: bold;
            font-size: 1.2em;
            margin-top: 10px;
        }
        .empty-message {
            text-align: center;
            padding: 50px;
            color: #666;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #333;
            text-decoration: none;
        }
        .back-link i {
            margin-right: 5px;
        }
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .order-header h2 {
            margin: 0;
        }
        .order-date {
            color: #666;
            font-size: 0.9em;
        }
        .shipping-info {
            margin-top: 20px;
            padding: 15px;
            background-color: #f9f9f9;
            border-radius: 4px;
        }
        .shipping-info h3 {
            margin-top: 0;
            margin-bottom: 10px;
            font-size: 1.1em;
        }
        .info-row {
            margin-bottom: 5px;
        }
        .info-row strong {
            display: inline-block;
            width: 120px;
        }
        #message {
            padding: 10px;
            margin: 20px 0;
            border-radius: 4px;
            text-align: center;
        }
        .error {
            background-color: #f2dede;
            color: #a94442;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/include/header.php'; ?>

    <div class="container">
        <h1 class="page-title">Order History</h1>
        
        <?php if(!empty($message)): ?>
            <div id="message" class="error">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <div class="orders-container">
            <div class="orders-list">
                <?php if(count($orders) > 0): ?>
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Date</th>
                                <th>Total</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($orders as $order): ?>
                                <tr class="<?php echo (isset($_GET['order_id']) && $_GET['order_id'] == $order['id']) ? 'selected' : ''; ?>" onclick="window.location='order_history.php?order_id=<?php echo $order['id']; ?>'">
                                    <td>#<?php echo $order['id']; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                                    <td>$<?php echo number_format($order['total_price'], 2); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-message">
                        <p>You haven't placed any orders yet.</p>
                        <a href="product_list.php" class="continue-shopping">Start Shopping</a>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if($selectedOrder): ?>
                <div class="order-details">
                    <a href="order_history.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to all orders</a>
                    
                    <div class="order-header">
                        <h2>Order #<?php echo $selectedOrder['id']; ?></h2>
                        <span class="status-badge status-<?php echo strtolower($selectedOrder['status']); ?>">
                            <?php echo ucfirst($selectedOrder['status']); ?>
                        </span>
                    </div>
                    
                    <div class="order-date">
                        Placed on <?php echo date('F d, Y \a\t h:i A', strtotime($selectedOrder['order_date'])); ?>
                    </div>
                    
                    <?php if(count($orderItems) > 0): ?>
                        <table class="order-items">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Name</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($orderItems as $item): ?>
                                    <tr>
                                        <td>
                                            <?php $imagePath = !empty($item['image']) ? $item['image'] : 'img/air.png'; ?>
                                            <img src="<?php echo htmlspecialchars($imagePath); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="product-image">
                                        </td>
                                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                                        <td>$<?php echo number_format($item['price'], 2); ?></td>
                                        <td><?php echo $item['quantity']; ?></td>
                                        <td>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <div class="order-summary">
                            <div class="summary-row">
                                <span>Subtotal:</span>
                                <span>$<?php echo number_format($selectedOrder['total_price'], 2); ?></span>
                            </div>
                            <div class="summary-row">
                                <span>Shipping:</span>
                                <span>$0.00</span>
                            </div>
                            <div class="summary-row total">
                                <span>Total:</span>
                                <span>$<?php echo number_format($selectedOrder['total_price'], 2); ?></span>
                            </div>
                        </div>
                        
                        <div class="shipping-info">
                            <h3>Shipping Information</h3>
                            <div class="info-row">
                                <strong>Name:</strong> <?php echo htmlspecialchars($selectedOrder['name']); ?>
                            </div>
                            <div class="info-row">
                                <strong>Email:</strong> <?php echo htmlspecialchars($selectedOrder['email']); ?>
                            </div>
                            <div class="info-row">
                                <strong>Phone:</strong> <?php echo htmlspecialchars($selectedOrder['number']); ?>
                            </div>
                            <div class="info-row">
                                <strong>Address:</strong> <?php echo htmlspecialchars($selectedOrder['address']); ?>
                            </div>
                            <div class="info-row">
                                <strong>City:</strong> <?php echo htmlspecialchars($selectedOrder['city']); ?>
                            </div>
                            <div class="info-row">
                                <strong>State:</strong> <?php echo htmlspecialchars($selectedOrder['state']); ?>
                            </div>
                            <div class="info-row">
                                <strong>Country:</strong> <?php echo htmlspecialchars($selectedOrder['country']); ?>
                            </div>
                            <div class="info-row">
                                <strong>ZIP Code:</strong> <?php echo htmlspecialchars($selectedOrder['zip_code']); ?>
                            </div>
                            <div class="info-row">
                                <strong>Payment Method:</strong> <?php echo htmlspecialchars($selectedOrder['method']); ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="empty-message">
                            <p>No items found for this order.</p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php elseif(count($orders) > 0): ?>
                <div class="order-details">
                    <div class="empty-message">
                        <p>Select an order to view details.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include __DIR__ . '/include/footer.php'; ?>
</body>
</html>
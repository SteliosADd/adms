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

// Check if order ID is provided
if (!isset($_GET['order_id']) || !is_numeric($_GET['order_id'])) {
    header("Location: admin_dashboard.php");
    exit();
}

$orderId = (int)$_GET['order_id'];

// Handle order status updates
if (isset($_POST['update_status']) && isset($_POST['status'])) {
    $status = $_POST['status'];
    
    // Validate status
    $validStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
    if (in_array($status, $validStatuses)) {
        // Update the order status
        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $orderId);
        
        if ($stmt->execute()) {
            $message = "Order status updated successfully!";
            $messageType = "success";
        } else {
            $message = "Failed to update order status: " . $conn->error;
            $messageType = "error";
        }
    } else {
        $message = "Invalid status selected!";
        $messageType = "error";
    }
}

// Fetch order details
$stmt = $conn->prepare("SELECT o.*, u.username, u.email as user_email 
                      FROM orders o 
                      JOIN users u ON o.user_id = u.id 
                      WHERE o.id = ?");
$stmt->bind_param("i", $orderId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: admin_dashboard.php");
    exit();
}

$order = $result->fetch_assoc();

// Get order items
$stmt = $conn->prepare("SELECT oi.*, p.name, p.photo as image, p.seller_id, u.username as seller_name 
                      FROM order_items oi 
                      JOIN products p ON oi.product_id = p.id 
                      JOIN users u ON p.seller_id = u.id 
                      WHERE oi.order_id = ?");
$stmt->bind_param("i", $orderId);
$stmt->execute();
$result = $stmt->get_result();
$orderItems = [];

while ($row = $result->fetch_assoc()) {
    $orderItems[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order #<?php echo $orderId; ?> Details - Admin Dashboard</title>
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
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .page-title {
            margin-bottom: 30px;
            color: var(--dark-color);
        }
        
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: var(--primary-color);
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
            color: var(--dark-color);
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
        
        .order-date {
            color: var(--secondary-color);
            font-size: 0.9em;
            margin-bottom: 20px;
        }
        
        .order-sections {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
        }
        
        .order-section {
            background-color: white;
            border-radius: 5px;
            padding: 20px;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            margin-bottom: 20px;
        }
        
        .order-section h3 {
            margin-top: 0;
            margin-bottom: 15px;
            color: var(--dark-color);
            border-bottom: 1px solid #e3e6f0;
            padding-bottom: 10px;
        }
        
        .order-items-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .order-items-table th {
            background-color: #f8f9fc;
            padding: 12px;
            text-align: left;
            color: var(--dark-color);
            border-bottom: 1px solid #e3e6f0;
        }
        
        .order-items-table td {
            padding: 12px;
            border-bottom: 1px solid #e3e6f0;
            color: var(--secondary-color);
        }
        
        .product-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
        }
        
        .info-row {
            margin-bottom: 10px;
            display: flex;
        }
        
        .info-row strong {
            width: 120px;
            display: inline-block;
            color: var(--dark-color);
        }
        
        .order-summary {
            margin-top: 20px;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 5px 0;
        }
        
        .summary-row.total {
            font-weight: bold;
            font-size: 1.2em;
            border-top: 1px solid #e3e6f0;
            padding-top: 10px;
            margin-top: 10px;
            color: var(--dark-color);
        }
        
        .status-form {
            margin-top: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: var(--dark-color);
        }
        
        .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #d1d3e2;
            border-radius: 4px;
        }
        
        .btn {
            display: inline-block;
            padding: 8px 16px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
        }
        
        .btn:hover {
            background-color: #2e59d9;
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
    </style>
</head>
<body>
    <?php include 'include/header.php'; ?>

    <div class="container">
        <a href="admin_dashboard.php" class="back-link"><i class="fa fa-arrow-left"></i> Back to Dashboard</a>
        
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
        
        <div class="order-header">
            <h2>Order #<?php echo $order['id']; ?></h2>
            <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                <?php echo ucfirst($order['status']); ?>
            </span>
        </div>
        
        <div class="order-date">
            Placed on <?php echo date('F d, Y \a\t h:i A', strtotime($order['order_date'])); ?>
        </div>
        
        <div class="order-sections">
            <div>
                <div class="order-section">
                    <h3>Order Items</h3>
                    <?php if(count($orderItems) > 0): ?>
                        <table class="order-items-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Name</th>
                                    <th>Seller</th>
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
                                        <td><?php echo htmlspecialchars($item['seller_name']); ?></td>
                                        <td>$<?php echo number_format($item['price'], 2); ?></td>
                                        <td><?php echo $item['quantity']; ?></td>
                                        <td>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>No items found for this order.</p>
                    <?php endif; ?>
                </div>
                
                <div class="order-section">
                    <h3>Shipping Information</h3>
                    <div class="info-row">
                        <strong>Name:</strong> <?php echo htmlspecialchars($order['name']); ?>
                    </div>
                    <div class="info-row">
                        <strong>Email:</strong> <?php echo htmlspecialchars($order['email']); ?>
                    </div>
                    <div class="info-row">
                        <strong>Phone:</strong> <?php echo htmlspecialchars($order['number']); ?>
                    </div>
                    <div class="info-row">
                        <strong>Address:</strong> <?php echo htmlspecialchars($order['address']); ?>
                    </div>
                    <div class="info-row">
                        <strong>City:</strong> <?php echo htmlspecialchars($order['city']); ?>
                    </div>
                    <div class="info-row">
                        <strong>State:</strong> <?php echo htmlspecialchars($order['state']); ?>
                    </div>
                    <div class="info-row">
                        <strong>Country:</strong> <?php echo htmlspecialchars($order['country']); ?>
                    </div>
                    <div class="info-row">
                        <strong>ZIP Code:</strong> <?php echo htmlspecialchars($order['zip_code']); ?>
                    </div>
                    <div class="info-row">
                        <strong>Payment Method:</strong> <?php echo htmlspecialchars($order['method']); ?>
                    </div>
                </div>
            </div>
            
            <div>
                <div class="order-section">
                    <h3>Customer Information</h3>
                    <div class="info-row">
                        <strong>Username:</strong> <?php echo htmlspecialchars($order['username']); ?>
                    </div>
                    <div class="info-row">
                        <strong>Email:</strong> <?php echo htmlspecialchars($order['user_email']); ?>
                    </div>
                    <div class="info-row">
                        <strong>User ID:</strong> <?php echo $order['user_id']; ?>
                    </div>
                </div>
                
                <div class="order-section">
                    <h3>Order Summary</h3>
                    <div class="order-summary">
                        <div class="summary-row">
                            <span>Subtotal:</span>
                            <span>$<?php echo number_format($order['total_amount'], 2); ?></span>
                        </div>
                        <div class="summary-row">
                            <span>Shipping:</span>
                            <span>$0.00</span>
                        </div>
                        <div class="summary-row total">
                            <span>Total:</span>
                            <span>$<?php echo number_format($order['total_amount'], 2); ?></span>
                        </div>
                    </div>
                    
                    <div class="status-form">
                        <h3>Update Order Status</h3>
                        <form method="post">
                            <div class="form-group">
                                <label for="status">Status:</label>
                                <select name="status" id="status">
                                    <option value="pending" <?php echo ($order['status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                    <option value="processing" <?php echo ($order['status'] == 'processing') ? 'selected' : ''; ?>>Processing</option>
                                    <option value="shipped" <?php echo ($order['status'] == 'shipped') ? 'selected' : ''; ?>>Shipped</option>
                                    <option value="delivered" <?php echo ($order['status'] == 'delivered') ? 'selected' : ''; ?>>Delivered</option>
                                    <option value="cancelled" <?php echo ($order['status'] == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </div>
                            <button type="submit" name="update_status" class="btn">Update Status</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'include/footer.php'; ?>
</body>
</html>
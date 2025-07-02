<?php
include('include/connect.php');
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

$userId = $_SESSION['user_id'];
$errors = [];
$success = false;
$orderComplete = false;
$couponDiscount = 0;
$appliedCoupon = null;

// Fetch user information
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$userResult = $stmt->get_result();
$user = $userResult->fetch_assoc();

// Fetch cart items
$stmt = $conn->prepare("SELECT c.*, p.name, p.price, p.photo as image, p.stock 
                      FROM cart c
                      JOIN products p ON c.product_id = p.id 
                      WHERE c.user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$cartItems = [];
$subtotal = 0;
$productNames = [];

while ($row = $result->fetch_assoc()) {
    // Check stock availability
    if ($row['stock'] < $row['quantity']) {
        $errors[] = "Insufficient stock for {$row['name']}. Available: {$row['stock']}, Requested: {$row['quantity']}";
    }
    $cartItems[] = $row;
    $subtotal += $row['price'] * $row['quantity'];
    $productNames[] = $row['name'] . ' (' . $row['quantity'] . ')';
}

// Check for Buy Now functionality
$buyNow = isset($_GET['buy_now']) && $_GET['buy_now'] == 1;
$buyNowProductId = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
$buyNowQuantity = isset($_GET['quantity']) ? (int)$_GET['quantity'] : 1;

// If Buy Now is active, add the product to cart temporarily
if ($buyNow && $buyNowProductId > 0) {
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $buyNowProductId);
    $stmt->execute();
    $buyNowProduct = $stmt->get_result()->fetch_assoc();
    
    if ($buyNowProduct) {
        if ($buyNowProduct['stock'] < $buyNowQuantity) {
            $errors[] = "Insufficient stock for {$buyNowProduct['name']}. Available: {$buyNowProduct['stock']}, Requested: {$buyNowQuantity}";
        }
        
        $tempCartItem = [
            'product_id' => $buyNowProduct['id'],
            'name' => $buyNowProduct['name'],
            'price' => $buyNowProduct['price'],
            'image' => $buyNowProduct['photo'],
            'quantity' => $buyNowQuantity,
            'stock' => $buyNowProduct['stock']
        ];
        
        $cartItems[] = $tempCartItem;
        $subtotal += $tempCartItem['price'] * $tempCartItem['quantity'];
        $productNames[] = $tempCartItem['name'] . ' (' . $tempCartItem['quantity'] . ')';
    }
}

// Check if cart is empty
if (count($cartItems) === 0) {
    header("Location: view_cart.php");
    exit();
}

// Handle coupon application
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_coupon'])) {
    $couponCode = trim($_POST['coupon_code'] ?? '');
    
    if (!empty($couponCode)) {
        // Check if coupon exists and is valid
        $stmt = $conn->prepare("SELECT * FROM coupons WHERE code = ? AND is_active = 1 AND start_date <= NOW() AND end_date >= NOW()");
        $stmt->bind_param("s", $couponCode);
        $stmt->execute();
        $couponResult = $stmt->get_result();
        
        if ($coupon = $couponResult->fetch_assoc()) {
            // Check if user has already used this coupon
            $stmt = $conn->prepare("SELECT COUNT(*) as usage_count FROM coupon_usage WHERE user_id = ? AND coupon_id = ?");
            $stmt->bind_param("ii", $userId, $coupon['id']);
            $stmt->execute();
            $usageResult = $stmt->get_result()->fetch_assoc();
            
            // Check usage limits
            $totalUsage = $coupon['used_count'];
            $userUsage = $usageResult['usage_count'];
            
            if ($coupon['usage_limit'] > 0 && $totalUsage >= $coupon['usage_limit']) {
                $errors[] = "This coupon has reached its usage limit.";
            } elseif ($coupon['user_limit'] > 0 && $userUsage >= $coupon['user_limit']) {
                $errors[] = "You have already used this coupon the maximum number of times.";
            } elseif ($subtotal < $coupon['minimum_amount']) {
                $errors[] = "Minimum order amount of $" . number_format($coupon['minimum_amount'], 2) . " required for this coupon.";
            } else {
                // Apply coupon
                $appliedCoupon = $coupon;
                if ($coupon['discount_type'] === 'percentage') {
                    $couponDiscount = ($subtotal * $coupon['discount_value']) / 100;
                    if ($coupon['max_discount'] > 0) {
                        $couponDiscount = min($couponDiscount, $coupon['max_discount']);
                    }
                } else {
                    $couponDiscount = $coupon['discount_value'];
                }
                $couponDiscount = min($couponDiscount, $subtotal); // Don't exceed subtotal
                $_SESSION['applied_coupon'] = $coupon;
                $_SESSION['coupon_discount'] = $couponDiscount;
            }
        } else {
            $errors[] = "Invalid or expired coupon code.";
        }
    }
}

// Restore applied coupon from session
if (isset($_SESSION['applied_coupon']) && isset($_SESSION['coupon_discount'])) {
    $appliedCoupon = $_SESSION['applied_coupon'];
    $couponDiscount = $_SESSION['coupon_discount'];
}

// Calculate totals
$shippingCost = $subtotal > 100 ? 0 : 10; // Free shipping over $100
$tax = ($subtotal - $couponDiscount) * 0.08; // 8% tax
$totalPrice = $subtotal - $couponDiscount + $shippingCost + $tax;

// Process checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    // Validate form data
    $name = trim($_POST['name'] ?? '');
    $number = trim($_POST['number'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $method = trim($_POST['method'] ?? 'Cash On Delivery');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $country = trim($_POST['country'] ?? '');
    $zipCode = trim($_POST['zip'] ?? '');
    
    // Basic validation
    if (empty($name)) $errors[] = "Full name is required";
    if (empty($number) || !preg_match('/^[0-9]{10}$/', $number)) $errors[] = "Valid phone number is required";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required";
    if (empty($address)) $errors[] = "Address is required";
    if (empty($city)) $errors[] = "City is required";
    if (empty($state)) $errors[] = "State is required";
    if (empty($zipCode)) $errors[] = "ZIP code is required";
    
    // Final stock check
    foreach ($cartItems as $item) {
        $stmt = $conn->prepare("SELECT stock FROM products WHERE id = ?");
        $stmt->bind_param("i", $item['product_id']);
        $stmt->execute();
        $currentStock = $stmt->get_result()->fetch_assoc()['stock'];
        
        if ($currentStock < $item['quantity']) {
            $errors[] = "Insufficient stock for {$item['name']}. Available: {$currentStock}";
        }
    }
    
    // If no errors, process the order
    if (empty($errors)) {
        $conn->begin_transaction();
        
        try {
            // Create order
            $orderDate = date('Y-m-d H:i:s');
            $status = 'pending';
            $totalProductsStr = implode(', ', $productNames);
            $trackingNumber = 'TRK' . time() . rand(1000, 9999);
            
            $stmt = $conn->prepare("INSERT INTO orders (user_id, name, number, email, method, address, city, state, country, zip_code, total_products, total_price, subtotal, discount_amount, shipping_cost, tax_amount, order_date, status, tracking_number, coupon_code) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $couponCode = $appliedCoupon ? $appliedCoupon['code'] : null;
            $stmt->bind_param("issssssssssddddsssss", $userId, $name, $number, $email, $method, $address, $city, $state, $country, $zipCode, $totalProductsStr, $totalPrice, $subtotal, $couponDiscount, $shippingCost, $tax, $orderDate, $status, $trackingNumber, $couponCode);
            $stmt->execute();
            
            $orderId = $conn->insert_id;
            
            // Add order items and update stock
            foreach ($cartItems as $item) {
                $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) 
                                      VALUES (?, ?, ?, ?)");
                $stmt->bind_param("iiid", $orderId, $item['product_id'], $item['quantity'], $item['price']);
                $stmt->execute();
                
                // Update product stock
                $stmt = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
                $stmt->bind_param("ii", $item['quantity'], $item['product_id']);
                $stmt->execute();
            }
            
            // Record coupon usage
            if ($appliedCoupon) {
                $stmt = $conn->prepare("INSERT INTO coupon_usage (user_id, coupon_id, order_id, discount_amount, used_at) VALUES (?, ?, ?, ?, NOW())");
                $stmt->bind_param("iiid", $userId, $appliedCoupon['id'], $orderId, $couponDiscount);
                $stmt->execute();
                
                // Update coupon usage count
                $stmt = $conn->prepare("UPDATE coupons SET used_count = used_count + 1 WHERE id = ?");
                $stmt->bind_param("i", $appliedCoupon['id']);
                $stmt->execute();
            }
            
            // Clear cart and coupon session
            if (!$buyNow) {
                $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
                $stmt->bind_param("i", $userId);
                $stmt->execute();
            }
            
            unset($_SESSION['applied_coupon']);
            unset($_SESSION['coupon_discount']);
            
            // Create notification
            $message = "Your order #{$orderId} has been placed successfully. Tracking: {$trackingNumber}";
            $stmt = $conn->prepare("INSERT INTO notifications (user_id, message, type, created_at) VALUES (?, ?, 'order', NOW())");
            $stmt->bind_param("is", $userId, $message);
            $stmt->execute();
            
            $conn->commit();
            
            $success = true;
            $orderComplete = true;
            $_SESSION['last_order_id'] = $orderId;
            $_SESSION['tracking_number'] = $trackingNumber;
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = "Order processing failed: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enhanced Checkout - SneakZone</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .checkout-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 30px;
        }
        
        .checkout-form {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .order-summary {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            height: fit-content;
            position: sticky;
            top: 20px;
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
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #007bff;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .coupon-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .coupon-input {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
        
        .coupon-input input {
            flex: 1;
        }
        
        .coupon-input button {
            background: #28a745;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
        }
        
        .applied-coupon {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 10px;
            border-radius: 5px;
            margin-top: 10px;
        }
        
        .order-item {
            display: flex;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }
        
        .order-item img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 5px;
            margin-right: 15px;
        }
        
        .item-details {
            flex: 1;
        }
        
        .item-name {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .item-price {
            color: #666;
        }
        
        .price-breakdown {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #eee;
        }
        
        .price-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .price-row.total {
            font-weight: bold;
            font-size: 18px;
            color: #007bff;
            border-top: 1px solid #ddd;
            padding-top: 10px;
            margin-top: 15px;
        }
        
        .discount {
            color: #28a745;
        }
        
        .place-order-btn {
            width: 100%;
            background: #007bff;
            color: white;
            border: none;
            padding: 15px;
            border-radius: 5px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 20px;
            transition: background-color 0.3s;
        }
        
        .place-order-btn:hover {
            background: #0056b3;
        }
        
        .success-message {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 20px;
        }
        
        .error-message {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .payment-methods {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
            margin-top: 10px;
        }
        
        .payment-method {
            display: flex;
            align-items: center;
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .payment-method:hover {
            border-color: #007bff;
        }
        
        .payment-method input[type="radio"] {
            margin-right: 8px;
        }
        
        @media (max-width: 768px) {
            .checkout-container {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include('include/header.php'); ?>
    
    <div class="checkout-container">
        <?php if ($orderComplete): ?>
            <div class="success-message">
                <h2>🎉 Order Placed Successfully!</h2>
                <p>Your order has been placed successfully.</p>
                <p><strong>Order ID:</strong> #<?php echo $_SESSION['last_order_id']; ?></p>
                <p><strong>Tracking Number:</strong> <?php echo $_SESSION['tracking_number']; ?></p>
                <p>You will receive an email confirmation shortly.</p>
                <a href="order_history.php" class="btn">View Order History</a>
                <a href="index.php" class="btn">Continue Shopping</a>
            </div>
        <?php else: ?>
            <div class="checkout-form">
                <h2>Checkout</h2>
                
                <?php if (!empty($errors)): ?>
                    <div class="error-message">
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="form-group">
                        <label for="name">Full Name *</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="number">Phone Number *</label>
                            <input type="tel" id="number" name="number" value="<?php echo htmlspecialchars($_POST['number'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email *</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="address">Address *</label>
                        <textarea id="address" name="address" rows="3" required><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="city">City *</label>
                            <input type="text" id="city" name="city" value="<?php echo htmlspecialchars($_POST['city'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="state">State *</label>
                            <input type="text" id="state" name="state" value="<?php echo htmlspecialchars($_POST['state'] ?? ''); ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="country">Country *</label>
                            <input type="text" id="country" name="country" value="<?php echo htmlspecialchars($_POST['country'] ?? 'USA'); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="zip">ZIP Code *</label>
                            <input type="text" id="zip" name="zip" value="<?php echo htmlspecialchars($_POST['zip'] ?? ''); ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Payment Method *</label>
                        <div class="payment-methods">
                            <label class="payment-method">
                                <input type="radio" name="method" value="Cash On Delivery" checked>
                                Cash on Delivery
                            </label>
                            <label class="payment-method">
                                <input type="radio" name="method" value="Credit Card">
                                Credit Card
                            </label>
                            <label class="payment-method">
                                <input type="radio" name="method" value="PayPal">
                                PayPal
                            </label>
                        </div>
                    </div>
                    
                    <button type="submit" name="place_order" class="place-order-btn">Place Order - $<?php echo number_format($totalPrice, 2); ?></button>
                </form>
            </div>
            
            <div class="order-summary">
                <h3>Order Summary</h3>
                
                <!-- Coupon Section -->
                <div class="coupon-section">
                    <h4>Have a Coupon?</h4>
                    <?php if ($appliedCoupon): ?>
                        <div class="applied-coupon">
                            <strong>Coupon Applied: <?php echo htmlspecialchars($appliedCoupon['code']); ?></strong><br>
                            <?php if ($appliedCoupon['discount_type'] === 'percentage'): ?>
                                <?php echo $appliedCoupon['discount_value']; ?>% off
                            <?php else: ?>
                                $<?php echo number_format($appliedCoupon['discount_value'], 2); ?> off
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <form method="POST">
                            <div class="coupon-input">
                                <input type="text" name="coupon_code" placeholder="Enter coupon code" value="<?php echo htmlspecialchars($_POST['coupon_code'] ?? ''); ?>">
                                <button type="submit" name="apply_coupon">Apply</button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
                
                <!-- Order Items -->
                <?php foreach ($cartItems as $item): ?>
                    <div class="order-item">
                        <img src="uploaded_img/<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                        <div class="item-details">
                            <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                            <div class="item-price">$<?php echo number_format($item['price'], 2); ?> × <?php echo $item['quantity']; ?></div>
                        </div>
                        <div class="item-total">$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></div>
                    </div>
                <?php endforeach; ?>
                
                <!-- Price Breakdown -->
                <div class="price-breakdown">
                    <div class="price-row">
                        <span>Subtotal:</span>
                        <span>$<?php echo number_format($subtotal, 2); ?></span>
                    </div>
                    
                    <?php if ($couponDiscount > 0): ?>
                        <div class="price-row discount">
                            <span>Discount (<?php echo htmlspecialchars($appliedCoupon['code']); ?>):</span>
                            <span>-$<?php echo number_format($couponDiscount, 2); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <div class="price-row">
                        <span>Shipping:</span>
                        <span><?php echo $shippingCost > 0 ? '$' . number_format($shippingCost, 2) : 'FREE'; ?></span>
                    </div>
                    
                    <div class="price-row">
                        <span>Tax (8%):</span>
                        <span>$<?php echo number_format($tax, 2); ?></span>
                    </div>
                    
                    <div class="price-row total">
                        <span>Total:</span>
                        <span>$<?php echo number_format($totalPrice, 2); ?></span>
                    </div>
                </div>
                
                <?php if ($subtotal < 100): ?>
                    <p style="text-align: center; color: #666; margin-top: 15px; font-size: 14px;">
                        💡 Add $<?php echo number_format(100 - $subtotal, 2); ?> more for free shipping!
                    </p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include('include/footer.php'); ?>
</body>
</html>
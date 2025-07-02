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
$errors = [];
$success = false;
$orderComplete = false;

// Fetch user information
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$userResult = $stmt->get_result();
$user = $userResult->fetch_assoc();

// Fetch cart items
$stmt = $conn->prepare("SELECT c.*, p.name, p.price, p.photo as image 
                      FROM cart c
                      JOIN products p ON c.product_id = p.id 
                      WHERE c.user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$cartItems = [];
$totalPrice = 0;
$productNames = [];

while ($row = $result->fetch_assoc()) {
    $cartItems[] = $row;
    $totalPrice += $row['price'] * $row['quantity'];
    $productNames[] = $row['name'] . ' (' . $row['quantity'] . ')';
}

// Check for Buy Now functionality
$buyNow = isset($_GET['buy_now']) && $_GET['buy_now'] == 1;
$buyNowProductId = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
$buyNowQuantity = isset($_GET['quantity']) ? (int)$_GET['quantity'] : 1;

// If Buy Now is active, add the product to cart temporarily
if ($buyNow && $buyNowProductId > 0) {
    // Get product details
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $buyNowProductId);
    $stmt->execute();
    $buyNowProduct = $stmt->get_result()->fetch_assoc();
    
    if ($buyNowProduct) {
        // Create a temporary cart item
        $tempCartItem = [
            'product_id' => $buyNowProduct['id'],
            'name' => $buyNowProduct['name'],
            'price' => $buyNowProduct['price'],
            'image' => $buyNowProduct['image'],
            'quantity' => $buyNowQuantity
        ];
        
        // Add to cart items and calculate total
        $cartItems[] = $tempCartItem;
        $totalPrice += $tempCartItem['price'] * $tempCartItem['quantity'];
        $productNames[] = $tempCartItem['name'] . ' (' . $tempCartItem['quantity'] . ')';
    }
}

// Check if cart is empty (after potential Buy Now processing)
if (count($cartItems) === 0) {
    header("Location: view_cart.php");
    exit();
}

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
    
    // If no errors, process the order
    if (empty($errors)) {
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Create order
            $orderDate = date('Y-m-d H:i:s');
            $status = 'pending';
            $totalProductsStr = implode(', ', $productNames);
            
            $stmt = $conn->prepare("INSERT INTO orders (user_id, name, number, email, method, address, city, state, country, zip_code, total_products, total_price, order_date, status) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssssssssdss", $userId, $name, $number, $email, $method, $address, $city, $state, $country, $zipCode, $totalProductsStr, $totalPrice, $orderDate, $status);
            $stmt->execute();
            
            $orderId = $conn->insert_id;
            
            // Add order items
            foreach ($cartItems as $item) {
                $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) 
                                      VALUES (?, ?, ?, ?)");
                $stmt->bind_param("iiid", $orderId, $item['product_id'], $item['quantity'], $item['price']);
                $stmt->execute();
                
                // If this is a Buy Now item from wishlist, remove it from wishlist
                if ($buyNow && $buyNowProductId == $item['product_id']) {
                    $stmt = $conn->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
                    $stmt->bind_param("ii", $userId, $item['product_id']);
                    $stmt->execute();
                }
            }
            
            // Only clear cart if this is not a Buy Now order
            if (!$buyNow) {
                $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
                $stmt->bind_param("i", $userId);
                $stmt->execute();
            }
            
            // Commit transaction
            $conn->commit();
            
            $success = true;
            $orderComplete = true;
        } catch (Exception $e) {
            // Rollback transaction on error
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
    <title>Checkout - Online Store</title>
    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .checkout-title {
            margin-bottom: 30px;
            text-align: center;
        }
        .checkout-container {
            display: flex;
            flex-wrap: wrap;
            gap: 30px;
        }
        .checkout-form {
            flex: 1;
            min-width: 300px;
        }
        .order-summary {
            flex: 0 0 300px;
            background-color: #f5f5f5;
            padding: 20px;
            border-radius: 8px;
            align-self: flex-start;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        .form-row {
            display: flex;
            gap: 15px;
        }
        .form-row .form-group {
            flex: 1;
        }
        .section-title {
            margin-top: 30px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }
        .cart-items {
            margin-bottom: 20px;
        }
        .cart-item {
            display: flex;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #ddd;
        }
        .cart-item:last-child {
            border-bottom: none;
        }
        .item-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
            margin-right: 15px;
        }
        .item-details {
            flex: 1;
        }
        .item-name {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .item-price {
            color: #666;
        }
        .item-quantity {
            color: #666;
            font-size: 0.9em;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .summary-row.total {
            font-weight: bold;
            font-size: 1.2em;
            border-top: 1px solid #ddd;
            padding-top: 10px;
            margin-top: 10px;
        }
        .place-order-btn {
            display: block;
            width: 100%;
            padding: 12px;
            background-color: #04AA6D;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            margin-top: 20px;
        }
        .place-order-btn:hover {
            background-color: #45a049;
        }
        .error-message {
            color: #f44336;
            margin-bottom: 20px;
            padding: 10px;
            background-color: #ffebee;
            border-radius: 4px;
        }
        .success-message {
            color: #4CAF50;
            margin-bottom: 20px;
            padding: 10px;
            background-color: #e8f5e9;
            border-radius: 4px;
            text-align: center;
        }
        .continue-shopping {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background-color: #04AA6D;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }
        .payment-methods {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }
        .payment-method {
            flex: 1;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .payment-method:hover {
            border-color: #04AA6D;
        }
        .payment-method.active {
            border-color: #04AA6D;
            background-color: #e8f5e9;
        }
        .payment-method i {
            font-size: 24px;
            margin-bottom: 10px;
            color: #04AA6D;
        }
        .order-complete {
            text-align: center;
            padding: 40px 20px;
            background-color: #e8f5e9;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .order-complete i {
            font-size: 64px;
            color: #04AA6D;
            margin-bottom: 20px;
        }
        .order-complete h2 {
            margin-bottom: 20px;
            color: #04AA6D;
        }
        .order-details {
            margin-top: 30px;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 8px;
        }
        .order-details h3 {
            margin-bottom: 15px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
        }
        .detail-row {
            display: flex;
            margin-bottom: 10px;
        }
        .detail-row strong {
            width: 150px;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/include/header.php'; ?>

    <div class="container">
        <h1 class="checkout-title">Checkout</h1>
        
        <?php if ($orderComplete): ?>
            <div class="order-complete">
                <i class="fas fa-check-circle"></i>
                <h2>Thank you for your order!</h2>
                <p>Your order has been placed successfully. We'll process it as soon as possible.</p>
                <a href="product_list.php" class="continue-shopping">Continue Shopping</a>
            </div>
        <?php else: ?>
            <?php if (!empty($errors)): ?>
                <div class="error-message">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <div class="checkout-container">
                <div class="checkout-form">
                    <form method="POST" action="checkout.php" id="checkout-form">
                        <h2 class="section-title">Billing Information</h2>
                        
                        <div class="form-group">
                            <label for="name">Full Name</label>
                            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['fullname'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="number">Phone Number</label>
                                <input type="text" id="number" name="number" placeholder="10-digit phone number" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="address">Address</label>
                            <input type="text" id="address" name="address" required>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="city">City</label>
                                <input type="text" id="city" name="city" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="state">State</label>
                                <input type="text" id="state" name="state" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="country">Country</label>
                                <input type="text" id="country" name="country" value="United States" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="zip">ZIP Code</label>
                                <input type="text" id="zip" name="zip" required>
                            </div>
                        </div>
                        
                        <h2 class="section-title">Payment Method</h2>
                        
                        <div class="payment-methods">
                            <div class="payment-method active" data-method="Cash On Delivery">
                                <i class="fas fa-money-bill-wave"></i>
                                <div>Cash On Delivery</div>
                            </div>
                            <div class="payment-method" data-method="Credit Card">
                                <i class="fas fa-credit-card"></i>
                                <div>Credit Card</div>
                            </div>
                            <div class="payment-method" data-method="PayPal">
                                <i class="fab fa-paypal"></i>
                                <div>PayPal</div>
                            </div>
                        </div>
                        <input type="hidden" name="method" id="payment_method" value="Cash On Delivery">
                        
                        <button type="submit" name="place_order" class="place-order-btn">Place Order</button>
                    </form>
                </div>
                
                <div class="order-summary">
                    <h2>Order Summary</h2>
                    
                    <div class="cart-items">
                        <?php foreach ($cartItems as $item): ?>
                            <div class="cart-item">
                                <?php $imagePath = !empty($item['image']) ? $item['image'] : 'img/air.png'; ?>
                                <img src="<?php echo htmlspecialchars($imagePath); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="item-image">
                                
                                <div class="item-details">
                                    <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                    <div class="item-price">$<?php echo number_format($item['price'], 2); ?></div>
                                    <div class="item-quantity">Qty: <?php echo $item['quantity']; ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="summary-row">
                        <span>Subtotal:</span>
                        <span>$<?php echo number_format($totalPrice, 2); ?></span>
                    </div>
                    
                    <div class="summary-row">
                        <span>Shipping:</span>
                        <span>$0.00</span>
                    </div>
                    
                    <div class="summary-row total">
                        <span>Total:</span>
                        <span>$<?php echo number_format($totalPrice, 2); ?></span>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php include __DIR__ . '/include/footer.php'; ?>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Payment method selection
            const paymentMethods = document.querySelectorAll('.payment-method');
            const paymentMethodInput = document.getElementById('payment_method');
            
            paymentMethods.forEach(method => {
                method.addEventListener('click', function() {
                    // Remove active class from all methods
                    paymentMethods.forEach(m => m.classList.remove('active'));
                    
                    // Add active class to clicked method
                    this.classList.add('active');
                    
                    // Update hidden input value
                    paymentMethodInput.value = this.getAttribute('data-method');
                });
            });
            
            // Phone number validation
            const phoneInput = document.getElementById('number');
            if (phoneInput) {
                phoneInput.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, '');
                    if (value.length > 10) {
                        value = value.substring(0, 10);
                    }
                    e.target.value = value;
                });
            }
            
            // Basic form validation
            const checkoutForm = document.getElementById('checkout-form');
            if (checkoutForm) {
                checkoutForm.addEventListener('submit', function(e) {
                    let valid = true;
                    const requiredFields = checkoutForm.querySelectorAll('[required]');
                    
                    requiredFields.forEach(function(field) {
                        if (!field.value.trim()) {
                            valid = false;
                            field.style.borderColor = '#f44336';
                        } else {
                            field.style.borderColor = '#ddd';
                        }
                    });
                    
                    // Validate email format
                    const emailField = document.getElementById('email');
                    if (emailField && emailField.value.trim()) {
                        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                        if (!emailPattern.test(emailField.value.trim())) {
                            valid = false;
                            emailField.style.borderColor = '#f44336';
                        }
                    }
                    
                    // Validate phone number
                    const phoneField = document.getElementById('number');
                    if (phoneField && phoneField.value.trim()) {
                        if (phoneField.value.trim().length !== 10) {
                            valid = false;
                            phoneField.style.borderColor = '#f44336';
                        }
                    }
                    
                    if (!valid) {
                        e.preventDefault();
                        alert('Please fill in all required fields correctly.');
                    }
                });
            }
        });
    </script>
</body>
</html>
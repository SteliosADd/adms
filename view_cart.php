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

// Process any form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : null;
    
    if ($action === 'update') {
        $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
        $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
        
        if ($productId > 0 && $quantity > 0) {
            $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
            $stmt->bind_param("iii", $quantity, $userId, $productId);
            if ($stmt->execute()) {
                $message = "Cart updated successfully";
            } else {
                $message = "Error updating cart: " . $conn->error;
            }
        }
    } elseif ($action === 'remove') {
        $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
        
        if ($productId > 0) {
            $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
            $stmt->bind_param("ii", $userId, $productId);
            if ($stmt->execute()) {
                $message = "Product removed from cart";
            } else {
                $message = "Error removing product: " . $conn->error;
            }
        }
    } elseif ($action === 'checkout') {
        // Redirect to checkout page
        header("Location: checkout.php");
        exit();
    }
}

// Fetch cart items
$stmt = $conn->prepare("SELECT c.*, p.name, p.price, p.photo as image, p.description 
                      FROM cart c
                      JOIN products p ON c.product_id = p.id 
                      WHERE c.user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$cartItems = [];
$totalPrice = 0;

while ($row = $result->fetch_assoc()) {
    $cartItems[] = $row;
    $totalPrice += $row['price'] * $row['quantity'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - Online Store</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f8f9fa;
            min-height: 100vh;
        }
        .cart-title {
            margin-bottom: 40px;
            text-align: center;
            color: #2c3e50;
            font-size: 2.5rem;
            font-weight: 700;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .cart-items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
        }
        .cart-items th {
            background: linear-gradient(135deg, #495057 0%, #6c757d 100%);
            color: white;
            padding: 16px;
            text-align: left;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 14px;
        }
        .cart-items td {
            padding: 16px;
            border-bottom: 1px solid #e9ecef;
            vertical-align: middle;
            background-color: white;
        }
        .cart-items tr:hover td {
            background-color: #f8f9fa;
        }
        .product-image {
            width: 90px;
            height: 90px;
            object-fit: cover;
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .product-image:hover {
            transform: scale(1.05);
        }
        .product-name {
            font-weight: 700;
            color: #2c3e50;
            font-size: 16px;
        }
        .quantity-input {
            width: 70px;
            padding: 8px 12px;
            text-align: center;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-weight: 600;
            color: #495057;
            background-color: #f8f9fa;
            transition: all 0.3s ease;
        }
        .quantity-input:focus {
            outline: none;
            border-color: #007bff;
            background-color: white;
            box-shadow: 0 0 0 3px rgba(0,123,255,0.1);
        }
        .update-btn, .remove-btn {
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            margin-left: 8px;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .update-btn {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
        }
        .update-btn:hover {
            background: linear-gradient(135deg, #218838 0%, #1ea085 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(40, 167, 69, 0.4);
        }
        .remove-btn {
            background: white;
            color: #dc3545;
            border: 2px solid #dc3545;
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.1);
        }
        .remove-btn:hover {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(220, 53, 69, 0.3);
        }
        .cart-summary {
            background: white;
            padding: 30px;
            border-radius: 16px;
            margin-bottom: 30px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
            border: 1px solid #e9ecef;
            position: relative;
        }
        .cart-summary::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #28a745, #20c997, #17a2b8);
            border-radius: 16px 16px 0 0;
        }
        .cart-summary h2 {
            margin-top: 0;
            margin-bottom: 25px;
            color: #2c3e50;
            font-weight: 700;
            font-size: 1.8rem;
            text-align: center;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            padding: 8px 0;
            font-size: 16px;
        }
        .summary-row.total {
            font-weight: 800;
            font-size: 1.4em;
            color: #28a745;
            border-top: 2px solid #e9ecef;
            padding-top: 20px;
            margin-top: 20px;
            text-shadow: 0 1px 2px rgba(40,167,69,0.2);
        }
        .checkout-btn {
            display: block;
            width: 100%;
            padding: 16px 24px;
            background: linear-gradient(135deg, #007bff 0%, #6610f2 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 18px;
            font-weight: 700;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            margin-top: 25px;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 6px 20px rgba(0, 123, 255, 0.3);
        }
        .checkout-btn:hover {
            background: linear-gradient(135deg, #0056b3 0%, #520dc2 100%);
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 123, 255, 0.4);
        }
        .checkout-btn:active {
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(0, 123, 255, 0.3);
        }
        .empty-cart {
            text-align: center;
            padding: 80px 40px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
            margin: 40px 0;
        }
        .empty-cart i {
            font-size: 80px;
            color: #dee2e6;
            margin-bottom: 30px;
            display: block;
        }
        .empty-cart h3 {
            color: #6c757d;
            font-size: 1.5rem;
            margin-bottom: 15px;
            font-weight: 600;
        }
        .empty-cart p {
            color: #adb5bd;
            margin-bottom: 30px;
            font-size: 16px;
        }
        .empty-cart a {
            display: inline-block;
            margin-top: 20px;
            padding: 14px 28px;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        }
        .empty-cart a:hover {
            background: linear-gradient(135deg, #218838 0%, #1ea085 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
        }
        #message {
            padding: 16px 24px;
            margin: 25px 0;
            border-radius: 12px;
            text-align: center;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            animation: slideIn 0.3s ease-out;
        }
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .success {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
            border: 2px solid #c3e6cb;
        }
        .error {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
            border: 2px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/include/header.php'; ?>

    <div class="container">
        <h1 class="cart-title">Shopping Cart</h1>
        
        <?php if(!empty($message)): ?>
            <div id="message" class="<?php echo strpos($message, 'Error') !== false ? 'error' : 'success'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <?php if(count($cartItems) > 0): ?>
            <table class="cart-items">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Name</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Total</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($cartItems as $item): ?>
                        <tr>
                            <td>
                                <?php $imagePath = !empty($item['image']) ? $item['image'] : 'img/air.png'; ?>
                                <img src="<?php echo htmlspecialchars($imagePath); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="product-image">
                            </td>
                            <td class="product-name"><?php echo htmlspecialchars($item['name']); ?></td>
                            <td>$<?php echo number_format($item['price'], 2); ?></td>
                            <td>
                                <form method="POST" action="view_cart.php" class="update-form">
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                    <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" min="1" max="10" class="quantity-input">
                                    <button type="submit" class="update-btn"><i class="fas fa-sync-alt"></i> Update</button>
                                </form>
                            </td>
                            <td>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                            <td>
                                <form method="POST" action="view_cart.php" class="remove-form" onsubmit="return confirmRemove()">
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                    <button type="submit" class="remove-btn"><i class="fas fa-trash-alt"></i> Remove</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="cart-summary">
                <h2>Order Summary</h2>
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
                
                <form method="POST" action="view_cart.php">
                    <input type="hidden" name="action" value="checkout">
                    <button type="submit" class="checkout-btn">Proceed to Checkout</button>
                </form>
            </div>
        <?php else: ?>
            <div class="empty-cart">
                <i class="fas fa-shopping-cart"></i>
                <h3>Your cart is empty</h3>
                <p>Add some products to your cart to get started with your shopping.</p>
                <a href="product_list.php"><i class="fas fa-shopping-bag"></i> Continue Shopping</a>
            </div>
        <?php endif; ?>
    </div>

    <?php include __DIR__ . '/include/footer.php'; ?>
    
    <script>
        // Auto-hide message after 3 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const message = document.getElementById('message');
            if (message) {
                setTimeout(function() {
                    message.style.display = 'none';
                }, 3000);
            }
        });
        
        // Enhanced remove confirmation
        function confirmRemove() {
            return confirm('Are you sure you want to remove this item from your cart?');
        }
        
        // Add loading state to buttons
        document.querySelectorAll('.remove-btn, .update-btn').forEach(button => {
            button.addEventListener('click', function() {
                this.disabled = true;
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                setTimeout(() => {
                    this.form.submit();
                }, 100);
            });
        });
    </script>
</body>
</html>
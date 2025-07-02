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

// Fetch wishlist items
$stmt = $conn->prepare("SELECT w.*, p.name, p.price, p.photo as image, p.description, p.type as category, u.fullname as seller_name 
                      FROM wishlist w
                      JOIN products p ON w.product_id = p.id 
                      LEFT JOIN users u ON p.seller_id = u.id
                      WHERE w.user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$wishlistItems = [];
while ($row = $result->fetch_assoc()) {
    $wishlistItems[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Wishlist - Online Store</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .wishlist-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f8f9fa;
            min-height: 100vh;
        }
        .wishlist-title {
            text-align: center;
            margin-bottom: 40px;
            color: #2c3e50;
            font-size: 2.5rem;
            font-weight: 700;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .wishlist-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }
        .wishlist-item {
            background: white;
            border-radius: 16px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid #e9ecef;
            position: relative;
        }
        .wishlist-item:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
        }
        .wishlist-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #dc3545, #fd7e14, #ffc107);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .wishlist-item:hover::before {
            opacity: 1;
        }
        .wishlist-item img {
            width: 100%;
            height: 220px;
            object-fit: cover;
            transition: transform 0.4s ease;
        }
        .wishlist-item:hover img {
            transform: scale(1.05);
        }
        .wishlist-item .content {
            padding: 25px;
        }
        .wishlist-item .name {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 12px;
            color: #2c3e50;
            line-height: 1.3;
        }
        .wishlist-item .description {
            color: #6c757d;
            margin-bottom: 15px;
            font-size: 14px;
            line-height: 1.5;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .wishlist-item .price {
            font-size: 24px;
            font-weight: 800;
            color: #28a745;
            margin-bottom: 12px;
            text-shadow: 0 1px 2px rgba(40,167,69,0.2);
        }
        .wishlist-item .seller {
            font-size: 14px;
            color: #6c757d;
            margin-bottom: 20px;
            font-weight: 500;
        }
        .wishlist-item .category-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            background-color: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
        }
        .wishlist-item .seller {
            font-size: 14px;
            color: #666;
            margin-bottom: 15px;
        }
        .wishlist-actions {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-top: 20px;
        }
        .wishlist-actions button {
            padding: 12px 20px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            justify-content: center;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            position: relative;
            overflow: hidden;
        }
        .wishlist-actions button i {
            margin-right: 8px;
            font-size: 16px;
            transition: transform 0.3s ease;
        }
        .wishlist-actions button:hover i {
            transform: scale(1.1);
        }
        .add-to-cart-btn {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
            border: 2px solid transparent;
        }
        .add-to-cart-btn:hover {
            background: linear-gradient(135deg, #218838 0%, #1ea085 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
        }
        .add-to-cart-btn:active {
            transform: translateY(0);
            box-shadow: 0 2px 10px rgba(40, 167, 69, 0.3);
        }
        .buy-now-btn {
            background: linear-gradient(135deg, #007bff 0%, #6610f2 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(0, 123, 255, 0.3);
            border: 2px solid transparent;
        }
        .buy-now-btn:hover {
            background: linear-gradient(135deg, #0056b3 0%, #520dc2 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 123, 255, 0.4);
        }
        .buy-now-btn:active {
            transform: translateY(0);
            box-shadow: 0 2px 10px rgba(0, 123, 255, 0.3);
        }
        .remove-btn {
            background: white;
            color: #dc3545;
            border: 2px solid #dc3545;
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.1);
        }
        .remove-btn:hover {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(220, 53, 69, 0.3);
        }
        .remove-btn:active {
            transform: translateY(0);
            box-shadow: 0 2px 10px rgba(220, 53, 69, 0.2);
        }
        .remove-btn:hover i {
            transform: scale(1.1) rotate(10deg);
        }
        .empty-wishlist {
            text-align: center;
            padding: 80px 40px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
            margin: 40px 0;
        }
        .empty-wishlist i {
            font-size: 80px;
            color: #dee2e6;
            margin-bottom: 30px;
            display: block;
        }
        .empty-wishlist h3 {
            color: #6c757d;
            font-size: 1.5rem;
            margin-bottom: 15px;
            font-weight: 600;
        }
        .empty-wishlist p {
            color: #adb5bd;
            margin-bottom: 30px;
            font-size: 16px;
        }
        .empty-wishlist a {
            display: inline-block;
            margin-top: 20px;
            padding: 14px 28px;
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
        }
        .empty-wishlist a:hover {
            background: linear-gradient(135deg, #b02a37 0%, #a71e2a 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(220, 53, 69, 0.4);
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
        .quantity-container {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        .quantity-label {
            margin-right: 10px;
            font-weight: bold;
        }
        .quantity-input {
            width: 60px;
            padding: 5px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-align: center;
        }
        @keyframes fadeOut {
            0% { opacity: 1; }
            70% { opacity: 1; }
            100% { opacity: 0; }
        }
        @media (max-width: 768px) {
            .wishlist-items {
                grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            }
        }
        @media (max-width: 480px) {
            .wishlist-items {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/include/header.php'; ?>

    <div class="wishlist-container">
        <h1 class="wishlist-title">My Wishlist</h1>
        
        <?php if(!empty($message)): ?>
            <div id="message" class="<?php echo strpos($message, 'Error') !== false ? 'error' : 'success'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <?php if(count($wishlistItems) > 0): ?>
            <div class="wishlist-grid">
                <?php foreach($wishlistItems as $item): ?>
                    <div class="wishlist-item" data-id="<?php echo $item['product_id']; ?>">
                        <?php $imagePath = !empty($item['image']) ? $item['image'] : 'img/air.png'; ?>
                        <img src="<?php echo htmlspecialchars($imagePath); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                        <div class="content">
                            <div class="category-badge"><?php echo htmlspecialchars($item['category']); ?></div>
                            <div class="name"><?php echo htmlspecialchars($item['name']); ?></div>
                            <div class="price">$<?php echo number_format($item['price'], 2); ?></div>
                            <div class="seller">Seller: <?php echo htmlspecialchars($item['seller_name'] ?? 'Unknown'); ?></div>
                            <div class="description">
                                <?php echo htmlspecialchars(substr($item['description'], 0, 100)) . (strlen($item['description']) > 100 ? '...' : ''); ?>
                            </div>
                        
                        <div class="wishlist-actions">
                            <form action="cart_action.php" method="POST" class="cart-form">
                                <input type="hidden" name="action" value="add">
                                <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                <div class="quantity-container">
                                    <label for="quantity-<?php echo $item['product_id']; ?>" class="quantity-label">Qty:</label>
                                    <input type="number" id="quantity-<?php echo $item['product_id']; ?>" name="quantity" value="1" min="1" max="10" class="quantity-input">
                                </div>
                                <button type="submit" class="add-to-cart-btn"><i class="fas fa-shopping-cart"></i> Add to Cart</button>
                            </form>
                            
                            <form action="checkout.php" method="GET" class="buy-now-form">
                                <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                <input type="hidden" name="quantity" value="1">
                                <input type="hidden" name="buy_now" value="1">
                                <button type="submit" class="buy-now-btn"><i class="fas fa-bolt"></i> Buy Now</button>
                            </form>
                            
                            <form action="add_wishlist.php" method="POST" class="remove-form">
                                <input type="hidden" name="action" value="remove">
                                <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                <button type="submit" class="remove-btn"><i class="fas fa-trash"></i> Remove</button>
                            </form>
                        </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-wishlist">
                <i class="fas fa-heart-broken"></i>
                <h3>Your wishlist is empty</h3>
                <p>Add items to your wishlist to keep track of products you're interested in.</p>
                <a href="product_list.php"><i class="fas fa-shopping-bag"></i> Browse Products</a>
            </div>
        <?php endif; ?>
    </div>

    <?php include __DIR__ . '/include/footer.php'; ?>

    <script>
        // Auto-hide message after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const message = document.getElementById('message');
            if (message) {
                setTimeout(function() {
                    message.style.display = 'none';
                }, 5000);
            }
            
            // Update quantity in Buy Now form when quantity input changes
            const quantityInputs = document.querySelectorAll('.quantity-input');
            quantityInputs.forEach(input => {
                input.addEventListener('change', function() {
                    const productId = this.id.split('-')[1];
                    const buyNowForm = document.querySelector(`.wishlist-item[data-id="${productId}"] .buy-now-form input[name="quantity"]`);
                    if (buyNowForm) {
                        buyNowForm.value = this.value;
                    }
                });
            });
        });
    </script>
</body>
</html>
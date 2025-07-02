<?php
// File: seller_dashboard.php
include('include/connect.php');
session_start();

// Check if the user is logged in and is a seller
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'seller') {
    header("Location: login.php?error=Unauthorized");
    exit();
}

// Fetch seller-specific products
$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM products WHERE seller_id = ? ORDER BY name";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$products = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; }
        .dashboard { max-width: 1200px; margin: auto; padding: 20px; }
        .dashboard-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .dashboard-actions { display: flex; gap: 15px; }
        .dashboard-actions a { 
            display: inline-block; 
            padding: 10px 15px; 
            background-color: #4CAF50; 
            color: white; 
            text-decoration: none; 
            border-radius: 4px; 
        }
        .dashboard-actions a:hover { background-color: #45a049; }
        .dashboard-actions a.orders { background-color: #2196F3; }
        .dashboard-actions a.orders:hover { background-color: #0b7dda; }
        .products-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); 
            gap: 20px; 
        }
        .product { 
            border: 1px solid #ddd; 
            padding: 15px; 
            border-radius: 5px; 
            box-shadow: 0 2px 5px rgba(0,0,0,0.1); 
            transition: transform 0.3s ease; 
        }
        .product:hover { transform: translateY(-5px); }
        .product h3 { margin-top: 0; color: #333; }
        .product-price { font-weight: bold; color: #4CAF50; }
        .actions { margin-top: 15px; display: flex; gap: 10px; }
        .actions button { 
            padding: 8px 12px; 
            border: none; 
            border-radius: 4px; 
            cursor: pointer; 
        }
        .actions button[type="submit"] { background-color: #2196F3; color: white; }
        .actions button[type="submit"]:hover { background-color: #0b7dda; }
        .actions form:last-child button { background-color: #f44336; color: white; }
        .actions form:last-child button:hover { background-color: #d32f2f; }
        .empty-message { text-align: center; padding: 50px; color: #666; }
    </style>
</head>
<body>
    <?php include 'include/header.php'; ?>
    
    <div class="dashboard">
        <div class="dashboard-header">
            <h1>Seller Dashboard</h1>
            <div class="dashboard-actions">
                <a href="add_product.php">Add New Product</a>
                <a href="seller_orders.php" class="orders">Manage Orders</a>
            </div>
        </div>
        
        <h2>Your Products</h2>
        <?php if (count($products) > 0): ?>
            <div class="products-grid">
                <?php foreach ($products as $product): ?>
                    <div class="product">
                        <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                        <p><?php echo htmlspecialchars($product['description']); ?></p>
                        <p class="product-price">$<?php echo number_format($product['price'], 2); ?></p>
                        <?php if (!empty($product['image'])): ?>
                            <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" style="max-width: 100%; height: 150px; object-fit: cover; border-radius: 4px;">
                        <?php endif; ?>
                        <div class="actions">
                            <form method="POST" action="edit_product.php">
                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                <button type="submit">Edit Product</button>
                            </form>
                            <form method="POST" action="delete_product.php">
                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                <button type="submit" onclick="return confirm('Are you sure you want to delete this product?');">Delete</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-message">
                <p>You haven't added any products yet.</p>
                <a href="add_product.php" class="dashboard-actions">Add Your First Product</a>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include 'include/footer.php'; ?>
</body>
</html>

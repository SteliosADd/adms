<?php
// File: customer_dashboard.php
include('include/connect.php');
session_start();

// Check if the user is logged in and is a customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: login.php?error=Unauthorized");
    exit();
}

// Fetch product categories and products with seller information
$query = "
    SELECT 
        p.id AS product_id, 
        p.name AS product_name, 
        p.description, 
        p.price, 
        p.type as category, 
        u.fullname AS seller_name
    FROM 
        products p
    JOIN 
        users u ON p.seller_id = u.id
    ORDER BY 
        p.type, p.name
";
$result = $conn->query($query);

if (!$result) {
    die("Query failed: " . $conn->error);
}

$products_by_category = [];
while ($row = $result->fetch_assoc()) {
    $products_by_category[$row['category']][] = $row;
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; }
        .category { margin-top: 20px; }
        .product { margin-bottom: 10px; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        .product h3 { margin: 0; }
        .product p { margin: 5px 0; }
        .add-to-cart { margin-top: 10px; }
    </style>
</head>
<body>
    <h1>Welcome to the Customer Dashboard</h1>

    <?php foreach ($products_by_category as $category => $products): ?>
        <div class="category">
            <h2>Category: <?php echo htmlspecialchars($category); ?></h2>
            <?php foreach ($products as $product): ?>
                <div class="product">
                    <h3><?php echo htmlspecialchars($product['product_name']); ?></h3>
                    <p><?php echo htmlspecialchars($product['description']); ?></p>
                    <p>Price: $<?php echo number_format($product['price'], 2); ?></p>
                    <p>Seller: <?php echo htmlspecialchars($product['seller_name']); ?></p>
                    <form method="POST" action="add_to_cart.php" class="add-to-cart">
                        <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                        <label for="quantity">Quantity:</label>
                        <input type="number" name="quantity" id="quantity" min="1" value="1" required>
                        <button type="submit">Add to Cart</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
</body>
</html>

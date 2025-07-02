<?php
// File: delete_product.php
include('include/connect.php');
session_start();

// Check if the user is logged in and is a seller
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'seller') {
    header("Location: login.php?error=Unauthorized");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['product_id'])) {
    $product_id = intval($_POST['product_id']);
    $seller_id = $_SESSION['user_id'];
    
    // First, get the product details to check ownership and get image path
    $stmt = $conn->prepare("SELECT image FROM products WHERE id = ? AND seller_id = ?");
    $stmt->bind_param("ii", $product_id, $seller_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    $stmt->close();
    
    if (!$product) {
        header("Location: seller_dashboard.php?error=Product not found or unauthorized");
        exit();
    }
    
    // Delete the product from database
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ? AND seller_id = ?");
    $stmt->bind_param("ii", $product_id, $seller_id);
    
    if ($stmt->execute()) {
        // Delete the image file if it's not the default image
        if ($product['image'] !== 'img/air.png' && !empty($product['image']) && file_exists($product['image'])) {
            unlink($product['image']);
        }
        
        header("Location: seller_dashboard.php?success=Product deleted successfully");
    } else {
        header("Location: seller_dashboard.php?error=Error deleting product");
    }
    
    $stmt->close();
} else {
    header("Location: seller_dashboard.php?error=Invalid request");
}
$conn->close();
?>

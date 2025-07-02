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
$redirect = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : null;

    switch ($action) {
        case 'add':
            $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
            
            if ($productId <= 0) {
                $message = "Invalid product";
                break;
            }
            
            // Check if the product is already in the wishlist
            $stmt = $conn->prepare("SELECT * FROM wishlist WHERE user_id = ? AND product_id = ?");
            $stmt->bind_param("ii", $userId, $productId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $message = "Product already in your wishlist";
            } else {
                // Insert new product to wishlist
                $stmt = $conn->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)");
                $stmt->bind_param("ii", $userId, $productId);
                if ($stmt->execute()) {
                    $message = "Product added to wishlist successfully";
                    $redirect = "product_list.php";
                } else {
                    $message = "Error adding to wishlist: " . $conn->error;
                }
            }
            break;

        case 'remove':
            $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
            
            if ($productId <= 0) {
                $message = "Invalid product";
                break;
            }
            
            $stmt = $conn->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
            $stmt->bind_param("ii", $userId, $productId);
            if ($stmt->execute()) {
                $message = "Product removed from wishlist";
                $redirect = "view_wishlist.php";
            } else {
                $message = "Error removing from wishlist: " . $conn->error;
            }
            break;

        case 'get':
            $stmt = $conn->prepare("SELECT w.*, p.name, p.price, p.photo as image, p.description 
                                  FROM wishlist w
                                  JOIN products p ON w.product_id = p.id 
                                  WHERE w.user_id = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $wishlistItems = [];
            while ($row = $result->fetch_assoc()) {
                $wishlistItems[] = $row;
            }
            echo json_encode($wishlistItems);
            exit(); // Exit early for JSON response
// Remove break statement since it's unreachable after exit()
            
        case 'move_to_cart':
            $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
            
            if ($productId <= 0) {
                $message = "Invalid product";
                break;
            }
            
            // First add to cart
            $stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, 1) 
                                  ON DUPLICATE KEY UPDATE quantity = quantity + 1");
            $stmt->bind_param("ii", $userId, $productId);
            
            if ($stmt->execute()) {
                // Then remove from wishlist
                $stmt = $conn->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
                $stmt->bind_param("ii", $userId, $productId);
                $stmt->execute();
                
                $message = "Product moved to cart successfully";
                $redirect = "view_cart.php";
            } else {
                $message = "Error moving product to cart: " . $conn->error;
            }
            break;
    }
    
    // Redirect to the specified page or to the referring page or to a default page
    $redirect_to = isset($_POST['redirect']) ? $_POST['redirect'] : (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'view_wishlist.php');
    header('Location: ' . $redirect_to);
    exit;
}
?>
<?php
// Redirect to the view_wishlist.php page
header("Location: view_wishlist.php");
exit();
?>
<?php
// This file is for backend processing only
// The HTML/frontend code was unreachable due to previous exit() statements
// Removed unreachable HTML/JS code
?>

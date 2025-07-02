<?php
// File: edit_product.php
include('include/connect.php');
session_start();

// Check if user is logged in and is a seller
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'seller') {
    header("Location: login.php?error=Unauthorized");
    exit();
}

$product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : (isset($_GET['product_id']) ? intval($_GET['product_id']) : 0);
$seller_id = $_SESSION['user_id'];

// Verify that the product belongs to the current seller
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ? AND seller_id = ?");
$stmt->bind_param("ii", $product_id, $seller_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();
$stmt->close();

if (!$product) {
    header("Location: seller_dashboard.php?error=Product not found or unauthorized");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_product'])) {
    // Get form data
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $category = trim($_POST['category']);
    
    // Handle image upload
    $image_path = $product['image']; // Keep existing image by default
    
    if(isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $upload_dir = 'uploads/';
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_name = time() . '_' . basename($_FILES['image']['name']);
        $target_path = $upload_dir . $file_name;
        
        if(move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
            // Delete old image if it's not the default
            if ($product['image'] !== 'img/air.png' && file_exists($product['image'])) {
                unlink($product['image']);
            }
            $image_path = $target_path;
        }
    }

    // Update product in database
    $stmt = $conn->prepare("UPDATE products SET name = ?, description = ?, price = ?, type = ?, photo = ? WHERE id = ? AND seller_id = ?");
    $stmt->bind_param("ssdsiii", $name, $description, $price, $category, $image_path, $product_id, $seller_id);
    
    if ($stmt->execute()) {
        header("Location: seller_dashboard.php?success=Product updated successfully");
        exit();
    } else {
        $error = "Error updating product: " . $conn->error;
    }
    
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="style3.css">
    <style>
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"],
        input[type="number"],
        input[type="file"],
        select,
        textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        textarea {
            height: 100px;
            resize: vertical;
        }
        .current-image {
            max-width: 200px;
            height: 150px;
            object-fit: cover;
            border-radius: 4px;
            margin: 10px 0;
        }
        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        button {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }
        .btn-primary {
            background-color: #4CAF50;
            color: white;
        }
        .btn-primary:hover {
            background-color: #45a049;
        }
        .btn-secondary {
            background-color: #6c757d;
            color: white;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        .error {
            color: #d32f2f;
            margin-bottom: 15px;
            padding: 10px;
            background-color: #ffebee;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <?php include('include/header.php'); ?>
    
    <div class="container">
        <h1>Edit Product</h1>
        
        <?php if (isset($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
            
            <div class="form-group">
                <label for="name">Product Name:</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="description">Description:</label>
                <textarea id="description" name="description" required><?php echo htmlspecialchars($product['description']); ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="price">Price ($):</label>
                <input type="number" id="price" name="price" step="0.01" min="0" value="<?php echo $product['price']; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="category">Category:</label>
                <select id="category" name="category" required>
                    <option value="">Select Category</option>
                    <option value="Footwear" <?php echo $product['category'] == 'Footwear' ? 'selected' : ''; ?>>Footwear</option>
                    <option value="Electronics" <?php echo $product['category'] == 'Electronics' ? 'selected' : ''; ?>>Electronics</option>
                    <option value="Accessories" <?php echo $product['category'] == 'Accessories' ? 'selected' : ''; ?>>Accessories</option>
                    <option value="Home & Kitchen" <?php echo $product['category'] == 'Home & Kitchen' ? 'selected' : ''; ?>>Home & Kitchen</option>
                    <option value="Sports & Outdoors" <?php echo $product['category'] == 'Sports & Outdoors' ? 'selected' : ''; ?>>Sports & Outdoors</option>
                    <option value="Clothing" <?php echo $product['category'] == 'Clothing' ? 'selected' : ''; ?>>Clothing</option>
                    <option value="Books" <?php echo $product['category'] == 'Books' ? 'selected' : ''; ?>>Books</option>
                    <option value="Other" <?php echo $product['category'] == 'Other' ? 'selected' : ''; ?>>Other</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="image">Current Image:</label>
                <?php if (!empty($product['image'])): ?>
                    <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="Current product image" class="current-image">
                <?php endif; ?>
                <label for="image">Upload New Image (optional):</label>
                <input type="file" id="image" name="image" accept="image/*">
                <small>Leave empty to keep current image</small>
            </div>
            
            <div class="form-actions">
                <button type="submit" name="update_product" class="btn-primary">Update Product</button>
                <a href="seller_dashboard.php" class="btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
    
    <?php include('include/footer.php'); ?>
</body>
</html>


<script>
// Add to Cart functionality
function addToCart(productId, quantity = 1) {
    const formData = new FormData();
    formData.append('action', 'add');
    formData.append('product_id', productId);
    formData.append('quantity', quantity);
    
    fetch('cart_action.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(data => {
         // Show success message
         showMessage('Product added to cart successfully!', 'success');
         if (typeof updateHeaderCounts === 'function') {
             updateHeaderCounts();
         }
     })
    .catch(error => {
        console.error('Error:', error);
        showMessage('Error adding product to cart', 'error');
    });
}

// Add to Wishlist functionality
function addToWishlist(productId) {
    const formData = new FormData();
    formData.append('action', 'add');
    formData.append('product_id', productId);
    
    fetch('add_wishlist.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(data => {
         // Show success message
         showMessage('Product added to wishlist successfully!', 'success');
         if (typeof updateHeaderCounts === 'function') {
             updateHeaderCounts();
         }
     })
    .catch(error => {
        console.error('Error:', error);
        showMessage('Error adding product to wishlist', 'error');
    });
}



// Show message to user
function showMessage(message, type = 'info') {
    // Remove existing messages
    const existingMessages = document.querySelectorAll('.message-popup');
    existingMessages.forEach(msg => msg.remove());
    
    // Create new message
    const messageDiv = document.createElement('div');
    messageDiv.className = `message-popup message-${type}`;
    messageDiv.textContent = message;
    
    // Style the message
    messageDiv.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        border-radius: 5px;
        color: white;
        font-weight: bold;
        z-index: 1000;
        animation: slideIn 0.3s ease-out;
        max-width: 300px;
        word-wrap: break-word;
    `;
    
    // Set background color based on type
    switch(type) {
        case 'success':
            messageDiv.style.backgroundColor = '#4CAF50';
            break;
        case 'error':
            messageDiv.style.backgroundColor = '#f44336';
            break;
        default:
            messageDiv.style.backgroundColor = '#2196F3';
    }
    
    // Add animation keyframes if not already added
    if (!document.querySelector('#message-animations')) {
        const style = document.createElement('style');
        style.id = 'message-animations';
        style.textContent = `
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOut {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
        `;
        document.head.appendChild(style);
    }
    
    // Add to page
    document.body.appendChild(messageDiv);
    
    // Auto remove after 3 seconds
    setTimeout(() => {
        messageDiv.style.animation = 'slideOut 0.3s ease-in';
        setTimeout(() => {
            if (messageDiv.parentNode) {
                messageDiv.parentNode.removeChild(messageDiv);
            }
        }, 300);
    }, 3000);
}

// Initialize cart and wishlist counts on page load
 document.addEventListener('DOMContentLoaded', function() {
     if (typeof updateHeaderCounts === 'function') {
         updateHeaderCounts();
     }
 });
</script>

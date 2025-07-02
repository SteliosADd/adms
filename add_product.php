<?php
// File: add_product.php
include('include/connect.php');
session_start();

// Check if user is logged in and is a seller
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'seller') {
    header("Location: login.php?error=Unauthorized");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $category = trim($_POST['category']);
    $seller_id = $_SESSION['user_id'];
    
    // Handle image upload
    $image_path = 'img/air.png'; // Default image
    
    if(isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $upload_dir = 'uploads/';
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_name = time() . '_' . basename($_FILES['image']['name']);
        $target_path = $upload_dir . $file_name;
        
        if(move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
            $image_path = $target_path;
        }
    }

    // Insert product into database
    $stmt = $conn->prepare("INSERT INTO products (name, description, price, type, photo, seller_id) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssdssi", $name, $description, $price, $category, $image_path, $seller_id);
    
    if ($stmt->execute()) {
        header("Location: seller_dashboard.php?success=Product added successfully");
        exit();
    } else {
        $error = "Error adding product: " . $conn->error;
    }
    
    $stmt->close();
}

 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product</title>
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
        textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        textarea {
            height: 100px;
        }
        button {
            background-color: #333;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background-color: #555;
        }
        .error {
            color: red;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <?php include('include/header.php'); ?>

    <div class="container">
        <h1>Add a New Product</h1>
        
        <?php if(isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="add_product.php" enctype="multipart/form-data" id="add-product-form">
            <div class="form-group">
                <label for="name">Product Name:</label>
                <input type="text" id="name" name="name" required>
            </div>
            <div class="form-group">
                <label for="description">Description:</label>
                <textarea id="description" name="description" required></textarea>
            </div>
            <div class="form-group">
                <label for="price">Price ($):</label>
                <input type="number" id="price" name="price" step="0.01" min="0.01" required>
            </div>
            <div class="form-group">
                <label for="category">Category:</label>
                <input type="text" id="category" name="category" required>
            </div>
            <div class="form-group">
                <label for="image">Product Image:</label>
                <input type="file" id="image" name="image" accept="image/*">
                <small>Leave empty to use default image</small>
            </div>
            <div class="form-group">
                <button type="submit">Add Product</button>
            </div>
        </form>
    </div>
    
    <?php include('include/footer.php'); ?>
</body>
</html>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const form = document.querySelector("#add-product-form");

    form.addEventListener("submit", (e) => {
        const name = form.querySelector("#name").value.trim();
        const description = form.querySelector("#description").value.trim();
        const price = form.querySelector("#price").value.trim();
        const category = form.querySelector("#category").value.trim();

        // Basic validation
        if (!name || !description || !price || !category) {
            e.preventDefault();
            alert("Product name, description, price, and category are required!");
            return;
        }

        // Ensure price is a valid number
        if (isNaN(price) || parseFloat(price) <= 0) {
            e.preventDefault();
            alert("Price must be a valid positive number.");
            return;
        }

        // Validate image file type if one is selected
        const imageInput = form.querySelector("#image");
        if (imageInput.files.length > 0) {
            const file = imageInput.files[0];
            const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            
            if (!validTypes.includes(file.type)) {
                e.preventDefault();
                alert("Please select a valid image file (JPEG, PNG, GIF, or WEBP).");
                return;
            }
            
            // Check file size (max 5MB)
            if (file.size > 5 * 1024 * 1024) {
                e.preventDefault();
                alert("Image file size must be less than 5MB.");
                return;
            }
        }
    });
});
</script>

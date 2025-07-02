<?php
// File: cart_action.php
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
            $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
            
            if ($productId <= 0 || $quantity <= 0) {
                $message = "Invalid product or quantity";
                break;
            }
            
            // Check if the product is already in the cart
            $stmt = $conn->prepare("SELECT * FROM cart WHERE user_id = ? AND product_id = ?");
            $stmt->bind_param("ii", $userId, $productId);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                // Update quantity if product exists
                $stmt = $conn->prepare("UPDATE cart SET quantity = quantity + ? WHERE user_id = ? AND product_id = ?");
                $stmt->bind_param("iii", $quantity, $userId, $productId);
                if ($stmt->execute()) {
                    $message = "Cart updated successfully";
                    $redirect = "product_list.php";
                } else {
                    $message = "Error updating cart: " . $conn->error;
                }
            } else {
                // Insert new product
                $stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
                $stmt->bind_param("iii", $userId, $productId, $quantity);
                if ($stmt->execute()) {
                    $message = "Product added to cart successfully";
                    $redirect = "product_list.php";
                } else {
                    $message = "Error adding to cart: " . $conn->error;
                }
            }
            break;

        case 'update':
            $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
            $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
            
            if ($productId <= 0 || $quantity <= 0) {
                $message = "Invalid product or quantity";
                break;
            }
            
            $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
            $stmt->bind_param("iii", $quantity, $userId, $productId);
            if ($stmt->execute()) {
                $message = "Cart updated successfully";
                $redirect = "view_cart.php";
            } else {
                $message = "Error updating cart: " . $conn->error;
            }
            break;

        case 'remove':
            $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
            
            if ($productId <= 0) {
                $message = "Invalid product";
                break;
            }
            
            $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
            $stmt->bind_param("ii", $userId, $productId);
            if ($stmt->execute()) {
                $message = "Product removed from cart";
                $redirect = "view_cart.php";
            } else {
                $message = "Error removing from cart: " . $conn->error;
            }
            break;

        case 'get':
            $stmt = $conn->prepare("SELECT c.*, p.name, p.price, p.photo as image 
                                  FROM cart c
                                  JOIN products p ON c.product_id = p.id 
                                  WHERE c.user_id = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $cartItems = [];
            while ($row = $result->fetch_assoc()) {
                $cartItems[] = $row;
            }
            echo json_encode($cartItems);
            exit(); // Exit early for JSON response
// Remove break since we already called exit() above
    }
    
    // Redirect if specified
    if (!empty($redirect)) {
        header("Location: $redirect");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style4.css">
   
    <title>Products with Wishlist</title>
    </head>
<body>
 <?php   
if (!file_exists('include/header.php')) {
    die("Error: 'includes/header.php' does not exist. Please check the file path.");
}
include('include/header.php');
?>
    
        


     <div class="product-list">
    <div class="conatainer"></div>
    <div class="Product" id="Product">
       
       
        <div class="cart-summary">
        

        <div class="payment">
            <h1 class="payTitle">Personal Information</h1>
            <label>Name and Surname</label>
            <input type="text" placeholder="John Doe" class="payInput">
            <label>Phone Number</label>
            <input type="text" placeholder="+1 234 5678" class="payInput">
            <label>Address</label>
            <input type="text" placeholder="Elton St 21 22-145" class="payInput">
            <h1 class="payTitle">Card Information</h1>
            <div class="cardIcons">
                <img src="./img/visa.png" width="40" alt="" class="cardIcon">
                <img src="./img/master.png" alt="" width="40" class="cardIcon">
            </div>
            <input type="password" class="payInput" placeholder="Card Number">
            <div class="cardInfo">
                <input type="text" placeholder="mm" class="payInput sm">
                <input type="text" placeholder="yyyy" class="payInput sm">
                <input type="text" placeholder="cvv" class="payInput sm">
            </div>
             <form method="post" action="checkout.php">
            <button class="payButton">Checkout!</button>
            <span class="close">X</span>
        </div>
    </div>
    <div class="related-products">
    <div class="RelatedProduct">
        <img src="./img/air.png" alt="Product 1" class="RelatedProductImg">
        <div class="RelatedProductDetails">
            <h3 class="RelatedProductTitle">air force</h3>
            <p class="RelatedProductPrice">$100</p>
            <p class="RelatedProductDesc">A short description of the product goes here.</p>
            <button onclick="showPaymentForm()" class="add-to-cart">checkout</button>
            <form method="post" action="add_product.php" style="display: inline;">
    <button type="submit" class="btn btn-primary" onclick="location.href='view_wishlist.php'; return false;">go to wishlist</button>
</form>
        </div>
    </div>
    <div class="related-products">
    <div class="RelatedProduct">
    <h1>Add a New Product</h1>
    <form method='post' action='add_product.php'>
     <a href="add_product.php"class="add-to-cart" class="add-product-button">Add Product</a>
       
        
    </form>
        </div>
    </div>
   
    
    </body>
</html>
<script>
// Load cart items
function loadCart() {
    fetch('cart_action.php', {
        method: 'POST',
        body: new URLSearchParams({ action: 'get' })
    })
    .then(response => response.json())
    .then(cartItems => {
        const cartItemsContainer = document.getElementById('cartItems');
        cartItemsContainer.innerHTML = '';
        let total = 0;

        cartItems.forEach(item => {
            const itemTotal = item.price * item.quantity;
            total += itemTotal;

            cartItemsContainer.innerHTML += `
                <div class="cart-item">
                    <div class="cart-item-image">
                        <img src="${item.photo}" alt="${item.name}">
                    </div>
                    <div class="cart-item-details">
                        <h3>${item.name}</h3>
                        <p>Price: $${item.price.toFixed(2)}</p>
                        <div class="cart-item-quantity">
                            <label for="quantity-${item.product_id}">Quantity:</label>
                            <input id="quantity-${item.product_id}" type="number" value="${item.quantity}" min="1" onchange="updateCartQuantity(${item.product_id}, this.value)">
                        </div>
                        <p>Total: $${itemTotal.toFixed(2)}</p>
                        <div class="cart-item-actions">
                            <button class="remove-button" onclick="removeFromCart(${item.product_id})">Remove</button>
                            <button class="wishlist-button" onclick="saveToWishlist(${item.product_id})">Move to Wishlist</button>
                        </div>
                    </div>
                </div>
            `;
        });

        document.getElementById('totalPrice').textContent = total.toFixed(2);
    });
}
document.addEventListener("DOMContentLoaded", () => {
    const form = document.querySelector("#add-product-form");

    form.addEventListener("submit", (e) => {
        const name = form.querySelector("#name").value.trim();
        const description = form.querySelector("#description").value.trim();
        const price = form.querySelector("#price").value.trim();
        const type = form.querySelector("#type").value.trim();
        const sellerId = form.querySelector("#seller_id").value.trim();

        // Basic validation
        if (!name || !description || !price || !type || !sellerId) {
            e.preventDefault();
            alert("All fields except 'Download Link' are required!");
        }

        // Ensure price and seller ID are valid numbers
        if (isNaN(price) || isNaN(sellerId)) {
            e.preventDefault();
            alert("Price and Seller ID must be valid numbers.");
        }
    });
});
document.getElementById("goToWishlist").addEventListener("click", function() {
        window.location.href = "view_wishlist.php";
    });
 // Function to show the payment form
 function showPaymentForm() {
        const paymentDiv = document.querySelector('.payment');
        paymentDiv.style.display = 'block'; // Show the payment form
    }

    // Function to hide the payment form
    function hidePaymentForm() {
        const paymentDiv = document.querySelector('.payment');
        paymentDiv.style.display = 'none'; // Hide the payment form
    }

    // Attach event listener to the close button
    document.addEventListener('DOMContentLoaded', () => {
        const closeButton = document.querySelector('.payment .close');
        if (closeButton) {
            closeButton.addEventListener('click', hidePaymentForm);
        }
    });
    

</script>
<?php

if (!file_exists('include/footer.php')) {
    die("Error: 'include/footer.php' does not exist. Please check the file path.");
}
include('include/footer.php');
?>
    
<style>

footer {
    display: flex;
    background:rgb(47, 44, 44); /* Dark background for contrast */
    color: #fff; /* White text for readability */
}

.footerLeft {
    flex: 1;
    display: flex;
    justify-content: space-between;
    padding: 50px;
}

.fMenuTitle {
    font-size: 18px;
    font-weight: bold;
    color: #fff; /* Bright color for titles */
}

.fList {
    padding: 0;
    list-style: inside;
}

.fListItem {
    margin-bottom: 15px;
    color: #f3efef; /* Light gray for subtle links */
    cursor: pointer;
    transition: color 0.3s;
}

.fListItem:hover {
    color: #00ffcc; /* Accent color for hover effect */
}

.footerRight {
    flex: 1;
    padding: 50px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.fInput {
    padding: 10px;
    width: calc(100% - 90px);
    border: 1px solid #242121; /* Subtle border */
    border-radius: 4px;
    margin-right: 10px;
    background: #444; 
    color: #fff;
}

.fButton {
    padding: 8px 16px;
    background-color: #00ffcc; /* Vibrant accent color */
    color: #333; /* Dark text on bright background */
    border: none;
    border-radius: 4px;
    cursor: pointer;
    transition: background-color 0.3s;
}

.fButton:hover {
    background-color: #3821cb; /* Slightly darker hover effect */
}

.fIcons {
    display: flex;
}

.fIcon {
    width: 24px;
    height: 24px;
    margin-right: 15px;
    filter: brightness(0) invert(1); /* Makes icons white */
    transition: transform 0.3s;
}

.fIcon:hover {
    transform: scale(1.1); /* Slight zoom effect */
}


.copyright {
    margin-top: 20px;
    font-size: 0.9rem;
    color: #aaa;
}
</style>
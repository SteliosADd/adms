<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Header script loaded');
    
    // Ensure all navigation links work properly
    const navLinks = document.querySelectorAll('.navbar a:not(.dropbtn)');
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            console.log('Navigation link clicked:', this.href);
            // Allow normal navigation
        });
    });
    
    // Fix dropdown functionality
    const dropdownBtn = document.querySelector('.dropbtn');
    const dropdown = document.querySelector('.dropdown');
    
    if (dropdownBtn && dropdown) {
        const dropdownContent = dropdown.querySelector('.dropdown-content');
        
        // Add click event to dropdown button
        dropdownBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Toggle dropdown visibility
            const isVisible = dropdownContent.classList.contains('show');
            
            // Close all other dropdowns first
            document.querySelectorAll('.dropdown-content.show').forEach(content => {
                content.classList.remove('show');
            });
            
            // Toggle current dropdown
            if (!isVisible) {
                dropdownContent.classList.add('show');
            }
            
            console.log('Dropdown toggled, current state:', dropdownContent.classList.contains('show'));
        });
        
        // Prevent dropdown from closing when clicking inside it
        dropdownContent.addEventListener('click', function(e) {
            e.stopPropagation();
        });
        
        // Make dropdown links work properly
        const dropdownLinks = dropdownContent.querySelectorAll('a');
        dropdownLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                // Allow normal link behavior
                console.log('Dropdown link clicked:', this.href);
                // Close dropdown after clicking a link
                dropdownContent.classList.remove('show');
            });
        });
    }
    
    // Close dropdown when clicking outside, but not when clicking on dropdown content
    document.addEventListener('click', function(e) {
        if (!e.target.matches('.dropbtn') && 
            !e.target.closest('.dropdown-content')) {
            const dropdowns = document.getElementsByClassName('dropdown-content');
            for (let i = 0; i < dropdowns.length; i++) {
                const openDropdown = dropdowns[i];
                if (openDropdown.classList.contains('show')) {
                    openDropdown.classList.remove('show');
                }
            }
        }
    });
    
    // Initialize cart and wishlist counts
    updateHeaderCounts();
});

// Function to update cart and wishlist counts in header
function updateHeaderCounts() {
    // Update cart count
    fetch('cart_action.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=get'
    })
    .then(response => response.json())
    .then(data => {
        const cartCount = data.reduce((total, item) => total + parseInt(item.quantity), 0);
        const cartCountElement = document.querySelector('.cart-count');
        if (cartCountElement) {
            cartCountElement.textContent = cartCount;
            cartCountElement.style.display = cartCount > 0 ? 'inline-flex' : 'none';
        }
    })
    .catch(error => console.error('Error updating cart count:', error));
    
    // Update wishlist count
    fetch('add_wishlist.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=get'
    })
    .then(response => response.json())
    .then(data => {
        const wishlistCount = data.length;
        const wishlistCountElement = document.querySelector('.wishlist-count');
        if (wishlistCountElement) {
            wishlistCountElement.textContent = wishlistCount;
            wishlistCountElement.style.display = wishlistCount > 0 ? 'inline-flex' : 'none';
        }
    })
    .catch(error => console.error('Error updating wishlist count:', error));
}

// Make updateHeaderCounts globally available
window.updateHeaderCounts = updateHeaderCounts;
</script>
<?php
  
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

?>
<header>
    <nav id="navbar">
    <div class="navTop">
            <a href="index.php" class="brand-logo">
                <img src="./img/sneakers.png" alt="SneakZone Logo">
                <span class="brand-name">SneakZone</span>
            </a>
        </div>

        <div class="navbar">
            <ul style="list-style: none; margin: 0; padding: 0; display: flex;">
                <li><a <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'class="active"' : ''; ?> href="index.php"><i class="fas fa-home"></i> Home</a></li>
                <li><a <?php echo (basename($_SERVER['PHP_SELF']) == 'product_list.php') ? 'class="active"' : ''; ?> href="product_list.php"><i class="fas fa-search"></i> Products</a></li>
                <li><a <?php echo (basename($_SERVER['PHP_SELF']) == 'contactUS.php') ? 'class="active"' : ''; ?> href="contactUS.php"><i class="fas fa-envelope"></i> Contact</a></li>
            
            <?php if (isset($_SESSION['user_id'])): ?>
                <li><a <?php echo (basename($_SERVER['PHP_SELF']) == 'view_cart.php') ? 'class="active"' : ''; ?> href="view_cart.php"><i class="fas fa-shopping-cart"></i> Cart <span class="cart-count">0</span></a></li>
                <li><a <?php echo (basename($_SERVER['PHP_SELF']) == 'view_wishlist.php') ? 'class="active"' : ''; ?> href="view_wishlist.php"><i class="fas fa-heart"></i> Wishlist <span class="wishlist-count">0</span></a></li>
            <?php endif; ?>
            
                <li class="dropdown">
                    <a href="#" class="dropbtn"><i class="fas fa-user"></i> Account <i class="fas fa-caret-down"></i></a>
                    <div class="dropdown-content">
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <a href="register.php"><i class="fas fa-user-plus"></i> Sign Up</a>
                    <a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
                <?php else: ?>
                    <span>Welcome, <?php echo $_SESSION['username']; ?>!</span>
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
                        <a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Admin Dashboard</a>
                    <?php elseif (isset($_SESSION['role']) && $_SESSION['role'] == 'seller'): ?>
                        <a href="seller_dashboard.php"><i class="fas fa-tachometer-alt"></i> Seller Dashboard</a>
                        <a href="seller_orders.php"><i class="fas fa-shopping-bag"></i> Manage Orders</a>
                    <?php elseif (isset($_SESSION['role']) && ($_SESSION['role'] == 'customer' || $_SESSION['role'] == 'buyer')): ?>
                        <a href="customer_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="order_history.php"><i class="fas fa-history"></i> Order History</a>
            <?php endif; ?>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        <?php endif; ?>
                    </div>
                </li>
            </ul>
        </div>
    </nav>
</header>

<style>
html{
    scroll-behavior:smooth;
}

body {
    font-family:"Lato", sans-serif;
    padding: 0;
    margin:0;
}

.navTop{
    padding: 20px 30px;
    display: flex;
    align-items: center;
    justify-content: flex-start;
    background: linear-gradient(135deg, #000 0%, #1a1a1a 50%, #2d2d2d 100%);
    font-size: 16px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.navTop a {
    display: block;
}

.navbar{
    width: 100%;
    background: linear-gradient(135deg, #1a1a1a 0%, #000 100%);
    overflow: auto;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

.navbar ul {
    display: flex;
    flex-wrap: wrap;
    width: 100%;
}

.navbar li {
    display: inline-block;
}

.navbar a{
    display: block;
    text-align: center;
    padding: 20px 25px;
    color: white;
    text-decoration: none;
    font-size: 18px;
    font-weight: 500;
    cursor: pointer;
    pointer-events: auto;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}
.navbar a:hover{
    background: linear-gradient(135deg, #ff6b35 0%, #f7931e 100%);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(255, 107, 53, 0.3);
}

.navbar a::before {
    content: '';
    position: absolute;
    bottom: 0;
    left: 50%;
    width: 0;
    height: 3px;
    background: linear-gradient(135deg, #ff6b35 0%, #f7931e 100%);
    transition: all 0.3s ease;
    transform: translateX(-50%);
}

.navbar a:hover::before {
    width: 80%;
}
.active{
    background-color:rgb(19, 62, 47);
}

li.dropdown {
    position: relative;
    display: inline-block;
}

.dropbtn {
    display: inline-block;
    color: white;
    text-align: center;
    padding: 20px;
    text-decoration: none;
    cursor: pointer;
}

.dropdown-content {
    display: none;
    position: absolute;
    right: 0;
    background-color: rgb(156, 152, 152);
    min-width: 200px;
    box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
    z-index: 100; /* Increased z-index to ensure it appears above other elements */
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: opacity 0.3s ease, transform 0.3s ease, visibility 0s 0.3s;
}

.dropdown-content a, .dropdown-content span {
    color: black;
    padding: 12px 16px;
    text-decoration: none;
    display: block;
    text-align: left;
    font-size: 14px;
    font-weight: bold;
    background-color: rgb(156, 152, 152);
    border-bottom: 1px solid rgba(0,0,0,0.1);
}



.dropdown-content a:hover {
    background-color: #ddd;
    color: #333;
}

.dropdown-content a:hover {background-color: #f1f1f1;}

.dropdown-content.show {
    display: block;
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
    transition: opacity 0.3s ease, transform 0.3s ease, visibility 0s;
}

/* Cart and Wishlist Count Badges */
.cart-count, .wishlist-count {
    background-color: #dc3545;
    color: white;
    border-radius: 50%;
    padding: 2px 6px;
    font-size: 12px;
    font-weight: bold;
    margin-left: 5px;
    min-width: 18px;
    height: 18px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    line-height: 1;
}

.wishlist-count {
    background-color: #e91e63;
}

.cart-count:empty, .wishlist-count:empty {
    display: none;
}

@media screen and (max-width: 768px){
    .navbar ul {
        flex-direction: column;
        padding: 0;
        width: 100%;
    }
    
    .navbar li {
        width: 100%;
        display: block;
    }
    
    .navbar a, .navbar .dropbtn {
        width: 100%;
        text-align: left;
        box-sizing: border-box;
        padding: 15px;
    }
    
    li.dropdown {
        width: 100%;
        position: relative;
    }
    
    .dropdown-content {
        position: relative;
        width: 100%;
        left: 0;
        box-shadow: none;
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.3s ease-out;
    }
    
    .dropdown-content.show {
        max-height: 500px;
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }
}
</style>
<?php
// Complete Database Setup for SneakZone E-commerce
include('include/connect.php');

// Function to execute SQL queries safely
function executeQuery($conn, $query, $description) {
    if ($conn->query($query) === TRUE) {
        echo "<div class='success'>✓ $description completed successfully</div>";
        return true;
    } else {
        echo "<div class='error'>✗ Error in $description: " . $conn->error . "</div>";
        return false;
    }
}

// Function to check if table exists
function tableExists($conn, $tableName) {
    $result = $conn->query("SHOW TABLES LIKE '$tableName'");
    return $result->num_rows > 0;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup - SneakZone</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2c3e50;
            text-align: center;
            margin-bottom: 30px;
            font-size: 2.5rem;
        }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 12px 20px;
            border-radius: 8px;
            margin: 10px 0;
            border-left: 4px solid #28a745;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 12px 20px;
            border-radius: 8px;
            margin: 10px 0;
            border-left: 4px solid #dc3545;
        }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            padding: 12px 20px;
            border-radius: 8px;
            margin: 10px 0;
            border-left: 4px solid #17a2b8;
        }
        .section {
            margin: 30px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
        }
        .section h2 {
            color: #495057;
            margin-bottom: 15px;
            font-size: 1.5rem;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
            margin: 10px 5px;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }
        .test-accounts {
            background: #e3f2fd;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }
        .test-accounts h3 {
            color: #1565c0;
            margin-bottom: 15px;
        }
        .account-info {
            background: white;
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
            border-left: 4px solid #2196f3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 SneakZone Database Setup</h1>
        
        <?php
        echo "<div class='info'>📊 Starting database setup process...</div>";
        
        // Check database connection
        if ($conn->connect_error) {
            echo "<div class='error'>❌ Database connection failed: " . $conn->connect_error . "</div>";
            echo "<div class='info'>Please make sure XAMPP is running and MySQL service is started.</div>";
            exit();
        } else {
            echo "<div class='success'>✅ Database connection successful!</div>";
        }
        
        echo "<div class='section'>";
        echo "<h2>🗄️ Creating Core Tables</h2>";
        
        // Create Users table
        $usersTable = "
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            role ENUM('admin', 'seller', 'customer') NOT NULL DEFAULT 'customer',
            email VARCHAR(255) NOT NULL UNIQUE,
            fullname VARCHAR(255) NOT NULL,
            phone VARCHAR(20),
            address TEXT,
            date_of_birth DATE,
            gender ENUM('male', 'female', 'other'),
            profile_picture VARCHAR(255),
            notification_preferences JSON,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        executeQuery($conn, $usersTable, "Users table creation");
        
        // Create Products table
        $productsTable = "
        CREATE TABLE IF NOT EXISTS products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            description TEXT NOT NULL,
            price DECIMAL(10, 2) NOT NULL,
            photo VARCHAR(255),
            type ENUM('sneaker', 'apparel', 'accessory') NOT NULL,
            brand VARCHAR(100),
            category_id INT,
            stock_quantity INT DEFAULT 0,
            featured BOOLEAN DEFAULT FALSE,
            file_link VARCHAR(255),
            upload_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            seller_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        executeQuery($conn, $productsTable, "Products table creation");
        
        // Create Cart table
        $cartTable = "
        CREATE TABLE IF NOT EXISTS cart (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            product_id INT NOT NULL,
            quantity INT NOT NULL DEFAULT 1,
            added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
            UNIQUE KEY unique_user_product (user_id, product_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        executeQuery($conn, $cartTable, "Cart table creation");
        
        // Create Wishlist table
        $wishlistTable = "
        CREATE TABLE IF NOT EXISTS wishlist (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            product_id INT NOT NULL,
            added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
            UNIQUE KEY unique_user_product (user_id, product_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        executeQuery($conn, $wishlistTable, "Wishlist table creation");
        
        // Create Orders table
        $ordersTable = "
        CREATE TABLE IF NOT EXISTS orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            total_amount DECIMAL(10, 2) NOT NULL,
            discount_amount DECIMAL(10, 2) DEFAULT 0,
            shipping_amount DECIMAL(10, 2) DEFAULT 0,
            tax_amount DECIMAL(10, 2) DEFAULT 0,
            coupon_code VARCHAR(50),
            status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
            tracking_number VARCHAR(100),
            shipping_address TEXT,
            billing_address TEXT,
            payment_method VARCHAR(50),
            payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
            order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        executeQuery($conn, $ordersTable, "Orders table creation");
        
        // Create Order Details table
        $orderDetailsTable = "
        CREATE TABLE IF NOT EXISTS order_details (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            product_id INT NOT NULL,
            quantity INT NOT NULL,
            price DECIMAL(10, 2) NOT NULL,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        executeQuery($conn, $orderDetailsTable, "Order Details table creation");
        
        echo "</div>";
        
        // Enhanced Features Tables
        echo "<div class='section'>";
        echo "<h2>⭐ Creating Enhanced Feature Tables</h2>";
        
        // Product Reviews table
        $reviewsTable = "
        CREATE TABLE IF NOT EXISTS product_reviews (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT NOT NULL,
            user_id INT NOT NULL,
            rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
            review_text TEXT,
            review_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            status ENUM('pending','approved','rejected') DEFAULT 'approved',
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY unique_user_product_review (user_id, product_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        executeQuery($conn, $reviewsTable, "Product Reviews table creation");
        
        // Coupons table
        $couponsTable = "
        CREATE TABLE IF NOT EXISTS coupons (
            id INT AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(50) NOT NULL UNIQUE,
            description VARCHAR(255),
            discount_type ENUM('percentage','fixed') NOT NULL,
            discount_value DECIMAL(10,2) NOT NULL,
            minimum_amount DECIMAL(10,2) DEFAULT 0,
            usage_limit INT DEFAULT NULL,
            used_count INT DEFAULT 0,
            start_date DATETIME NOT NULL,
            end_date DATETIME NOT NULL,
            status ENUM('active','inactive') DEFAULT 'active',
            created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        executeQuery($conn, $couponsTable, "Coupons table creation");
        
        // Coupon Usage table
        $couponUsageTable = "
        CREATE TABLE IF NOT EXISTS coupon_usage (
            id INT AUTO_INCREMENT PRIMARY KEY,
            coupon_id INT NOT NULL,
            user_id INT NOT NULL,
            order_id INT NOT NULL,
            discount_amount DECIMAL(10,2) NOT NULL,
            used_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (coupon_id) REFERENCES coupons(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        executeQuery($conn, $couponUsageTable, "Coupon Usage table creation");
        
        // Categories table
        $categoriesTable = "
        CREATE TABLE IF NOT EXISTS categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL UNIQUE,
            description TEXT,
            parent_id INT DEFAULT NULL,
            image VARCHAR(255),
            status ENUM('active','inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        executeQuery($conn, $categoriesTable, "Categories table creation");
        
        // Notifications table
        $notificationsTable = "
        CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            type ENUM('order','product','system','promotion') DEFAULT 'system',
            is_read BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        executeQuery($conn, $notificationsTable, "Notifications table creation");
        
        echo "</div>";
        
        // Insert sample data
        echo "<div class='section'>";
        echo "<h2>👥 Creating Test Accounts</h2>";
        
        // Check if admin user exists
        $adminCheck = $conn->query("SELECT id FROM users WHERE username = 'admin'");
        if ($adminCheck->num_rows == 0) {
            $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
            $adminInsert = "INSERT INTO users (username, email, password, role, fullname) VALUES 
                           ('admin', 'admin@sneakzone.com', '$adminPassword', 'admin', 'System Administrator')";
            executeQuery($conn, $adminInsert, "Admin account creation");
        } else {
            echo "<div class='info'>ℹ️ Admin account already exists</div>";
        }
        
        // Check if seller user exists
        $sellerCheck = $conn->query("SELECT id FROM users WHERE username = 'seller1'");
        if ($sellerCheck->num_rows == 0) {
            $sellerPassword = password_hash('seller123', PASSWORD_DEFAULT);
            $sellerInsert = "INSERT INTO users (username, email, password, role, fullname) VALUES 
                            ('seller1', 'seller1@sneakzone.com', '$sellerPassword', 'seller', 'John Seller')";
            executeQuery($conn, $sellerInsert, "Seller account creation");
        } else {
            echo "<div class='info'>ℹ️ Seller account already exists</div>";
        }
        
        // Check if customer user exists
        $customerCheck = $conn->query("SELECT id FROM users WHERE username = 'customer1'");
        if ($customerCheck->num_rows == 0) {
            $customerPassword = password_hash('customer123', PASSWORD_DEFAULT);
            $customerInsert = "INSERT INTO users (username, email, password, role, fullname) VALUES 
                             ('customer1', 'customer1@sneakzone.com', '$customerPassword', 'customer', 'Jane Customer')";
            executeQuery($conn, $customerInsert, "Customer account creation");
        } else {
            echo "<div class='info'>ℹ️ Customer account already exists</div>";
        }
        
        echo "</div>";
        
        // Insert sample categories
        echo "<div class='section'>";
        echo "<h2>📂 Creating Sample Categories</h2>";
        
        $categories = [
            ['Sneakers', 'Athletic and casual footwear'],
            ['Basketball Shoes', 'High-performance basketball sneakers'],
            ['Running Shoes', 'Lightweight running and jogging shoes'],
            ['Casual Sneakers', 'Everyday casual footwear'],
            ['Apparel', 'Clothing and accessories'],
            ['Accessories', 'Bags, hats, and other accessories']
        ];
        
        foreach ($categories as $category) {
            $categoryCheck = $conn->query("SELECT id FROM categories WHERE name = '{$category[0]}'");
            if ($categoryCheck->num_rows == 0) {
                $categoryInsert = "INSERT INTO categories (name, description) VALUES ('{$category[0]}', '{$category[1]}')";
                executeQuery($conn, $categoryInsert, "Category '{$category[0]}' creation");
            }
        }
        
        echo "</div>";
        
        // Insert sample coupons
        echo "<div class='section'>";
        echo "<h2>🎫 Creating Sample Coupons</h2>";
        
        $coupons = [
            ['WELCOME10', 'Welcome discount for new customers', 'percentage', 10, 50, 100, '2024-01-01 00:00:00', '2024-12-31 23:59:59'],
            ['SAVE20', 'Save $20 on orders over $100', 'fixed', 20, 100, 50, '2024-01-01 00:00:00', '2024-12-31 23:59:59'],
            ['SUMMER15', 'Summer sale - 15% off', 'percentage', 15, 75, 200, '2024-06-01 00:00:00', '2024-08-31 23:59:59']
        ];
        
        foreach ($coupons as $coupon) {
            $couponCheck = $conn->query("SELECT id FROM coupons WHERE code = '{$coupon[0]}'");
            if ($couponCheck->num_rows == 0) {
                $couponInsert = "INSERT INTO coupons (code, description, discount_type, discount_value, minimum_amount, usage_limit, start_date, end_date) 
                                VALUES ('{$coupon[0]}', '{$coupon[1]}', '{$coupon[2]}', {$coupon[3]}, {$coupon[4]}, {$coupon[5]}, '{$coupon[6]}', '{$coupon[7]}')";
                executeQuery($conn, $couponInsert, "Coupon '{$coupon[0]}' creation");
            }
        }
        
        echo "</div>";
        
        echo "<div class='success'>🎉 Database setup completed successfully!</div>";
        ?>
        
        <div class="test-accounts">
            <h3>🔐 Test Account Credentials</h3>
            <div class="account-info">
                <strong>👨‍💼 Admin Account:</strong><br>
                Username: <code>admin</code><br>
                Password: <code>admin123</code><br>
                Access: Full system administration
            </div>
            <div class="account-info">
                <strong>🏪 Seller Account:</strong><br>
                Username: <code>seller1</code><br>
                Password: <code>seller123</code><br>
                Access: Product management and sales
            </div>
            <div class="account-info">
                <strong>🛒 Customer Account:</strong><br>
                Username: <code>customer1</code><br>
                Password: <code>customer123</code><br>
                Access: Shopping and order management
            </div>
        </div>
        
        <div style="text-align: center; margin-top: 30px;">
            <a href="login.php" class="btn">🔑 Go to Login</a>
            <a href="register.php" class="btn">📝 Go to Register</a>
            <a href="index.php" class="btn">🏠 Go to Homepage</a>
            <a href="enhanced_admin_dashboard.php" class="btn">👨‍💼 Admin Dashboard</a>
        </div>
    </div>
</body>
</html>
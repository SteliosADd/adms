<?php
// Quick database fix script
echo "<h1>SneakZone Database Fix</h1>";

$host = 'localhost';
$username = 'root';
$password = '';
$database = 'my_database_user';

// Connect to MySQL server
$conn = new mysqli($host, $username, $password);

if ($conn->connect_error) {
    die("<p style='color: red;'>Connection failed: " . $conn->connect_error . "</p>");
}

echo "<p style='color: green;'>✓ Connected to MySQL server</p>";

// Create database
$sql = "CREATE DATABASE IF NOT EXISTS $database";
if ($conn->query($sql) === TRUE) {
    echo "<p style='color: green;'>✓ Database '$database' ready</p>";
} else {
    die("<p style='color: red;'>Error creating database: " . $conn->error . "</p>");
}

// Select database
$conn->select_db($database);

// Create users table
$users_table = "
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'seller', 'customer') NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    fullname VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($users_table) === TRUE) {
    echo "<p style='color: green;'>✓ Users table created/verified</p>";
} else {
    echo "<p style='color: red;'>Error creating users table: " . $conn->error . "</p>";
}

// Create products table
$products_table = "
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    photo VARCHAR(255),
    type ENUM('sneaker', 'apparel', 'accessory') NOT NULL,
    file_link VARCHAR(255),
    upload_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    seller_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE
)";

if ($conn->query($products_table) === TRUE) {
    echo "<p style='color: green;'>✓ Products table created/verified</p>";
} else {
    echo "<p style='color: red;'>Error creating products table: " . $conn->error . "</p>";
}

// Create cart table
$cart_table = "
CREATE TABLE IF NOT EXISTS cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
)";

if ($conn->query($cart_table) === TRUE) {
    echo "<p style='color: green;'>✓ Cart table created/verified</p>";
} else {
    echo "<p style='color: red;'>Error creating cart table: " . $conn->error . "</p>";
}

// Create wishlist table
$wishlist_table = "
CREATE TABLE IF NOT EXISTS wishlist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
)";

if ($conn->query($wishlist_table) === TRUE) {
    echo "<p style='color: green;'>✓ Wishlist table created/verified</p>";
} else {
    echo "<p style='color: red;'>Error creating wishlist table: " . $conn->error . "</p>";
}

// Create orders table
$orders_table = "
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";

if ($conn->query($orders_table) === TRUE) {
    echo "<p style='color: green;'>✓ Orders table created/verified</p>";
} else {
    echo "<p style='color: red;'>Error creating orders table: " . $conn->error . "</p>";
}

// Create order_details table
$order_details_table = "
CREATE TABLE IF NOT EXISTS order_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
)";

if ($conn->query($order_details_table) === TRUE) {
    echo "<p style='color: green;'>✓ Order details table created/verified</p>";
} else {
    echo "<p style='color: red;'>Error creating order details table: " . $conn->error . "</p>";
}

// Check if we need to insert sample data
$user_count = $conn->query("SELECT COUNT(*) as count FROM users");
if ($user_count && $user_count->fetch_assoc()['count'] == 0) {
    echo "<p style='color: blue;'>Inserting sample data...</p>";
    
    // Insert sample users
    $sample_users = "
    INSERT INTO users (username, email, password, role, fullname) VALUES
    ('admin', 'admin@sneakzone.com', '$2y$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'Administrator'),
    ('seller1', 'seller1@sneakzone.com', '$2y$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'seller', 'John Seller'),
    ('customer1', 'customer1@sneakzone.com', '$2y$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer', 'Jane Customer')
    ";
    
    if ($conn->query($sample_users) === TRUE) {
        echo "<p style='color: green;'>✓ Sample users inserted</p>";
        
        // Insert sample products
        $sample_products = "
        INSERT INTO products (name, description, price, photo, type, file_link, seller_id) VALUES
        ('Air Force 1 White', 'Classic Nike Air Force 1 in pristine white. A timeless sneaker that goes with everything.', 120.00, 'img/air.png', 'sneaker', '', 2),
        ('Jordan 1 Retro High', 'Iconic Air Jordan 1 in the classic Chicago colorway. A must-have for any sneaker collection.', 170.00, 'img/jordan.png', 'sneaker', '', 2),
        ('Premium Sneakers', 'High-quality sneakers with modern design and superior comfort.', 200.00, 'img/sneakers.png', 'sneaker', '', 2)
        ";
        
        if ($conn->query($sample_products) === TRUE) {
            echo "<p style='color: green;'>✓ Sample products inserted</p>";
        } else {
            echo "<p style='color: orange;'>Warning: Could not insert sample products: " . $conn->error . "</p>";
        }
    } else {
        echo "<p style='color: orange;'>Warning: Could not insert sample users: " . $conn->error . "</p>";
    }
} else {
    echo "<p style='color: blue;'>Sample data already exists</p>";
}

// Final verification
$result = $conn->query("SHOW TABLES");
if ($result && $result->num_rows > 0) {
    echo "<h3>Database Tables:</h3><ul>";
    while ($row = $result->fetch_array()) {
        echo "<li>" . $row[0] . "</li>";
    }
    echo "</ul>";
}

echo "<h2 style='color: green;'>✅ Database setup complete!</h2>";
echo "<p><strong>Test accounts (password: 'password'):</strong></p>";
echo "<ul>";
echo "<li>Admin: admin@sneakzone.com</li>";
echo "<li>Seller: seller1@sneakzone.com</li>";
echo "<li>Customer: customer1@sneakzone.com</li>";
echo "</ul>";
echo "<p><a href='login.php' style='background: #ff6b6b; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Login</a></p>";
echo "<p><a href='index.php' style='background: #4ecdc4; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Homepage</a></p>";

$conn->close();
?>
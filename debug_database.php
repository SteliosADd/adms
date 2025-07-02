<?php
echo "<h1>Database Debug - SneakZone</h1>";
echo "<style>body{font-family:Arial;padding:20px;} .success{color:green;} .error{color:red;} .info{color:blue;}</style>";

// Database connection details
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'my_database_user';

echo "<h2>1. Testing MySQL Connection</h2>";

// Test connection to MySQL server
$conn = new mysqli($host, $username, $password);
if ($conn->connect_error) {
    echo "<p class='error'>❌ MySQL Connection Failed: " . $conn->connect_error . "</p>";
    exit;
} else {
    echo "<p class='success'>✅ MySQL Server Connected Successfully</p>";
}

echo "<h2>2. Checking Database Existence</h2>";

// Check if database exists
$db_check = $conn->query("SHOW DATABASES LIKE '$database'");
if ($db_check->num_rows == 0) {
    echo "<p class='error'>❌ Database '$database' does not exist</p>";
    echo "<p class='info'>Creating database...</p>";
    
    if ($conn->query("CREATE DATABASE $database") === TRUE) {
        echo "<p class='success'>✅ Database '$database' created successfully</p>";
    } else {
        echo "<p class='error'>❌ Error creating database: " . $conn->error . "</p>";
        exit;
    }
} else {
    echo "<p class='success'>✅ Database '$database' exists</p>";
}

// Select the database
$conn->select_db($database);

echo "<h2>3. Checking Tables</h2>";

// Check tables
$tables = ['users', 'products', 'cart', 'wishlist', 'orders', 'order_details'];
foreach ($tables as $table) {
    $table_check = $conn->query("SHOW TABLES LIKE '$table'");
    if ($table_check->num_rows == 0) {
        echo "<p class='error'>❌ Table '$table' does not exist</p>";
    } else {
        echo "<p class='success'>✅ Table '$table' exists</p>";
        
        // Count records
        $count_result = $conn->query("SELECT COUNT(*) as count FROM $table");
        if ($count_result) {
            $count = $count_result->fetch_assoc()['count'];
            echo "<p class='info'>   → Records: $count</p>";
        }
    }
}

echo "<h2>4. Testing Users Table Specifically</h2>";

// Test users table structure
$users_structure = $conn->query("DESCRIBE users");
if ($users_structure) {
    echo "<p class='success'>✅ Users table structure:</p>";
    echo "<table border='1' style='border-collapse:collapse; margin:10px 0;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while ($row = $users_structure->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p class='error'>❌ Cannot describe users table: " . $conn->error . "</p>";
}

echo "<h2>5. Testing Sample Users</h2>";

// Check for sample users
$users_query = $conn->query("SELECT id, username, email, role FROM users LIMIT 5");
if ($users_query && $users_query->num_rows > 0) {
    echo "<p class='success'>✅ Sample users found:</p>";
    echo "<table border='1' style='border-collapse:collapse; margin:10px 0;'>";
    echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th></tr>";
    while ($user = $users_query->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $user['id'] . "</td>";
        echo "<td>" . $user['username'] . "</td>";
        echo "<td>" . $user['email'] . "</td>";
        echo "<td>" . $user['role'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p class='error'>❌ No users found in database</p>";
}

echo "<h2>6. Connection Test from include/connect.php</h2>";

// Test the actual connection file
try {
    include('include/connect.php');
    echo "<p class='success'>✅ include/connect.php loaded successfully</p>";
    
    // Test a simple query
    $test_query = $conn->query("SELECT 1 as test");
    if ($test_query) {
        echo "<p class='success'>✅ Database query test successful</p>";
    } else {
        echo "<p class='error'>❌ Database query test failed: " . $conn->error . "</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Error loading connect.php: " . $e->getMessage() . "</p>";
}

echo "<h2>7. Quick Fix Actions</h2>";
echo "<p><a href='fix_database.php' style='background:#ff6b6b;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>🔧 Run Database Fix</a></p>";
echo "<p><a href='setup_database.php' style='background:#4ecdc4;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>⚙️ Run Setup Database</a></p>";
echo "<p><a href='login.php' style='background:#45b7d1;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>🔑 Try Login</a></p>";

echo "<h2>Debug Complete</h2>";
echo "<p class='info'>If you see any ❌ errors above, click the 'Run Database Fix' button.</p>";

$conn->close();
?>
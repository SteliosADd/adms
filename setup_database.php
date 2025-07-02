<?php
// Database setup script
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'my_database_user';

echo "<h1>Database Setup</h1>";

// Connect to MySQL server (without selecting database)
$conn = new mysqli($host, $username, $password);

if ($conn->connect_error) {
    die("<p style='color: red;'>Connection failed: " . $conn->connect_error . "</p>");
}

echo "<p style='color: green;'>Connected to MySQL server successfully!</p>";

// Create database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS $database";
if ($conn->query($sql) === TRUE) {
    echo "<p style='color: green;'>Database '$database' created successfully or already exists!</p>";
} else {
    die("<p style='color: red;'>Error creating database: " . $conn->error . "</p>");
}

// Select the database
$conn->select_db($database);

// Read and execute the SQL file
$sql_file = 'store.sql';
if (file_exists($sql_file)) {
    $sql_content = file_get_contents($sql_file);
    
    // Split SQL commands by semicolon
    $sql_commands = explode(';', $sql_content);
    
    echo "<h2>Executing SQL commands...</h2>";
    
    foreach ($sql_commands as $command) {
        $command = trim($command);
        if (!empty($command) && !preg_match('/^--/', $command)) {
            if ($conn->query($command) === TRUE) {
                // Only show successful table creation/insertion messages
                if (preg_match('/CREATE TABLE|INSERT INTO/', $command)) {
                    if (preg_match('/CREATE TABLE (\w+)/', $command, $matches)) {
                        echo "<p style='color: green;'>✓ Table '{$matches[1]}' created successfully</p>";
                    } elseif (preg_match('/INSERT INTO (\w+)/', $command, $matches)) {
                        echo "<p style='color: blue;'>✓ Sample data inserted into '{$matches[1]}' table</p>";
                    }
                }
            } else {
                echo "<p style='color: red;'>Error executing command: " . $conn->error . "</p>";
                echo "<p>Command: " . htmlspecialchars(substr($command, 0, 100)) . "...</p>";
            }
        }
    }
    
    echo "<h2>Database Setup Complete!</h2>";
    
    // Verify tables were created
    $result = $conn->query("SHOW TABLES");
    if ($result && $result->num_rows > 0) {
        echo "<p style='color: green;'>Tables created:</p>";
        echo "<ul>";
        while ($row = $result->fetch_array()) {
            echo "<li>" . $row[0] . "</li>";
        }
        echo "</ul>";
    }
    
    // Check if products exist
    $product_count = $conn->query("SELECT COUNT(*) as count FROM products");
    if ($product_count) {
        $count = $product_count->fetch_assoc()['count'];
        echo "<p>Total products in database: <strong>$count</strong></p>";
    }
    
    echo "<p><a href='product_list_debug.php'>Test Product List</a> | <a href='product_list.php'>Go to Product List</a></p>";
    
} else {
    echo "<p style='color: red;'>SQL file '$sql_file' not found!</p>";
}

$conn->close();
?>
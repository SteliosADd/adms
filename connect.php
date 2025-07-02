<?php
$host = 'localhost';
$username = 'root'; // Default XAMPP username
$password = '';     // Default XAMPP password is empty
$database = 'my_database_user'; // Fixed to match the database name in store.sql

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8 for proper character handling
mysqli_set_charset($conn, 'utf8');
?>

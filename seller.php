<?php
// File: seller.php
include('include/connect.php');
session_start();

// Check if the user is logged in and is a seller
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'seller') {
    header("Location: login.php?error=Unauthorized");
    exit();
}

// Fetch seller-specific information
$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM users WHERE id = ? AND role = 'seller'";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$seller = $result->fetch_assoc();
$stmt->close();

if (!$seller) {
    die("Seller not found.");
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Profile</title>
    <style>
        body { font-family: Arial, sans-serif; }
        .profile { max-width: 600px; margin: auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
        .profile h1 { margin-bottom: 20px; }
        .profile p { margin: 10px 0; }
    </style>
</head>
<body>
    <div class="profile">
        <h1>Welcome, <?php echo htmlspecialchars($seller['fullname']); ?>!</h1>
        <p><strong>Username:</strong> <?php echo htmlspecialchars($seller['username']); ?></p>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($seller['email']); ?></p>
        <p><strong>Role:</strong> Seller</p>
        <a href="seller_dashboard.php">Go to Dashboard</a>
        <br><br>
        <a href="logout.php">Logout</a>
    </div>
</body>
</html>

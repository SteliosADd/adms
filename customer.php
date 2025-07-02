<?php
// File: customer.php
include('include/connect.php');
session_start();

// Check if the user is logged in and is a customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: login.php?error=Unauthorized");
    exit();
}

// Fetch customer-specific information
$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM users WHERE id = ? AND role = 'customer'";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$customer = $result->fetch_assoc();
$stmt->close();

if (!$customer) {
    die("Customer not found.");
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Profile</title>
    <style>
        body { font-family: Arial, sans-serif; }
        .profile { max-width: 600px; margin: auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
        .profile h1 { margin-bottom: 20px; }
        .profile p { margin: 10px 0; }
    </style>
</head>
<body>
    <div class="profile">
        <h1>Welcome, <?php echo htmlspecialchars($customer['fullname']); ?>!</h1>
        <p><strong>Username:</strong> <?php echo htmlspecialchars($customer['username']); ?></p>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($customer['email']); ?></p>
        <p><strong>Role:</strong> Customer</p>
        <a href="customer_dashboard.php">Go to Dashboard</a>
        <br><br>
        <a href="logout.php">Logout</a>
    </div>
</body>
</html>

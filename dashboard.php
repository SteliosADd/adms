<?php
session_start();
include('include/connect.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$role = $_SESSION['role'];

// Redirect to role-specific dashboard
if ($role === 'seller') {
    header("Location: seller_dashboard.php");
    exit();
} elseif ($role === 'buyer' || $role === 'customer') {
    header("Location: customer_dashboard.php");
    exit();
}
?>
<?php include('header.php'); ?>

<div class="container">
    <h1>Welcome, <?php echo htmlspecialchars($username); ?>!</h1>
    <p>You are logged in as <strong><?php echo htmlspecialchars($role); ?></strong>.</p>

    <?php if ($role === 'seller'): ?>
        <!-- Dashboard για πωλητές -->
        <h2>Seller Dashboard</h2>
        <a href="add_product.php" class="btn btn-primary">Add New Product</a>
        <h3>Your Products</h3>
        <?php
        $stmt = $conn->prepare("SELECT * FROM products WHERE seller_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0): ?>
            <table>
                <tr>
                    <th>Product Name</th>
                    <th>Description</th>
                    <th>Price</th>
                    <th>Actions</th>
                </tr>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td><?php echo htmlspecialchars($row['description']); ?></td>
                        <td>$<?php echo number_format($row['price'], 2); ?></td>
                        <td>
                            <a href="edit_product.php?id=<?php echo $row['id']; ?>" class="btn btn-secondary">Edit</a>
                            <a href="delete_product.php?id=<?php echo $row['id']; ?>" class="btn btn-danger">Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </table>
        <?php else: ?>
            <p>You have not added any products yet.</p>
        <?php endif;
        $stmt->close();
        ?>
    <?php elseif ($role === 'customer'): ?>
       
        <h2>Customer Dashboard</h2>
        <a href="browse_products.php" class="btn btn-primary">Browse Products</a>
        <a href="cart.php" class="btn btn-secondary">View Cart</a>
        <a href="wishlist.php" class="btn btn-secondary">View Wishlist</a>
    <?php endif; ?>
</div>

<?php include('../includes/footer.php'); ?>

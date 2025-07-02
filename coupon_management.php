<?php
session_start();
include 'include/connect.php';

// Check if user is admin
if (!isset($_SESSION['admin_name'])) {
    header('Location: login.php');
    exit();
}

$message = '';
$message_type = '';

// Handle coupon actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_coupon'])) {
        $code = strtoupper(trim($_POST['code']));
        $description = trim($_POST['description']);
        $discount_type = $_POST['discount_type'];
        $discount_value = (float)$_POST['discount_value'];
        $minimum_amount = (float)$_POST['minimum_amount'];
        $usage_limit = !empty($_POST['usage_limit']) ? (int)$_POST['usage_limit'] : NULL;
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];
        $status = $_POST['status'];
        
        // Validate inputs
        if (empty($code) || empty($description) || $discount_value <= 0) {
            $message = "Please fill in all required fields with valid values.";
            $message_type = "error";
        } else {
            // Check if coupon code already exists
            $check_query = "SELECT id FROM coupons WHERE code = ?";
            $check_stmt = $conn->prepare($check_query);
            $check_stmt->bind_param("s", $code);
            $check_stmt->execute();
            $existing_coupon = $check_stmt->get_result()->fetch_assoc();
            
            if ($existing_coupon) {
                $message = "Coupon code already exists. Please use a different code.";
                $message_type = "error";
            } else {
                $insert_query = "INSERT INTO coupons (code, description, discount_type, discount_value, minimum_amount, usage_limit, start_date, end_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $insert_stmt = $conn->prepare($insert_query);
                $insert_stmt->bind_param("sssddisss", $code, $description, $discount_type, $discount_value, $minimum_amount, $usage_limit, $start_date, $end_date, $status);
                
                if ($insert_stmt->execute()) {
                    $message = "Coupon created successfully!";
                    $message_type = "success";
                } else {
                    $message = "Error creating coupon. Please try again.";
                    $message_type = "error";
                }
            }
        }
    }
    
    if (isset($_POST['update_status'])) {
        $coupon_id = (int)$_POST['coupon_id'];
        $new_status = $_POST['new_status'];
        
        $update_query = "UPDATE coupons SET status = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("si", $new_status, $coupon_id);
        
        if ($update_stmt->execute()) {
            $message = "Coupon status updated successfully!";
            $message_type = "success";
        } else {
            $message = "Error updating coupon status.";
            $message_type = "error";
        }
    }
    
    if (isset($_POST['delete_coupon'])) {
        $coupon_id = (int)$_POST['coupon_id'];
        
        $delete_query = "DELETE FROM coupons WHERE id = ?";
        $delete_stmt = $conn->prepare($delete_query);
        $delete_stmt->bind_param("i", $coupon_id);
        
        if ($delete_stmt->execute()) {
            $message = "Coupon deleted successfully!";
            $message_type = "success";
        } else {
            $message = "Error deleting coupon.";
            $message_type = "error";
        }
    }
}

// Get all coupons with usage statistics
$coupons_query = "SELECT c.*, 
                  COUNT(cu.id) as total_uses,
                  COALESCE(SUM(cu.discount_amount), 0) as total_discount_given
                  FROM coupons c
                  LEFT JOIN coupon_usage cu ON c.id = cu.coupon_id
                  GROUP BY c.id
                  ORDER BY c.created_date DESC";
$coupons_result = $conn->query($coupons_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coupon Management - Admin Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .admin-header {
            background: #007bff;
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .admin-header h1 {
            margin: 0;
        }
        .coupon-form {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .form-row {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }
        .form-group {
            flex: 1;
            min-width: 200px;
        }
        .form-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
            color: #333;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        .form-group textarea {
            min-height: 60px;
            resize: vertical;
        }
        .coupons-table {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .table-header {
            background: #f8f9fa;
            padding: 15px 20px;
            border-bottom: 1px solid #dee2e6;
        }
        .table-content {
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        th {
            background: #f8f9fa;
            font-weight: bold;
            color: #333;
        }
        .coupon-code {
            font-family: monospace;
            background: #e9ecef;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: bold;
        }
        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }
        .status-expired {
            background: #fff3cd;
            color: #856404;
        }
        .discount-badge {
            background: #007bff;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        .btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 12px;
            margin: 2px;
        }
        .btn-primary {
            background: #007bff;
            color: white;
        }
        .btn-success {
            background: #28a745;
            color: white;
        }
        .btn-warning {
            background: #ffc107;
            color: #212529;
        }
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        .btn:hover {
            opacity: 0.8;
        }
        .message {
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
        }
        .stat-label {
            color: #666;
            margin-top: 5px;
        }
        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
            }
            .form-group {
                min-width: auto;
            }
        }
    </style>
</head>
<body>
    <?php include 'include/header.php'; ?>
    
    <div class="admin-container">
        <div class="admin-header">
            <h1>Coupon Management</h1>
            <p>Create and manage discount coupons for your store</p>
        </div>
        
        <?php if (!empty($message)): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php
        // Get coupon statistics
        $stats_query = "SELECT 
                        COUNT(*) as total_coupons,
                        SUM(CASE WHEN status = 'active' AND end_date > NOW() THEN 1 ELSE 0 END) as active_coupons,
                        SUM(used_count) as total_uses,
                        COALESCE(SUM(cu.discount_amount), 0) as total_savings
                        FROM coupons c
                        LEFT JOIN coupon_usage cu ON c.id = cu.coupon_id";
        $stats_result = $conn->query($stats_query);
        $stats = $stats_result->fetch_assoc();
        ?>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['total_coupons']; ?></div>
                <div class="stat-label">Total Coupons</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['active_coupons']; ?></div>
                <div class="stat-label">Active Coupons</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['total_uses']; ?></div>
                <div class="stat-label">Total Uses</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">$<?php echo number_format($stats['total_savings'], 2); ?></div>
                <div class="stat-label">Total Savings Given</div>
            </div>
        </div>
        
        <div class="coupon-form">
            <h2>Create New Coupon</h2>
            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label for="code">Coupon Code *</label>
                        <input type="text" id="code" name="code" required placeholder="e.g., SAVE20">
                    </div>
                    <div class="form-group">
                        <label for="discount_type">Discount Type *</label>
                        <select id="discount_type" name="discount_type" required>
                            <option value="percentage">Percentage (%)</option>
                            <option value="fixed">Fixed Amount ($)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="discount_value">Discount Value *</label>
                        <input type="number" id="discount_value" name="discount_value" step="0.01" min="0" required placeholder="10.00">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="description">Description *</label>
                        <textarea id="description" name="description" required placeholder="Describe this coupon..."></textarea>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="minimum_amount">Minimum Order Amount</label>
                        <input type="number" id="minimum_amount" name="minimum_amount" step="0.01" min="0" value="0" placeholder="0.00">
                    </div>
                    <div class="form-group">
                        <label for="usage_limit">Usage Limit</label>
                        <input type="number" id="usage_limit" name="usage_limit" min="1" placeholder="Leave empty for unlimited">
                    </div>
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="start_date">Start Date *</label>
                        <input type="datetime-local" id="start_date" name="start_date" required value="<?php echo date('Y-m-d\TH:i'); ?>">
                    </div>
                    <div class="form-group">
                        <label for="end_date">End Date *</label>
                        <input type="datetime-local" id="end_date" name="end_date" required value="<?php echo date('Y-m-d\TH:i', strtotime('+30 days')); ?>">
                    </div>
                </div>
                
                <button type="submit" name="add_coupon" class="btn btn-primary">Create Coupon</button>
            </form>
        </div>
        
        <div class="coupons-table">
            <div class="table-header">
                <h2>Existing Coupons</h2>
            </div>
            <div class="table-content">
                <table>
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Description</th>
                            <th>Discount</th>
                            <th>Min. Amount</th>
                            <th>Usage</th>
                            <th>Valid Period</th>
                            <th>Status</th>
                            <th>Total Savings</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($coupons_result->num_rows > 0): ?>
                            <?php while ($coupon = $coupons_result->fetch_assoc()): ?>
                                <?php
                                $is_expired = strtotime($coupon['end_date']) < time();
                                $is_not_started = strtotime($coupon['start_date']) > time();
                                $usage_percentage = $coupon['usage_limit'] ? ($coupon['used_count'] / $coupon['usage_limit']) * 100 : 0;
                                ?>
                                <tr>
                                    <td><span class="coupon-code"><?php echo htmlspecialchars($coupon['code']); ?></span></td>
                                    <td><?php echo htmlspecialchars($coupon['description']); ?></td>
                                    <td>
                                        <span class="discount-badge">
                                            <?php if ($coupon['discount_type'] == 'percentage'): ?>
                                                <?php echo $coupon['discount_value']; ?>%
                                            <?php else: ?>
                                                $<?php echo number_format($coupon['discount_value'], 2); ?>
                                            <?php endif; ?>
                                        </span>
                                    </td>
                                    <td>$<?php echo number_format($coupon['minimum_amount'], 2); ?></td>
                                    <td>
                                        <?php echo $coupon['used_count']; ?>
                                        <?php if ($coupon['usage_limit']): ?>
                                            / <?php echo $coupon['usage_limit']; ?>
                                            <br><small>(<?php echo round($usage_percentage); ?>%)</small>
                                        <?php else: ?>
                                            <br><small>Unlimited</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small>
                                            <?php echo date('M j, Y', strtotime($coupon['start_date'])); ?><br>
                                            to<br>
                                            <?php echo date('M j, Y', strtotime($coupon['end_date'])); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <?php if ($is_expired): ?>
                                            <span class="status-badge status-expired">Expired</span>
                                        <?php elseif ($is_not_started): ?>
                                            <span class="status-badge status-inactive">Not Started</span>
                                        <?php elseif ($coupon['status'] == 'active'): ?>
                                            <span class="status-badge status-active">Active</span>
                                        <?php else: ?>
                                            <span class="status-badge status-inactive">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>$<?php echo number_format($coupon['total_discount_given'], 2); ?></td>
                                    <td>
                                        <?php if (!$is_expired): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="coupon_id" value="<?php echo $coupon['id']; ?>">
                                                <input type="hidden" name="new_status" value="<?php echo $coupon['status'] == 'active' ? 'inactive' : 'active'; ?>">
                                                <button type="submit" name="update_status" class="btn <?php echo $coupon['status'] == 'active' ? 'btn-warning' : 'btn-success'; ?>">
                                                    <?php echo $coupon['status'] == 'active' ? 'Deactivate' : 'Activate'; ?>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this coupon?');">
                                            <input type="hidden" name="coupon_id" value="<?php echo $coupon['id']; ?>">
                                            <button type="submit" name="delete_coupon" class="btn btn-danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" style="text-align: center; padding: 40px; color: #666;">
                                    No coupons created yet. Create your first coupon above!
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <?php include 'include/footer.php'; ?>
    
    <script>
        // Auto-generate coupon code
        document.getElementById('code').addEventListener('focus', function() {
            if (this.value === '') {
                const randomCode = 'SAVE' + Math.floor(Math.random() * 100);
                this.value = randomCode;
            }
        });
        
        // Update discount value placeholder based on type
        document.getElementById('discount_type').addEventListener('change', function() {
            const discountValue = document.getElementById('discount_value');
            if (this.value === 'percentage') {
                discountValue.placeholder = 'e.g., 10 (for 10%)';
                discountValue.max = '100';
            } else {
                discountValue.placeholder = 'e.g., 20.00 (for $20)';
                discountValue.removeAttribute('max');
            }
        });
    </script>
</body>
</html>
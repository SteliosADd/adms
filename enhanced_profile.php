<?php
include('include/connect.php');
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $date_of_birth = $_POST['date_of_birth'];
        $gender = $_POST['gender'];
        
        // Validate input
        if (empty($name) || empty($email)) {
            $error = "Name and email are required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email format.";
        } else {
            // Check if email is already taken by another user
            $emailCheck = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $emailCheck->bind_param("si", $email, $user_id);
            $emailCheck->execute();
            
            if ($emailCheck->get_result()->num_rows > 0) {
                $error = "Email is already taken by another user.";
            } else {
                // Update user profile
                $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ?, date_of_birth = ?, gender = ? WHERE id = ?");
                $stmt->bind_param("sssssi", $name, $email, $phone, $date_of_birth, $gender, $user_id);
                
                if ($stmt->execute()) {
                    $_SESSION['user_name'] = $name;
                    $message = "Profile updated successfully!";
                } else {
                    $error = "Failed to update profile.";
                }
            }
        }
    }
    
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error = "All password fields are required.";
        } elseif ($new_password !== $confirm_password) {
            $error = "New passwords do not match.";
        } elseif (strlen($new_password) < 6) {
            $error = "New password must be at least 6 characters long.";
        } else {
            // Verify current password
            $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            
            if (password_verify($current_password, $result['password'])) {
                // Update password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->bind_param("si", $hashed_password, $user_id);
                
                if ($stmt->execute()) {
                    $message = "Password changed successfully!";
                } else {
                    $error = "Failed to change password.";
                }
            } else {
                $error = "Current password is incorrect.";
            }
        }
    }
    
    if (isset($_POST['upload_avatar'])) {
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $max_size = 5 * 1024 * 1024; // 5MB
            
            $file_type = $_FILES['avatar']['type'];
            $file_size = $_FILES['avatar']['size'];
            
            if (!in_array($file_type, $allowed_types)) {
                $error = "Only JPEG, PNG, and GIF images are allowed.";
            } elseif ($file_size > $max_size) {
                $error = "File size must be less than 5MB.";
            } else {
                $upload_dir = 'uploaded_img/';
                $file_extension = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
                $new_filename = 'avatar_' . $user_id . '_' . time() . '.' . $file_extension;
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['avatar']['tmp_name'], $upload_path)) {
                    // Delete old avatar if exists
                    $stmt = $conn->prepare("SELECT avatar FROM users WHERE id = ?");
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $old_avatar = $stmt->get_result()->fetch_assoc()['avatar'];
                    
                    if ($old_avatar && file_exists($upload_dir . $old_avatar)) {
                        unlink($upload_dir . $old_avatar);
                    }
                    
                    // Update database
                    $stmt = $conn->prepare("UPDATE users SET avatar = ? WHERE id = ?");
                    $stmt->bind_param("si", $new_filename, $user_id);
                    
                    if ($stmt->execute()) {
                        $message = "Profile picture updated successfully!";
                    } else {
                        $error = "Failed to update profile picture.";
                    }
                } else {
                    $error = "Failed to upload file.";
                }
            }
        } else {
            $error = "Please select a valid image file.";
        }
    }
    
    if (isset($_POST['add_address'])) {
        $address_type = $_POST['address_type'];
        $street_address = trim($_POST['street_address']);
        $city = trim($_POST['city']);
        $state = trim($_POST['state']);
        $postal_code = trim($_POST['postal_code']);
        $country = trim($_POST['country']);
        $is_default = isset($_POST['is_default']) ? 1 : 0;
        
        if (empty($street_address) || empty($city) || empty($state) || empty($postal_code) || empty($country)) {
            $error = "All address fields are required.";
        } else {
            // If this is set as default, remove default from other addresses
            if ($is_default) {
                $stmt = $conn->prepare("UPDATE user_addresses SET is_default = 0 WHERE user_id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
            }
            
            // Add new address
            $stmt = $conn->prepare("INSERT INTO user_addresses (user_id, address_type, street_address, city, state, postal_code, country, is_default) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssssi", $user_id, $address_type, $street_address, $city, $state, $postal_code, $country, $is_default);
            
            if ($stmt->execute()) {
                $message = "Address added successfully!";
            } else {
                $error = "Failed to add address.";
            }
        }
    }
    
    if (isset($_POST['delete_address'])) {
        $address_id = (int)$_POST['address_id'];
        
        $stmt = $conn->prepare("DELETE FROM user_addresses WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $address_id, $user_id);
        
        if ($stmt->execute()) {
            $message = "Address deleted successfully!";
        } else {
            $error = "Failed to delete address.";
        }
    }
    
    if (isset($_POST['update_preferences'])) {
        $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
        $sms_notifications = isset($_POST['sms_notifications']) ? 1 : 0;
        $newsletter = isset($_POST['newsletter']) ? 1 : 0;
        $order_updates = isset($_POST['order_updates']) ? 1 : 0;
        $promotional_emails = isset($_POST['promotional_emails']) ? 1 : 0;
        
        // Check if preferences record exists
        $stmt = $conn->prepare("SELECT id FROM user_preferences WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $exists = $stmt->get_result()->num_rows > 0;
        
        if ($exists) {
            $stmt = $conn->prepare("UPDATE user_preferences SET email_notifications = ?, sms_notifications = ?, newsletter = ?, order_updates = ?, promotional_emails = ? WHERE user_id = ?");
            $stmt->bind_param("iiiiii", $email_notifications, $sms_notifications, $newsletter, $order_updates, $promotional_emails, $user_id);
        } else {
            $stmt = $conn->prepare("INSERT INTO user_preferences (user_id, email_notifications, sms_notifications, newsletter, order_updates, promotional_emails) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iiiiii", $user_id, $email_notifications, $sms_notifications, $newsletter, $order_updates, $promotional_emails);
        }
        
        if ($stmt->execute()) {
            $message = "Preferences updated successfully!";
        } else {
            $error = "Failed to update preferences.";
        }
    }
}

// Get user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Get user addresses
$stmt = $conn->prepare("SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$addresses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get user preferences
$stmt = $conn->prepare("SELECT * FROM user_preferences WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$preferences = $stmt->get_result()->fetch_assoc();

// Get user statistics
$statsQuery = "SELECT 
    (SELECT COUNT(*) FROM orders WHERE user_id = ?) as total_orders,
    (SELECT SUM(total_price) FROM orders WHERE user_id = ?) as total_spent,
    (SELECT COUNT(*) FROM wishlist WHERE user_id = ?) as wishlist_items,
    (SELECT COUNT(*) FROM product_reviews WHERE user_id = ?) as reviews_written";

$stmt = $conn->prepare($statsQuery);
$stmt->bind_param("iiii", $user_id, $user_id, $user_id, $user_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

// Get recent orders
$stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY order_date DESC LIMIT 5");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recent_orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - SneakZone</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .profile-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .profile-header {
            background: linear-gradient(135deg, #007bff, #28a745);
            color: white;
            padding: 40px;
            border-radius: 20px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }
        
        .profile-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 200%;
            background: rgba(255,255,255,0.1);
            transform: rotate(45deg);
        }
        
        .profile-info {
            display: flex;
            align-items: center;
            gap: 30px;
            position: relative;
            z-index: 1;
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid rgba(255,255,255,0.3);
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
        }
        
        .profile-details h1 {
            margin: 0 0 10px 0;
            font-size: 2.5em;
            font-weight: 700;
        }
        
        .profile-details p {
            margin: 5px 0;
            opacity: 0.9;
            font-size: 1.1em;
        }
        
        .profile-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        
        .stat-item {
            text-align: center;
            background: rgba(255,255,255,0.2);
            padding: 20px;
            border-radius: 15px;
            backdrop-filter: blur(10px);
        }
        
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .profile-tabs {
            display: flex;
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            overflow: hidden;
        }
        
        .tab-btn {
            flex: 1;
            background: none;
            border: none;
            padding: 20px;
            cursor: pointer;
            font-weight: 600;
            color: #666;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .tab-btn.active {
            color: #007bff;
            background: #f8f9fa;
        }
        
        .tab-btn.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: #007bff;
        }
        
        .tab-content {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            padding: 30px;
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0,123,255,0.1);
        }
        
        .btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn:hover {
            background: #0056b3;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,123,255,0.3);
        }
        
        .btn-secondary {
            background: #6c757d;
        }
        
        .btn-secondary:hover {
            background: #545b62;
        }
        
        .btn-danger {
            background: #dc3545;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .avatar-upload {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .avatar-preview {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 auto 20px;
            display: block;
            border: 4px solid #e9ecef;
        }
        
        .file-input-wrapper {
            position: relative;
            display: inline-block;
        }
        
        .file-input {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }
        
        .address-card {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            position: relative;
        }
        
        .address-card.default {
            border-color: #28a745;
            background: #d4edda;
        }
        
        .address-type {
            background: #007bff;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 10px;
        }
        
        .address-type.home {
            background: #28a745;
        }
        
        .address-type.work {
            background: #ffc107;
            color: #333;
        }
        
        .default-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: #28a745;
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .address-actions {
            margin-top: 15px;
            display: flex;
            gap: 10px;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: auto;
            margin: 0;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .recent-orders {
            margin-top: 20px;
        }
        
        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            border: 1px solid #e9ecef;
            border-radius: 10px;
            margin-bottom: 10px;
        }
        
        .order-info {
            flex: 1;
        }
        
        .order-number {
            font-weight: 600;
            color: #007bff;
        }
        
        .order-date {
            color: #666;
            font-size: 14px;
        }
        
        .order-status {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-processing {
            background: #cce5ff;
            color: #004085;
        }
        
        .status-shipped {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .status-delivered {
            background: #d4edda;
            color: #155724;
        }
        
        @media (max-width: 768px) {
            .profile-info {
                flex-direction: column;
                text-align: center;
            }
            
            .profile-tabs {
                flex-direction: column;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .profile-stats {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <?php include('include/header.php'); ?>
    
    <div class="profile-container">
        <!-- Profile Header -->
        <div class="profile-header">
            <div class="profile-info">
                <img src="uploaded_img/<?php echo $user['avatar'] ? htmlspecialchars($user['avatar']) : 'default-avatar.png'; ?>" 
                     alt="Profile Picture" class="profile-avatar" 
                     onerror="this.src='img/sneakers.png'">
                <div class="profile-details">
                    <h1><?php echo htmlspecialchars($user['name']); ?></h1>
                    <p>📧 <?php echo htmlspecialchars($user['email']); ?></p>
                    <?php if ($user['phone']): ?>
                        <p>📱 <?php echo htmlspecialchars($user['phone']); ?></p>
                    <?php endif; ?>
                    <p>📅 Member since <?php echo date('F Y', strtotime($user['created_at'])); ?></p>
                </div>
            </div>
            
            <div class="profile-stats">
                <div class="stat-item">
                    <div class="stat-number"><?php echo number_format($stats['total_orders']); ?></div>
                    <div class="stat-label">Total Orders</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">$<?php echo number_format($stats['total_spent'], 2); ?></div>
                    <div class="stat-label">Total Spent</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo number_format($stats['wishlist_items']); ?></div>
                    <div class="stat-label">Wishlist Items</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo number_format($stats['reviews_written']); ?></div>
                    <div class="stat-label">Reviews Written</div>
                </div>
            </div>
        </div>
        
        <!-- Messages -->
        <?php if ($message): ?>
            <div class="alert alert-success">✅ <?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error">❌ <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <!-- Profile Tabs -->
        <div class="profile-tabs">
            <button class="tab-btn active" onclick="showTab('profile')">👤 Profile Info</button>
            <button class="tab-btn" onclick="showTab('avatar')">📷 Profile Picture</button>
            <button class="tab-btn" onclick="showTab('password')">🔒 Password</button>
            <button class="tab-btn" onclick="showTab('addresses')">📍 Addresses</button>
            <button class="tab-btn" onclick="showTab('preferences')">⚙️ Preferences</button>
            <button class="tab-btn" onclick="showTab('orders')">📦 Recent Orders</button>
        </div>
        
        <!-- Profile Info Tab -->
        <div id="profile-tab" class="tab-content active">
            <h2>📝 Personal Information</h2>
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="name">Full Name *</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="date_of_birth">Date of Birth</label>
                        <input type="date" id="date_of_birth" name="date_of_birth" value="<?php echo $user['date_of_birth']; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="gender">Gender</label>
                        <select id="gender" name="gender">
                            <option value="">Select Gender</option>
                            <option value="male" <?php echo $user['gender'] === 'male' ? 'selected' : ''; ?>>Male</option>
                            <option value="female" <?php echo $user['gender'] === 'female' ? 'selected' : ''; ?>>Female</option>
                            <option value="other" <?php echo $user['gender'] === 'other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                </div>
                
                <button type="submit" name="update_profile" class="btn">
                    💾 Update Profile
                </button>
            </form>
        </div>
        
        <!-- Avatar Tab -->
        <div id="avatar-tab" class="tab-content">
            <h2>📷 Profile Picture</h2>
            <div class="avatar-upload">
                <img src="uploaded_img/<?php echo $user['avatar'] ? htmlspecialchars($user['avatar']) : 'default-avatar.png'; ?>" 
                     alt="Current Avatar" class="avatar-preview" id="avatarPreview"
                     onerror="this.src='img/sneakers.png'">
                
                <form method="POST" enctype="multipart/form-data">
                    <div class="file-input-wrapper">
                        <input type="file" name="avatar" class="file-input" accept="image/*" onchange="previewAvatar(this)">
                        <button type="button" class="btn btn-secondary">
                            📁 Choose New Picture
                        </button>
                    </div>
                    <br><br>
                    <button type="submit" name="upload_avatar" class="btn">
                        📤 Upload Picture
                    </button>
                </form>
                
                <p style="margin-top: 15px; color: #666; font-size: 14px;">
                    Supported formats: JPEG, PNG, GIF (Max 5MB)
                </p>
            </div>
        </div>
        
        <!-- Password Tab -->
        <div id="password-tab" class="tab-content">
            <h2>🔒 Change Password</h2>
            <form method="POST">
                <div class="form-group">
                    <label for="current_password">Current Password *</label>
                    <input type="password" id="current_password" name="current_password" required>
                </div>
                
                <div class="form-group">
                    <label for="new_password">New Password *</label>
                    <input type="password" id="new_password" name="new_password" required minlength="6">
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password *</label>
                    <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
                </div>
                
                <button type="submit" name="change_password" class="btn">
                    🔐 Change Password
                </button>
            </form>
        </div>
        
        <!-- Addresses Tab -->
        <div id="addresses-tab" class="tab-content">
            <h2>📍 Manage Addresses</h2>
            
            <!-- Existing Addresses -->
            <div class="addresses-list">
                <?php foreach ($addresses as $address): ?>
                    <div class="address-card <?php echo $address['is_default'] ? 'default' : ''; ?>">
                        <?php if ($address['is_default']): ?>
                            <div class="default-badge">✅ Default</div>
                        <?php endif; ?>
                        
                        <div class="address-type <?php echo strtolower($address['address_type']); ?>">
                            <?php echo ucfirst($address['address_type']); ?>
                        </div>
                        
                        <div class="address-details">
                            <strong><?php echo htmlspecialchars($address['street_address']); ?></strong><br>
                            <?php echo htmlspecialchars($address['city']); ?>, <?php echo htmlspecialchars($address['state']); ?> <?php echo htmlspecialchars($address['postal_code']); ?><br>
                            <?php echo htmlspecialchars($address['country']); ?>
                        </div>
                        
                        <div class="address-actions">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="address_id" value="<?php echo $address['id']; ?>">
                                <button type="submit" name="delete_address" class="btn btn-danger" 
                                        onclick="return confirm('Delete this address?')">
                                    🗑️ Delete
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Add New Address -->
            <div style="margin-top: 30px; padding-top: 30px; border-top: 2px solid #e9ecef;">
                <h3>➕ Add New Address</h3>
                <form method="POST">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="address_type">Address Type *</label>
                            <select id="address_type" name="address_type" required>
                                <option value="home">🏠 Home</option>
                                <option value="work">🏢 Work</option>
                                <option value="other">📍 Other</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="street_address">Street Address *</label>
                            <input type="text" id="street_address" name="street_address" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="city">City *</label>
                            <input type="text" id="city" name="city" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="state">State/Province *</label>
                            <input type="text" id="state" name="state" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="postal_code">Postal Code *</label>
                            <input type="text" id="postal_code" name="postal_code" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="country">Country *</label>
                            <input type="text" id="country" name="country" required>
                        </div>
                    </div>
                    
                    <div class="checkbox-group">
                        <input type="checkbox" id="is_default" name="is_default">
                        <label for="is_default">Set as default address</label>
                    </div>
                    
                    <button type="submit" name="add_address" class="btn">
                        ➕ Add Address
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Preferences Tab -->
        <div id="preferences-tab" class="tab-content">
            <h2>⚙️ Notification Preferences</h2>
            <form method="POST">
                <div class="checkbox-group">
                    <input type="checkbox" id="email_notifications" name="email_notifications" 
                           <?php echo ($preferences['email_notifications'] ?? 1) ? 'checked' : ''; ?>>
                    <label for="email_notifications">📧 Email Notifications</label>
                </div>
                
                <div class="checkbox-group">
                    <input type="checkbox" id="sms_notifications" name="sms_notifications" 
                           <?php echo ($preferences['sms_notifications'] ?? 0) ? 'checked' : ''; ?>>
                    <label for="sms_notifications">📱 SMS Notifications</label>
                </div>
                
                <div class="checkbox-group">
                    <input type="checkbox" id="newsletter" name="newsletter" 
                           <?php echo ($preferences['newsletter'] ?? 1) ? 'checked' : ''; ?>>
                    <label for="newsletter">📰 Newsletter Subscription</label>
                </div>
                
                <div class="checkbox-group">
                    <input type="checkbox" id="order_updates" name="order_updates" 
                           <?php echo ($preferences['order_updates'] ?? 1) ? 'checked' : ''; ?>>
                    <label for="order_updates">📦 Order Status Updates</label>
                </div>
                
                <div class="checkbox-group">
                    <input type="checkbox" id="promotional_emails" name="promotional_emails" 
                           <?php echo ($preferences['promotional_emails'] ?? 1) ? 'checked' : ''; ?>>
                    <label for="promotional_emails">🎉 Promotional Emails</label>
                </div>
                
                <button type="submit" name="update_preferences" class="btn">
                    💾 Save Preferences
                </button>
            </form>
        </div>
        
        <!-- Recent Orders Tab -->
        <div id="orders-tab" class="tab-content">
            <h2>📦 Recent Orders</h2>
            <div class="recent-orders">
                <?php if (empty($recent_orders)): ?>
                    <p style="text-align: center; color: #666; padding: 40px;">
                        🛒 No orders found. <a href="index.php">Start shopping!</a>
                    </p>
                <?php else: ?>
                    <?php foreach ($recent_orders as $order): ?>
                        <div class="order-item">
                            <div class="order-info">
                                <div class="order-number">Order #<?php echo $order['id']; ?></div>
                                <div class="order-date"><?php echo date('M j, Y', strtotime($order['order_date'])); ?></div>
                            </div>
                            <div class="order-total">$<?php echo number_format($order['total_price'], 2); ?></div>
                            <div class="order-status status-<?php echo $order['status']; ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <div style="text-align: center; margin-top: 20px;">
                        <a href="order_history.php" class="btn btn-secondary">
                            📋 View All Orders
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        function showTab(tabName) {
            // Hide all tab contents
            const tabContents = document.querySelectorAll('.tab-content');
            tabContents.forEach(content => {
                content.classList.remove('active');
            });
            
            // Remove active class from all tab buttons
            const tabBtns = document.querySelectorAll('.tab-btn');
            tabBtns.forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected tab content
            document.getElementById(tabName + '-tab').classList.add('active');
            
            // Add active class to clicked tab button
            event.target.classList.add('active');
        }
        
        function previewAvatar(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('avatarPreview').src = e.target.result;
                };
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (newPassword !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
    
    <?php include('include/footer.php'); ?>
</body>
</html>
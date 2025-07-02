<?php
include('include/connect.php');
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle notification actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['mark_read'])) {
        $notification_id = (int)$_POST['notification_id'];
        $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $notification_id, $user_id);
        $stmt->execute();
        
        echo json_encode(['success' => true]);
        exit();
    }
    
    if (isset($_POST['mark_all_read'])) {
        $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        
        echo json_encode(['success' => true]);
        exit();
    }
    
    if (isset($_POST['delete_notification'])) {
        $notification_id = (int)$_POST['notification_id'];
        $stmt = $conn->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $notification_id, $user_id);
        $stmt->execute();
        
        echo json_encode(['success' => true]);
        exit();
    }
}

// Get notification filter
$filter = $_GET['filter'] ?? 'all';
$page = (int)($_GET['page'] ?? 1);
$limit = 20;
$offset = ($page - 1) * $limit;

// Build query based on filter
$whereClause = "WHERE user_id = ?";
$params = [$user_id];
$types = "i";

if ($filter === 'unread') {
    $whereClause .= " AND is_read = 0";
} elseif ($filter === 'order') {
    $whereClause .= " AND type = 'order'";
} elseif ($filter === 'product') {
    $whereClause .= " AND type = 'product'";
} elseif ($filter === 'system') {
    $whereClause .= " AND type = 'system'";
}

// Get notifications
$notificationsQuery = "SELECT * FROM notifications {$whereClause} ORDER BY created_at DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($notificationsQuery);
$types .= "ii";
$params[] = $limit;
$params[] = $offset;
$stmt->bind_param($types, ...$params);
$stmt->execute();
$notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get total count for pagination
$countQuery = "SELECT COUNT(*) as total FROM notifications {$whereClause}";
$stmt = $conn->prepare($countQuery);
$stmt->bind_param(substr($types, 0, -2), ...array_slice($params, 0, -2));
$stmt->execute();
$totalNotifications = $stmt->get_result()->fetch_assoc()['total'];
$totalPages = ceil($totalNotifications / $limit);

// Get unread count
$unreadQuery = "SELECT COUNT(*) as unread FROM notifications WHERE user_id = ? AND is_read = 0";
$stmt = $conn->prepare($unreadQuery);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$unreadCount = $stmt->get_result()->fetch_assoc()['unread'];

// Get notification statistics
$statsQuery = "SELECT 
    type,
    COUNT(*) as count,
    SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread_count
    FROM notifications 
    WHERE user_id = ? 
    GROUP BY type";
$stmt = $conn->prepare($statsQuery);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Function to get notification icon
function getNotificationIcon($type) {
    switch ($type) {
        case 'order':
            return '📦';
        case 'product':
            return '👟';
        case 'system':
            return '🔔';
        case 'promotion':
            return '🎉';
        default:
            return '📢';
    }
}

// Function to format time ago
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' minutes ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    if ($time < 31536000) return floor($time/2592000) . ' months ago';
    return floor($time/31536000) . ' years ago';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - SneakZone</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .notifications-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .notifications-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .notifications-title {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .notifications-title h1 {
            margin: 0;
            color: #333;
            font-size: 2em;
        }
        
        .unread-badge {
            background: #dc3545;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
        }
        
        .notifications-actions {
            display: flex;
            gap: 10px;
        }
        
        .action-btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .action-btn:hover {
            background: #0056b3;
            transform: translateY(-2px);
        }
        
        .action-btn.secondary {
            background: #6c757d;
        }
        
        .action-btn.secondary:hover {
            background: #545b62;
        }
        
        .notifications-filters {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            flex-wrap: wrap;
        }
        
        .filter-btn {
            background: #f8f9fa;
            border: 2px solid #dee2e6;
            color: #495057;
            padding: 10px 20px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .filter-btn:hover {
            background: #e9ecef;
            border-color: #adb5bd;
            transform: translateY(-2px);
        }
        
        .filter-btn.active {
            background: #007bff;
            border-color: #007bff;
            color: white;
        }
        
        .filter-count {
            background: rgba(255,255,255,0.3);
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
        }
        
        .notifications-list {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .notification-item {
            display: flex;
            align-items: flex-start;
            padding: 20px;
            border-bottom: 1px solid #eee;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .notification-item:last-child {
            border-bottom: none;
        }
        
        .notification-item:hover {
            background: #f8f9fa;
        }
        
        .notification-item.unread {
            background: linear-gradient(90deg, rgba(0,123,255,0.05), rgba(255,255,255,1));
            border-left: 4px solid #007bff;
        }
        
        .notification-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            margin-right: 15px;
            flex-shrink: 0;
        }
        
        .notification-icon.order {
            background: #e3f2fd;
            color: #1976d2;
        }
        
        .notification-icon.product {
            background: #e8f5e8;
            color: #388e3c;
        }
        
        .notification-icon.system {
            background: #fff3e0;
            color: #f57c00;
        }
        
        .notification-icon.promotion {
            background: #f3e5f5;
            color: #7b1fa2;
        }
        
        .notification-content {
            flex: 1;
        }
        
        .notification-title {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
            font-size: 16px;
        }
        
        .notification-message {
            color: #666;
            line-height: 1.5;
            margin-bottom: 8px;
        }
        
        .notification-meta {
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 14px;
            color: #999;
        }
        
        .notification-time {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .notification-actions {
            display: flex;
            gap: 10px;
            margin-left: 15px;
        }
        
        .notification-btn {
            background: none;
            border: 1px solid #ddd;
            color: #666;
            padding: 8px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.3s ease;
        }
        
        .notification-btn:hover {
            background: #f8f9fa;
            border-color: #adb5bd;
        }
        
        .notification-btn.primary {
            background: #007bff;
            border-color: #007bff;
            color: white;
        }
        
        .notification-btn.primary:hover {
            background: #0056b3;
        }
        
        .notification-btn.danger {
            color: #dc3545;
            border-color: #dc3545;
        }
        
        .notification-btn.danger:hover {
            background: #dc3545;
            color: white;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        
        .empty-state-icon {
            font-size: 4em;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        .empty-state h3 {
            margin-bottom: 10px;
            color: #333;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 30px;
            padding: 20px;
        }
        
        .pagination a,
        .pagination span {
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            text-decoration: none;
            color: #333;
            transition: all 0.3s ease;
        }
        
        .pagination a:hover {
            background: #007bff;
            color: white;
            border-color: #007bff;
        }
        
        .pagination .current {
            background: #007bff;
            color: white;
            border-color: #007bff;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #666;
            font-size: 14px;
            text-transform: uppercase;
            font-weight: 600;
        }
        
        @media (max-width: 768px) {
            .notifications-header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .notifications-filters {
                justify-content: center;
            }
            
            .notification-item {
                flex-direction: column;
                gap: 15px;
            }
            
            .notification-actions {
                margin-left: 0;
                justify-content: center;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <?php include('include/header.php'); ?>
    
    <div class="notifications-container">
        <div class="notifications-header">
            <div class="notifications-title">
                <h1>🔔 Notifications</h1>
                <?php if ($unreadCount > 0): ?>
                    <span class="unread-badge"><?php echo $unreadCount; ?> unread</span>
                <?php endif; ?>
            </div>
            <div class="notifications-actions">
                <?php if ($unreadCount > 0): ?>
                    <button class="action-btn" onclick="markAllRead()">Mark All Read</button>
                <?php endif; ?>
                <a href="customer_dashboard.php" class="action-btn secondary">Back to Dashboard</a>
            </div>
        </div>
        
        <!-- Statistics -->
        <div class="stats-grid">
            <?php foreach ($stats as $stat): ?>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stat['count']; ?></div>
                    <div class="stat-label"><?php echo ucfirst($stat['type']); ?> Notifications</div>
                    <?php if ($stat['unread_count'] > 0): ?>
                        <div style="color: #dc3545; font-size: 12px; margin-top: 5px;">
                            <?php echo $stat['unread_count']; ?> unread
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Filters -->
        <div class="notifications-filters">
            <a href="?filter=all" class="filter-btn <?php echo $filter === 'all' ? 'active' : ''; ?>">
                📋 All
                <span class="filter-count"><?php echo $totalNotifications; ?></span>
            </a>
            <a href="?filter=unread" class="filter-btn <?php echo $filter === 'unread' ? 'active' : ''; ?>">
                🔴 Unread
                <span class="filter-count"><?php echo $unreadCount; ?></span>
            </a>
            <a href="?filter=order" class="filter-btn <?php echo $filter === 'order' ? 'active' : ''; ?>">
                📦 Orders
            </a>
            <a href="?filter=product" class="filter-btn <?php echo $filter === 'product' ? 'active' : ''; ?>">
                👟 Products
            </a>
            <a href="?filter=system" class="filter-btn <?php echo $filter === 'system' ? 'active' : ''; ?>">
                🔔 System
            </a>
        </div>
        
        <!-- Notifications List -->
        <div class="notifications-list">
            <?php if (empty($notifications)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">🔔</div>
                    <h3>No notifications found</h3>
                    <p>You're all caught up! Check back later for updates.</p>
                </div>
            <?php else: ?>
                <?php foreach ($notifications as $notification): ?>
                    <div class="notification-item <?php echo $notification['is_read'] ? '' : 'unread'; ?>" id="notification-<?php echo $notification['id']; ?>">
                        <div class="notification-icon <?php echo $notification['type']; ?>">
                            <?php echo getNotificationIcon($notification['type']); ?>
                        </div>
                        <div class="notification-content">
                            <div class="notification-title"><?php echo htmlspecialchars($notification['title']); ?></div>
                            <div class="notification-message"><?php echo htmlspecialchars($notification['message']); ?></div>
                            <div class="notification-meta">
                                <div class="notification-time">
                                    🕒 <?php echo timeAgo($notification['created_at']); ?>
                                </div>
                                <div class="notification-type">
                                    📂 <?php echo ucfirst($notification['type']); ?>
                                </div>
                            </div>
                        </div>
                        <div class="notification-actions">
                            <?php if (!$notification['is_read']): ?>
                                <button class="notification-btn primary" onclick="markAsRead(<?php echo $notification['id']; ?>)">
                                    ✓ Mark Read
                                </button>
                            <?php endif; ?>
                            <button class="notification-btn danger" onclick="deleteNotification(<?php echo $notification['id']; ?>)">
                                🗑️ Delete
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?filter=<?php echo $filter; ?>&page=<?php echo $page - 1; ?>">← Previous</a>
                <?php endif; ?>
                
                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                    <?php if ($i === $page): ?>
                        <span class="current"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="?filter=<?php echo $filter; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                    <a href="?filter=<?php echo $filter; ?>&page=<?php echo $page + 1; ?>">Next →</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        function markAsRead(notificationId) {
            fetch('notification_system.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `mark_read=1&notification_id=${notificationId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const notification = document.getElementById(`notification-${notificationId}`);
                    notification.classList.remove('unread');
                    
                    // Remove the mark read button
                    const markReadBtn = notification.querySelector('.notification-btn.primary');
                    if (markReadBtn) {
                        markReadBtn.remove();
                    }
                    
                    // Update unread count
                    updateUnreadCount();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to mark notification as read');
            });
        }
        
        function markAllRead() {
            if (!confirm('Mark all notifications as read?')) return;
            
            fetch('notification_system.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'mark_all_read=1'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to mark all notifications as read');
            });
        }
        
        function deleteNotification(notificationId) {
            if (!confirm('Delete this notification?')) return;
            
            fetch('notification_system.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `delete_notification=1&notification_id=${notificationId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const notification = document.getElementById(`notification-${notificationId}`);
                    notification.style.animation = 'fadeOut 0.3s ease';
                    setTimeout(() => {
                        notification.remove();
                        
                        // Check if no notifications left
                        const remainingNotifications = document.querySelectorAll('.notification-item');
                        if (remainingNotifications.length === 0) {
                            location.reload();
                        }
                    }, 300);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to delete notification');
            });
        }
        
        function updateUnreadCount() {
            const unreadNotifications = document.querySelectorAll('.notification-item.unread');
            const unreadBadge = document.querySelector('.unread-badge');
            const markAllBtn = document.querySelector('.action-btn');
            
            if (unreadNotifications.length === 0) {
                if (unreadBadge) unreadBadge.remove();
                if (markAllBtn && markAllBtn.textContent.includes('Mark All')) {
                    markAllBtn.remove();
                }
            }
        }
        
        // Add fade out animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes fadeOut {
                from { opacity: 1; transform: translateX(0); }
                to { opacity: 0; transform: translateX(-20px); }
            }
        `;
        document.head.appendChild(style);
    </script>
    
    <?php include('include/footer.php'); ?>
</body>
</html>
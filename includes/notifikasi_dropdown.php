<?php
// includes/notification_dropdown.php
// Component untuk menampilkan dropdown notifikasi di navbar
// Include file ini di header setiap halaman user/admin

if (!isset($_SESSION['user_id'])) {
    return;
}

$db = getDB();
$user_id = $_SESSION['user_id'];

// Get unread count
$unreadStmt = $db->prepare("SELECT COUNT(*) as count FROM notifikasi WHERE user_id = ? AND status = 'unread'");
$unreadStmt->execute([$user_id]);
$unreadCount = $unreadStmt->fetch()['count'];

// Get recent notifications (5 latest)
$notifStmt = $db->prepare("
    SELECT n.*, b.tanggal_booking 
    FROM notifikasi n
    LEFT JOIN booking b ON n.booking_id = b.id
    WHERE n.user_id = ?
    ORDER BY n.created_at DESC
    LIMIT 5
");
$notifStmt->execute([$user_id]);
$recentNotifs = $notifStmt->fetchAll();
?>

<style>
.notification-bell {
    position: relative;
    cursor: pointer;
    padding: 0.5rem;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.notification-bell:hover {
    background: var(--soft-blue);
}

.notification-badge {
    position: absolute;
    top: 0;
    right: 0;
    background: #dc3545;
    color: white;
    border-radius: 10px;
    padding: 0.2rem 0.5rem;
    font-size: 0.7rem;
    font-weight: 700;
    min-width: 20px;
    text-align: center;
}

.notification-dropdown {
    position: absolute;
    top: 100%;
    right: 0;
    margin-top: 0.5rem;
    width: 380px;
    max-height: 500px;
    overflow-y: auto;
    background: white;
    border-radius: 15px;
    box-shadow: 0 10px 40px rgba(107, 163, 215, 0.2);
    display: none;
    z-index: 1000;
}

.notification-dropdown.show {
    display: block;
    animation: slideDown 0.3s ease;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.notification-header {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.notification-header h6 {
    margin: 0;
    font-weight: 700;
    color: var(--dark-blue);
}

.notification-body {
    max-height: 350px;
    overflow-y: auto;
}

.notif-item-dropdown {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #f0f0f0;
    transition: all 0.3s ease;
    cursor: pointer;
}

.notif-item-dropdown:hover {
    background: var(--soft-blue);
}

.notif-item-dropdown.unread {
    background: #f8f9fa;
    border-left: 3px solid var(--primary-blue);
}

.notif-item-dropdown:last-child {
    border-bottom: none;
}

.notif-item-title {
    font-weight: 600;
    color: var(--dark-blue);
    font-size: 0.9rem;
    margin-bottom: 0.3rem;
}

.notif-item-text {
    font-size: 0.85rem;
    color: #6c757d;
    margin-bottom: 0.3rem;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.notif-item-time {
    font-size: 0.75rem;
    color: #adb5bd;
}

.notification-footer {
    padding: 0.8rem 1.5rem;
    border-top: 1px solid #e9ecef;
    text-align: center;
}

.notification-footer a {
    color: var(--primary-blue);
    text-decoration: none;
    font-weight: 600;
    font-size: 0.9rem;
}

.notification-footer a:hover {
    color: var(--dark-blue);
}

.empty-notif {
    padding: 2rem;
    text-align: center;
    color: #adb5bd;
}

.empty-notif i {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.3;
}
</style>

<div style="position: relative;">
    <div class="notification-bell" id="notificationBell">
        <i class="fas fa-bell fa-lg" style="color: var(--dark-blue);"></i>
        <?php if ($unreadCount > 0): ?>
            <span class="notification-badge" id="notificationBadge"><?= $unreadCount > 9 ? '9+' : $unreadCount ?></span>
        <?php endif; ?>
    </div>
    
    <div class="notification-dropdown" id="notificationDropdown">
        <div class="notification-header">
            <h6><i class="fas fa-bell me-2"></i>Notifikasi</h6>
            <?php if ($unreadCount > 0): ?>
                <button class="btn btn-sm btn-link" onclick="markAllAsRead()" style="color: var(--primary-blue); text-decoration: none; font-size: 0.85rem;">
                    <i class="fas fa-check-double me-1"></i>Tandai Semua
                </button>
            <?php endif; ?>
        </div>
        
        <div class="notification-body" id="notificationBody">
            <?php if (count($recentNotifs) > 0): ?>
                <?php foreach ($recentNotifs as $notif): ?>
                <div class="notif-item-dropdown <?= $notif['status'] === 'unread' ? 'unread' : '' ?>" 
                     onclick="openNotification(<?= $notif['id'] ?>)">
                    <div class="notif-item-title">
                        <?= htmlspecialchars($notif['judul']) ?>
                        <?php if ($notif['status'] === 'unread'): ?>
                            <i class="fas fa-circle ms-1" style="font-size: 0.5rem; color: #dc3545;"></i>
                        <?php endif; ?>
                    </div>
                    <div class="notif-item-text"><?= htmlspecialchars($notif['pesan']) ?></div>
                    <div class="notif-item-time">
                        <i class="fas fa-clock me-1"></i><?= timeAgo($notif['created_at']) ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-notif">
                    <i class="fas fa-bell-slash"></i>
                    <p>Tidak ada notifikasi</p>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="notification-footer">
            <a href="<?= $_SESSION['role'] === 'admin' ? '../user/notifikasi.php' : 'notifikasi.php' ?>">
                Lihat Semua Notifikasi
            </a>
        </div>
    </div>
</div>

<script>
// Toggle notification dropdown
document.getElementById('notificationBell').addEventListener('click', function(e) {
    e.stopPropagation();
    const dropdown = document.getElementById('notificationDropdown');
    dropdown.classList.toggle('show');
    
    // Reload notifications when opened
    if (dropdown.classList.contains('show')) {
        loadNotifications();
    }
});

// Close dropdown when clicking outside
document.addEventListener('click', function(e) {
    const dropdown = document.getElementById('notificationDropdown');
    const bell = document.getElementById('notificationBell');
    
    if (!dropdown.contains(e.target) && !bell.contains(e.target)) {
        dropdown.classList.remove('show');
    }
});

// Load notifications via AJAX
function loadNotifications() {
    fetch('../api/notifikasi.php?action=get&limit=5')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                updateNotificationList(data.data);
            }
        });
    
    // Update unread count
    fetch('../api/notifikasi.php?action=unread_count')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                updateUnreadBadge(data.count);
            }
        });
}

// Update notification list
function updateNotificationList(notifications) {
    const body = document.getElementById('notificationBody');
    
    if (notifications.length === 0) {
        body.innerHTML = `
            <div class="empty-notif">
                <i class="fas fa-bell-slash"></i>
                <p>Tidak ada notifikasi</p>
            </div>
        `;
        return;
    }
    
    let html = '';
    notifications.forEach(notif => {
        html += `
            <div class="notif-item-dropdown ${notif.status === 'unread' ? 'unread' : ''}" 
                 onclick="openNotification(${notif.id})">
                <div class="notif-item-title">
                    ${notif.judul}
                    ${notif.status === 'unread' ? '<i class="fas fa-circle ms-1" style="font-size: 0.5rem; color: #dc3545;"></i>' : ''}
                </div>
                <div class="notif-item-text">${notif.pesan}</div>
                <div class="notif-item-time">
                    <i class="fas fa-clock me-1"></i>${notif.time_ago}
                </div>
            </div>
        `;
    });
    
    body.innerHTML = html;
}

// Update unread badge
function updateUnreadBadge(count) {
    const badge = document.getElementById('notificationBadge');
    
    if (count > 0) {
        if (badge) {
            badge.textContent = count > 9 ? '9+' : count;
        } else {
            const bell = document.getElementById('notificationBell');
            const newBadge = document.createElement('span');
            newBadge.className = 'notification-badge';
            newBadge.id = 'notificationBadge';
            newBadge.textContent = count > 9 ? '9+' : count;
            bell.appendChild(newBadge);
        }
    } else {
        if (badge) {
            badge.remove();
        }
    }
}

// Open notification
function openNotification(id) {
    // Mark as read
    fetch('../api/notifikasi.php?action=mark_read', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'notif_id=' + id
    })
    .then(() => {
        // Redirect to notification page
        window.location.href = '<?= $_SESSION['role'] === 'admin' ? '../user/notifikasi.php' : 'notifikasi.php' ?>';
    });
}

// Mark all as read
function markAllAsRead() {
    fetch('../api/notifikasi.php?action=mark_all_read', {
        method: 'POST'
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            loadNotifications();
        }
    });
}

// Auto refresh every 30 seconds
setInterval(function() {
    fetch('../api/notifikasi.php?action=unread_count')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                updateUnreadBadge(data.count);
            }
        });
}, 30000);

<?php
function timeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $difference = time() - $timestamp;
    
    if ($difference < 60) {
        return 'Baru saja';
    } elseif ($difference < 3600) {
        $minutes = floor($difference / 60);
        return $minutes . ' menit lalu';
    } elseif ($difference < 86400) {
        $hours = floor($difference / 3600);
        return $hours . ' jam lalu';
    } elseif ($difference < 604800) {
        $days = floor($difference / 86400);
        return $days . ' hari lalu';
    } else {
        return date('d M Y', $timestamp);
    }
}
?>
</script>
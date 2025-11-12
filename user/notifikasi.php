<?php
// user/notifikasi.php
require_once '../includes/functions.php';
requireLogin();

$db = getDB();
$user_id = $_SESSION['user_id'];

// Handle mark as read action
if (isset($_GET['mark_read']) && isset($_GET['id'])) {
    $notif_id = (int)$_GET['id'];
    $stmt = $db->prepare("UPDATE notifikasi SET status = 'read' WHERE id = ? AND user_id = ?");
    $stmt->execute([$notif_id, $user_id]);
    header('Location: notifikasi.php');
    exit();
}

// Get all notifications
$stmt = $db->prepare("
    SELECT n.*, b.tanggal_booking, b.jam_mulai, b.jam_selesai, b.status_booking,
           r.nama_ruangan, r.kode_ruangan
    FROM notifikasi n
    LEFT JOIN booking b ON n.booking_id = b.id
    LEFT JOIN ruangan r ON b.ruangan_id = r.id
    WHERE n.user_id = ?
    ORDER BY n.created_at DESC
");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifikasi - Booking UCA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-blue: #6BA3D7;
            --light-blue: #A8D0F0;
            --soft-blue: #E8F4F8;
            --dark-blue: #4A7BA7;
            --sidebar-width: 260px;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--soft-blue);
        }
        
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background: white;
            box-shadow: 4px 0 20px rgba(107, 163, 215, 0.1);
            z-index: 1000;
        }
        
        .sidebar-header {
            padding: 1.5rem;
            background: linear-gradient(135deg, var(--primary-blue), var(--light-blue));
            color: white;
        }
        
        .sidebar-brand {
            font-size: 1.5rem;
            font-weight: 800;
            text-decoration: none;
            color: white;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .sidebar-menu { padding: 1rem 0; }
        
        .menu-item {
            padding: 0.9rem 1.5rem;
            color: var(--dark-blue);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }
        
        .menu-item:hover, .menu-item.active {
            background: var(--soft-blue);
            color: var(--primary-blue);
            border-left-color: var(--primary-blue);
        }
        
        .menu-item i { width: 20px; font-size: 1.1rem; }
        
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 2rem;
            min-height: 100vh;
        }
        
        .topbar {
            background: white;
            border-radius: 15px;
            padding: 1rem 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(107, 163, 215, 0.08);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .topbar-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--dark-blue);
        }
        
        .card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(107, 163, 215, 0.08);
            border: none;
            margin-bottom: 2rem;
        }
        
        .card-header {
            background: transparent;
            border-bottom: 1px solid #f0f0f0;
            padding: 1.5rem;
            font-weight: 700;
            color: var(--dark-blue);
            display: flex;
            justify-content: between;
            align-items: center;
        }
        
        .card-body { padding: 1.5rem; }
        
        .notif-item {
            border-left: 4px solid var(--primary-blue);
            background: var(--soft-blue);
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }
        
        .notif-item:hover {
            box-shadow: 0 4px 15px rgba(107, 163, 215, 0.15);
            transform: translateX(5px);
        }
        
        .notif-item.unread {
            background: white;
            border-left-color: var(--primary-blue);
            box-shadow: 0 2px 10px rgba(107, 163, 215, 0.1);
        }
        
        .notif-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-right: 1rem;
        }
        
        .notif-icon.success {
            background: linear-gradient(135deg, #5DD39E, #BCE784);
            color: white;
        }
        
        .notif-icon.warning {
            background: linear-gradient(135deg, #FFB547, #FFCB80);
            color: white;
        }
        
        .notif-icon.info {
            background: linear-gradient(135deg, #6BA3D7, #A8D0F0);
            color: white;
        }
        
        .notif-icon.danger {
            background: linear-gradient(135deg, #E76F6F, #F09898);
            color: white;
        }
        
        .notif-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--dark-blue);
            margin-bottom: 0.5rem;
        }
        
        .notif-text {
            color: #6c757d;
            margin-bottom: 0.5rem;
            line-height: 1.6;
        }
        
        .notif-time {
            font-size: 0.85rem;
            color: #adb5bd;
        }
        
        .notif-actions {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #e9ecef;
        }
        
        .btn-sm {
            padding: 0.4rem 1rem;
            font-size: 0.85rem;
            border-radius: 6px;
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem 0;
            color: #6c757d;
        }
        
        .empty-state i {
            font-size: 5rem;
            opacity: 0.3;
            margin-bottom: 1rem;
        }
        
        .badge-new {
            background: linear-gradient(135deg, #E76F6F, #F09898);
            color: white;
            padding: 0.3rem 0.6rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <a href="#" class="sidebar-brand">
                <i class="fas fa-building"></i>
                <span>Booking UCA</span>
            </a>
        </div>
        <div class="sidebar-menu">
            <a href="index.php" class="menu-item">
                <i class="fas fa-home"></i><span>Dashboard</span>
            </a>
            <a href="booking.php" class="menu-item">
                <i class="fas fa-plus-circle"></i><span>Booking Baru</span>
            </a>
            <a href="riwayat.php" class="menu-item">
                <i class="fas fa-history"></i><span>Riwayat Booking</span>
            </a>
            <a href="notifikasi.php" class="menu-item active">
                <i class="fas fa-bell"></i><span>Notifikasi</span>
            </a>
            <a href="profil.php" class="menu-item">
                <i class="fas fa-user"></i><span>Profil Saya</span>
            </a>
            <hr>
            <a href="../auth/logout.php" class="menu-item">
                <i class="fas fa-sign-out-alt"></i><span>Logout</span>
            </a>
        </div>
    </div>
    
    <div class="main-content">
        <div class="topbar">
            <h1 class="topbar-title"><i class="fas fa-bell me-2"></i>Notifikasi</h1>
            <div>
                <button class="btn btn-outline-primary btn-sm me-2" onclick="markAllAsRead()">
                    <i class="fas fa-check-double me-1"></i>Tandai Semua Dibaca
                </button>
                <button class="btn btn-outline-danger btn-sm" onclick="deleteAllRead()">
                    <i class="fas fa-trash me-1"></i>Hapus yang Sudah Dibaca
                </button>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <span><i class="fas fa-list me-2"></i>Semua Notifikasi</span>
            </div>
            <div class="card-body">
                <?php if (count($notifications) > 0): ?>
                    <?php foreach ($notifications as $notif): ?>
                    <div class="notif-item <?= $notif['status'] === 'unread' ? 'unread' : '' ?>" id="notif-<?= $notif['id'] ?>">
                        <div class="d-flex">
                            <?php
                            $iconClass = 'info';
                            $icon = 'fa-info-circle';
                            
                            if (strpos(strtolower($notif['judul']), 'disetujui') !== false || 
                                strpos(strtolower($notif['judul']), 'approved') !== false) {
                                $iconClass = 'success';
                                $icon = 'fa-check-circle';
                            } elseif (strpos(strtolower($notif['judul']), 'ditolak') !== false || 
                                      strpos(strtolower($notif['judul']), 'rejected') !== false) {
                                $iconClass = 'danger';
                                $icon = 'fa-times-circle';
                            } elseif (strpos(strtolower($notif['judul']), 'pending') !== false) {
                                $iconClass = 'warning';
                                $icon = 'fa-clock';
                            }
                            ?>
                            <div class="notif-icon <?= $iconClass ?>">
                                <i class="fas <?= $icon ?>"></i>
                            </div>
                            <div class="flex-grow-1">
                                                                    <div class="d-flex justify-content-between align-items-start">
                                    <div class="notif-title">
                                        <?= htmlspecialchars($notif['judul']) ?>
                                        <?php if ($notif['status'] === 'unread'): ?>
                                            <span class="badge-new ms-2">BARU</span>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <?php if ($notif['status'] === 'unread'): ?>
                                            <button class="btn btn-sm btn-outline-success me-1" onclick="markAsRead(<?= $notif['id'] ?>)" title="Tandai sudah dibaca">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        <?php endif; ?>
                                        <button class="btn btn-sm btn-link text-danger" onclick="deleteNotif(<?= $notif['id'] ?>)" title="Hapus notifikasi">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="notif-text"><?= nl2br(htmlspecialchars($notif['pesan'])) ?></div>
                                <div class="notif-time">
                                    <i class="fas fa-clock me-1"></i><?= date('d M Y, H:i', strtotime($notif['created_at'])) ?>
                                </div>
                                
                                <?php if ($notif['booking_id']): ?>
                                <div class="notif-actions">
                                    <div class="row align-items-center">
                                        <div class="col-md-8">
                                            <small class="text-muted">
                                                <i class="fas fa-door-open me-1"></i><?= htmlspecialchars($notif['nama_ruangan'] ?? '-') ?>
                                                | <i class="fas fa-calendar me-1"></i><?= $notif['tanggal_booking'] ? date('d M Y', strtotime($notif['tanggal_booking'])) : '-' ?>
                                                | <i class="fas fa-clock me-1"></i><?= $notif['jam_mulai'] ?? '-' ?> - <?= $notif['jam_selesai'] ?? '-' ?>
                                            </small>
                                        </div>
                                        <div class="col-md-4 text-end">
                                            <a href="riwayat.php" class="btn btn-sm btn-primary">
                                                <i class="fas fa-eye me-1"></i>Lihat Detail
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-bell-slash"></i>
                        <h4>Belum Ada Notifikasi</h4>
                        <p class="text-muted">Anda belum memiliki notifikasi</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function markAllAsRead() {
            if (confirm('Tandai semua notifikasi sebagai sudah dibaca?')) {
                fetch('../api/notifikasi.php?action=mark_all_read', {
                    method: 'POST'
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Gagal menandai notifikasi');
                    }
                })
                .catch(err => {
                    console.error('Error:', err);
                    alert('Terjadi kesalahan');
                });
            }
        }
        
        function deleteNotif(id) {
            if (confirm('Hapus notifikasi ini?')) {
                fetch('../api/notifikasi.php?action=delete', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'notif_id=' + id
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('notif-' + id).remove();
                        
                        // Check if empty
                        const notifContainer = document.querySelector('.card-body');
                        const notifItems = notifContainer.querySelectorAll('.notif-item');
                        
                        if (notifItems.length === 0) {
                            notifContainer.innerHTML = `
                                <div class="empty-state">
                                    <i class="fas fa-bell-slash"></i>
                                    <h4>Belum Ada Notifikasi</h4>
                                    <p class="text-muted">Anda belum memiliki notifikasi</p>
                                </div>
                            `;
                        }
                    } else {
                        alert('Gagal menghapus notifikasi');
                    }
                })
                .catch(err => {
                    console.error('Error:', err);
                    alert('Terjadi kesalahan');
                });
            }
        }
        
        function deleteAllRead() {
            if (confirm('Hapus semua notifikasi yang sudah dibaca?')) {
                fetch('../api/notifikasi.php?action=delete_all', {
                    method: 'POST'
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Gagal menghapus notifikasi');
                    }
                })
                .catch(err => {
                    console.error('Error:', err);
                    alert('Terjadi kesalahan');
                });
            }
        }
    </script>
</body>
</html>
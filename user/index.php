<?php
// user/index.php
require_once '../includes/functions.php';
requireLogin();

$db = getDB();
$user_id = $_SESSION['user_id'];

// Get user statistics
$totalBooking = $db->prepare("SELECT COUNT(*) as total FROM booking WHERE user_id = ?");
$totalBooking->execute([$user_id]);
$totalBooking = $totalBooking->fetch()['total'];

$bookingAktif = $db->prepare("SELECT COUNT(*) as total FROM booking WHERE user_id = ? AND status_booking IN ('pending', 'approved')");
$bookingAktif->execute([$user_id]);
$bookingAktif = $bookingAktif->fetch()['total'];

$bookingPending = $db->prepare("SELECT COUNT(*) as total FROM booking WHERE user_id = ? AND status_booking = 'pending'");
$bookingPending->execute([$user_id]);
$bookingPending = $bookingPending->fetch()['total'];

// Get unread notifications count
$unreadNotif = $db->prepare("SELECT COUNT(*) as total FROM notifikasi WHERE user_id = ? AND status = 'unread'");
$unreadNotif->execute([$user_id]);
$unreadNotif = $unreadNotif->fetch()['total'];

// Get upcoming bookings
$upcomingBookings = $db->prepare("
    SELECT b.*, r.nama_ruangan, r.kode_ruangan, g.nama_gedung
    FROM booking b
    JOIN ruangan r ON b.ruangan_id = r.id
    JOIN gedung g ON r.gedung_id = g.id
    WHERE b.user_id = ? 
    AND b.tanggal_booking >= CURDATE()
    AND b.status_booking IN ('pending', 'approved')
    ORDER BY b.tanggal_booking ASC, b.jam_mulai ASC
    LIMIT 5
");
$upcomingBookings->execute([$user_id]);
$upcomingBookings = $upcomingBookings->fetchAll();

// Get recent notifications (3 latest)
$recentNotifs = $db->prepare("
    SELECT n.*, b.tanggal_booking, b.jam_mulai 
    FROM notifikasi n
    LEFT JOIN booking b ON n.booking_id = b.id
    WHERE n.user_id = ?
    ORDER BY n.created_at DESC
    LIMIT 3
");
$recentNotifs->execute([$user_id]);
$recentNotifs = $recentNotifs->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Booking UCA</title>
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
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
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
        
        .sidebar-menu {
            padding: 1rem 0;
        }
        
        .menu-item {
            padding: 0.9rem 1.5rem;
            color: var(--dark-blue);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
            position: relative;
        }
        
        .menu-item:hover,
        .menu-item.active {
            background: var(--soft-blue);
            color: var(--primary-blue);
            border-left-color: var(--primary-blue);
        }
        
        .menu-item i {
            width: 20px;
            font-size: 1.1rem;
        }
        
        .menu-badge {
            position: absolute;
            right: 1rem;
            background: #dc3545;
            color: white;
            border-radius: 10px;
            padding: 0.2rem 0.5rem;
            font-size: 0.7rem;
            font-weight: 700;
        }
        
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
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-blue), var(--light-blue));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }
        
        .welcome-card {
            background: linear-gradient(135deg, var(--primary-blue), var(--light-blue));
            border-radius: 20px;
            padding: 2rem;
            color: white;
            margin-bottom: 2rem;
            box-shadow: 0 8px 25px rgba(107, 163, 215, 0.3);
        }
        
        .welcome-title {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .welcome-text {
            opacity: 0.95;
            margin-bottom: 1.5rem;
        }
        
        .btn-booking {
            background: white;
            color: var(--primary-blue);
            border: none;
            padding: 0.8rem 2rem;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-booking:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 255, 255, 0.3);
            color: var(--dark-blue);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(107, 163, 215, 0.08);
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 20px rgba(107, 163, 215, 0.15);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .stat-icon.blue {
            background: linear-gradient(135deg, #6BA3D7, #A8D0F0);
            color: white;
        }
        
        .stat-icon.green {
            background: linear-gradient(135deg, #5DD39E, #BCE784);
            color: white;
        }
        
        .stat-icon.orange {
            background: linear-gradient(135deg, #FFB547, #FFCB80);
            color: white;
        }
        
        .stat-icon.red {
            background: linear-gradient(135deg, #E76F6F, #F09898);
            color: white;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 800;
            color: var(--dark-blue);
            margin-bottom: 0.3rem;
        }
        
        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
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
        
        .card-body {
            padding: 1.5rem;
        }
        
        .booking-item {
            border-bottom: 1px solid #f0f0f0;
            padding: 1rem 0;
        }
        
        .booking-item:last-child {
            border-bottom: none;
        }
        
        .booking-title {
            font-weight: 600;
            color: var(--dark-blue);
            margin-bottom: 0.3rem;
        }
        
        .booking-info {
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .booking-info i {
            width: 18px;
        }
        
        .badge {
            padding: 0.4rem 0.8rem;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.75rem;
        }
        
        .badge-warning {
            background: #FFF3CD;
            color: #856404;
        }
        
        .badge-success {
            background: #D4EDDA;
            color: #155724;
        }
        
        .badge-danger {
            background: #F8D7DA;
            color: #721C24;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem 0;
            color: #6c757d;
        }
        
        .empty-state i {
            font-size: 4rem;
            opacity: 0.3;
            margin-bottom: 1rem;
        }
        
        .notif-item-widget {
            background: var(--soft-blue);
            border-left: 3px solid var(--primary-blue);
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 0.8rem;
            transition: all 0.3s ease;
        }
        
        .notif-item-widget:hover {
            background: #d4e8f5;
            transform: translateX(5px);
        }
        
        .notif-item-widget.unread {
            background: white;
            box-shadow: 0 2px 8px rgba(107, 163, 215, 0.1);
        }
        
        .notif-widget-title {
            font-weight: 600;
            color: var(--dark-blue);
            font-size: 0.95rem;
            margin-bottom: 0.3rem;
        }
        
        .notif-widget-text {
            font-size: 0.85rem;
            color: #6c757d;
            margin-bottom: 0.3rem;
        }
        
        .notif-widget-time {
            font-size: 0.75rem;
            color: #adb5bd;
        }
        
        .view-all-link {
            text-align: center;
            padding-top: 1rem;
            border-top: 1px solid #f0f0f0;
        }
        
        .view-all-link a {
            color: var(--primary-blue);
            text-decoration: none;
            font-weight: 600;
        }
        
        .view-all-link a:hover {
            color: var(--dark-blue);
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <a href="#" class="sidebar-brand">
                <i class="fas fa-building"></i>
                <span>Booking UCA</span>
            </a>
        </div>
        <div class="sidebar-menu">
            <a href="index.php" class="menu-item active">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
            <a href="booking.php" class="menu-item">
                <i class="fas fa-plus-circle"></i>
                <span>Booking Baru</span>
            </a>
            <a href="riwayat.php" class="menu-item">
                <i class="fas fa-history"></i>
                <span>Riwayat Booking</span>
            </a>
            <a href="notifikasi.php" class="menu-item">
                <i class="fas fa-bell"></i>
                <span>Notifikasi</span>
                <?php if ($unreadNotif > 0): ?>
                    <span class="menu-badge"><?= $unreadNotif ?></span>
                <?php endif; ?>
            </a>
            <a href="profil.php" class="menu-item">
                <i class="fas fa-user"></i>
                <span>Profil Saya</span>
            </a>
            <hr>
            <a href="../auth/logout.php" class="menu-item">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Topbar -->
        <div class="topbar">
            <h1 class="topbar-title">Dashboard</h1>
            <div class="user-info">
                <?php include '../includes/notifikasi_dropdown.php'; ?>
                <span class="ms-3"><?= htmlspecialchars($_SESSION['nama']) ?></span>
                <div class="user-avatar">
                    <?= strtoupper(substr($_SESSION['nama'], 0, 1)) ?>
                </div>
            </div>
        </div>
        
        <!-- Welcome Card -->
        <div class="welcome-card">
            <h2 class="welcome-title">Selamat Datang, <?= htmlspecialchars($_SESSION['nama']) ?>!</h2>
            <p class="welcome-text">Siap untuk booking ruangan hari ini? Pilih ruangan yang Anda butuhkan dan kirim permohonan booking dengan mudah.</p>
            <a href="booking.php" class="btn btn-booking">
                <i class="fas fa-plus-circle me-2"></i>Buat Booking Baru
            </a>
        </div>
        
        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon blue">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="stat-value"><?= $totalBooking ?></div>
                <div class="stat-label">Total Booking</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon green">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-value"><?= $bookingAktif ?></div>
                <div class="stat-label">Booking Aktif</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon orange">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-value"><?= $bookingPending ?></div>
                <div class="stat-label">Menunggu Persetujuan</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon red">
                    <i class="fas fa-bell"></i>
                </div>
                <div class="stat-value"><?= $unreadNotif ?></div>
                <div class="stat-label">Notifikasi Baru</div>
            </div>
        </div>
        
        <div class="row">
            <!-- Upcoming Bookings -->
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-calendar-alt me-2"></i>Booking Mendatang
                    </div>
                    <div class="card-body">
                        <?php if (count($upcomingBookings) > 0): ?>
                            <?php foreach ($upcomingBookings as $booking): ?>
                                <div class="booking-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <div class="booking-title"><?= htmlspecialchars($booking['nama_ruangan']) ?></div>
                                            <div class="booking-info">
                                                <i class="fas fa-calendar"></i> <?= formatTanggal($booking['tanggal_booking']) ?><br>
                                                <i class="fas fa-clock"></i> <?= formatWaktu($booking['jam_mulai']) ?> - <?= formatWaktu($booking['jam_selesai']) ?><br>
                                                <i class="fas fa-building"></i> <?= htmlspecialchars($booking['nama_gedung']) ?>
                                            </div>
                                        </div>
                                        <span class="badge <?= getStatusBadge($booking['status_booking']) ?>">
                                            <?= getStatusLabel($booking['status_booking']) ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-calendar-times"></i>
                                <p>Belum ada booking mendatang</p>
                                <a href="booking.php" class="btn btn-primary mt-2">
                                    <i class="fas fa-plus-circle me-1"></i>Buat Booking
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Recent Notifications Widget -->
            <div class="col-lg-5">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-bell me-2"></i>Notifikasi Terbaru
                        <?php if ($unreadNotif > 0): ?>
                            <span class="badge badge-danger ms-2"><?= $unreadNotif ?> Baru</span>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <?php if (count($recentNotifs) > 0): ?>
                            <?php foreach ($recentNotifs as $notif): ?>
                                <div class="notif-item-widget <?= $notif['status'] === 'unread' ? 'unread' : '' ?>">
                                    <div class="notif-widget-title">
                                        <?= htmlspecialchars($notif['judul']) ?>
                                        <?php if ($notif['status'] === 'unread'): ?>
                                            <i class="fas fa-circle ms-1" style="font-size: 0.5rem; color: #dc3545;"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="notif-widget-text">
                                        <?= htmlspecialchars(substr($notif['pesan'], 0, 80)) ?>...
                                    </div>
                                    <div class="notif-widget-time">
                                        <i class="fas fa-clock me-1"></i>
                                        <?php
                                        $timestamp = strtotime($notif['created_at']);
                                        $diff = time() - $timestamp;
                                        if ($diff < 3600) {
                                            echo floor($diff / 60) . ' menit lalu';
                                        } elseif ($diff < 86400) {
                                            echo floor($diff / 3600) . ' jam lalu';
                                        } else {
                                            echo date('d M Y, H:i', $timestamp);
                                        }
                                        ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            
                            <div class="view-all-link">
                                <a href="notifikasi.php">
                                    Lihat Semua Notifikasi <i class="fas fa-arrow-right ms-1"></i>
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-bell-slash"></i>
                                <p>Belum ada notifikasi</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
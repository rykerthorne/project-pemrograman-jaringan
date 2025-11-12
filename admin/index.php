<?php
// admin/index.php
require_once '../includes/functions.php';
requireAdmin();

$db = getDB();

// Get statistics
$totalRuangan = $db->query("SELECT COUNT(*) as total FROM ruangan")->fetch()['total'];
$totalUsers = $db->query("SELECT COUNT(*) as total FROM users WHERE role='user'")->fetch()['total'];
$bookingPending = $db->query("SELECT COUNT(*) as total FROM booking WHERE status_booking='pending'")->fetch()['total'];
$bookingHariIni = $db->query("SELECT COUNT(*) as total FROM booking WHERE tanggal_booking = CURDATE()")->fetch()['total'];
$bookingSelesai = $db->query("SELECT COUNT(*) as total FROM booking WHERE status_booking='selesai'")->fetch()['total'];

// Get recent bookings
$recentBookings = $db->query("
    SELECT b.*, u.nama, r.nama_ruangan, r.kode_ruangan 
    FROM booking b
    JOIN users u ON b.user_id = u.id
    JOIN ruangan r ON b.ruangan_id = r.id
    ORDER BY b.created_at DESC
    LIMIT 5
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Booking UCA</title>
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
            overflow-x: hidden;
        }
        
        /* Sidebar */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background: white;
            box-shadow: 4px 0 20px rgba(107, 163, 215, 0.1);
            z-index: 1000;
            transition: all 0.3s ease;
            overflow-y: auto;
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
        
        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 2rem;
            min-height: 100vh;
        }
        
        /* Topbar */
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
        
        /* Stats Cards */
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
        
        .stat-icon.purple {
            background: linear-gradient(135deg, #B47AEA, #D4A5F9);
            color: white;
        }
        
        .stat-icon.teal {
            background: linear-gradient(135deg, #17a2b8, #20c997);
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
        
        /* Card */
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
        }
        
        .card-body {
            padding: 1.5rem;
        }
        
        /* Table */
        .table {
            margin-bottom: 0;
        }
        
        .table thead th {
            border-bottom: 2px solid var(--soft-blue);
            color: var(--dark-blue);
            font-weight: 600;
            padding: 1rem;
        }
        
        .table tbody td {
            padding: 1rem;
            vertical-align: middle;
        }
        
        /* Badge */
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
        
        .badge-info {
            background: #D1ECF1;
            color: #0C5460;
        }
        
        .badge-secondary {
            background: #E2E3E5;
            color: #383D41;
        }
        
        /* Button */
        .btn-sm {
            padding: 0.4rem 0.8rem;
            font-size: 0.85rem;
            border-radius: 6px;
        }
        
        .btn-primary {
            background: var(--primary-blue);
            border: none;
        }
        
        .btn-primary:hover {
            background: var(--dark-blue);
        }
        
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
            
            .sidebar {
                transform: translateX(-100%);
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
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
            <a href="ruangan.php" class="menu-item">
                <i class="fas fa-door-open"></i>
                <span>Kelola Ruangan</span>
            </a>
            <a href="booking.php" class="menu-item">
                <i class="fas fa-calendar-check"></i>
                <span>Kelola Booking</span>
            </a>
            <a href="riwayat.php" class="menu-item">
                <i class="fas fa-history"></i>
                <span>Riwayat Booking</span>
            </a>
            <a href="users.php" class="menu-item">
                <i class="fas fa-users"></i>
                <span>Kelola User</span>
            </a>
            <a href="laporan.php" class="menu-item">
                <i class="fas fa-chart-bar"></i>
                <span>Laporan</span>
            </a>
            <a href="notifikasi.php" class="menu-item">
                <i class="fas fa-bell"></i>
                <span>Kirim Notifikasi</span>
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
                <span>Admin</span>
                <div class="user-avatar">
                    <i class="fas fa-user"></i>
                </div>
            </div>
        </div>
        
        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon blue">
                    <i class="fas fa-door-open"></i>
                </div>
                <div class="stat-value"><?= $totalRuangan ?></div>
                <div class="stat-label">Total Ruangan</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon green">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-value"><?= $totalUsers ?></div>
                <div class="stat-label">Total Pengguna</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon orange">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-value"><?= $bookingPending ?></div>
                <div class="stat-label">Booking Pending</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon purple">
                    <i class="fas fa-calendar-day"></i>
                </div>
                <div class="stat-value"><?= $bookingHariIni ?></div>
                <div class="stat-label">Booking Hari Ini</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon teal">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-value"><?= $bookingSelesai ?></div>
                <div class="stat-label">Booking Selesai</div>
            </div>
        </div>
        
        <!-- Recent Bookings -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-list me-2"></i>Booking Terbaru
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Ruangan</th>
                                <th>Tanggal</th>
                                <th>Waktu</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentBookings as $booking): ?>
                            <tr>
                                <td><?= htmlspecialchars($booking['nama']) ?></td>
                                <td><?= htmlspecialchars($booking['kode_ruangan']) ?></td>
                                <td><?= formatTanggal($booking['tanggal_booking']) ?></td>
                                <td><?= formatWaktu($booking['jam_mulai']) ?> - <?= formatWaktu($booking['jam_selesai']) ?></td>
                                <td>
                                    <span class="badge <?= getStatusBadge($booking['status_booking']) ?>">
                                        <?= getStatusLabel($booking['status_booking']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($booking['status_booking'] === 'selesai' || $booking['status_booking'] === 'dibatalkan'): ?>
                                        <a href="riwayat.php" class="btn btn-sm btn-info text-white">
                                            <i class="fas fa-history"></i> Riwayat
                                        </a>
                                    <?php else: ?>
                                        <a href="booking.php" class="btn btn-sm btn-primary">
                                            <i class="fas fa-eye"></i> Detail
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
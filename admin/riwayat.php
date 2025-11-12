<?php
// admin/riwayat.php
require_once '../includes/functions.php';
requireAdmin();

$db = getDB();

// Get filter
$search = $_GET['search'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

// Build query - Show completed and cancelled bookings
$sql = "SELECT b.*, u.nama, u.email, u.nim_nip, r.nama_ruangan, r.kode_ruangan, g.nama_gedung,
        admin.nama as admin_nama
        FROM booking b
        JOIN users u ON b.user_id = u.id
        JOIN ruangan r ON b.ruangan_id = r.id
        JOIN gedung g ON r.gedung_id = g.id
        LEFT JOIN users admin ON b.approved_by = admin.id
        WHERE b.status_booking IN ('selesai', 'dibatalkan')";

$params = [];

if ($search) {
    $sql .= " AND (u.nama LIKE :search OR r.kode_ruangan LIKE :search OR r.nama_ruangan LIKE :search OR u.nim_nip LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

if ($start_date) {
    $sql .= " AND b.tanggal_booking >= :start_date";
    $params[':start_date'] = $start_date;
}

if ($end_date) {
    $sql .= " AND b.tanggal_booking <= :end_date";
    $params[':end_date'] = $end_date;
}

$sql .= " ORDER BY b.updated_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

// Get statistics
$statsStmt = $db->query("SELECT 
    SUM(CASE WHEN status_booking = 'selesai' THEN 1 ELSE 0 END) as total_selesai,
    SUM(CASE WHEN status_booking = 'dibatalkan' THEN 1 ELSE 0 END) as total_dibatalkan
    FROM booking");
$stats = $statsStmt->fetch();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Booking - Admin</title>
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
        }
        
        .topbar-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--dark-blue);
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(107, 163, 215, 0.08);
            margin-bottom: 1.5rem;
        }
        
        .stat-card h3 {
            font-size: 2rem;
            font-weight: 800;
            margin: 0.5rem 0;
            color: var(--primary-blue);
        }
        
        .stat-card p {
            margin: 0;
            color: #6c757d;
            font-weight: 600;
        }
        
        .stat-card .icon {
            font-size: 2.5rem;
            opacity: 0.2;
            float: right;
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
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .table thead th {
            border-bottom: 2px solid var(--soft-blue);
            color: var(--dark-blue);
            font-weight: 600;
            padding: 1rem;
            background: #f8f9fa;
        }
        
        .table tbody td {
            padding: 1rem;
            vertical-align: middle;
        }
        
        .badge {
            padding: 0.4rem 0.8rem;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.75rem;
        }
        
        .badge-info {
            background: #D1ECF1;
            color: #0C5460;
        }
        
        .badge-secondary {
            background: #E2E3E5;
            color: #383D41;
        }
        
        .btn-sm {
            padding: 0.4rem 0.8rem;
            font-size: 0.85rem;
            border-radius: 6px;
        }
        
        .modal-header {
            border-bottom: 1px solid #e9ecef;
        }
        
        .modal-footer {
            border-top: 1px solid #e9ecef;
        }
        
        .table-borderless th {
            font-weight: 600;
            width: 40%;
            padding: 0.5rem;
        }
        
        .table-borderless td {
            padding: 0.5rem;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: #6c757d;
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
            
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <a href="index.php" class="sidebar-brand">
                <i class="fas fa-building"></i>
                <span>Booking UCA</span>
            </a>
        </div>
        <div class="sidebar-menu">
            <a href="index.php" class="menu-item">
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
            <a href="riwayat.php" class="menu-item active">
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
        <div class="topbar">
            <h1 class="topbar-title">Riwayat Booking</h1>
        </div>
        
        <!-- Statistics Cards -->
        <div class="row">
            <div class="col-md-6">
                <div class="stat-card">
                    <i class="fas fa-check-circle text-info icon"></i>
                    <p>Total Booking Selesai</p>
                    <h3><?= $stats['total_selesai'] ?></h3>
                </div>
            </div>
            <div class="col-md-6">
                <div class="stat-card">
                    <i class="fas fa-times-circle text-secondary icon"></i>
                    <p>Total Booking Dibatalkan</p>
                    <h3><?= $stats['total_dibatalkan'] ?></h3>
                </div>
            </div>
        </div>
        
        <!-- Filter Card -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-filter me-2"></i>Filter & Pencarian
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Tanggal Mulai</label>
                        <input type="date" class="form-control" name="start_date" value="<?= htmlspecialchars($start_date) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Tanggal Akhir</label>
                        <input type="date" class="form-control" name="end_date" value="<?= htmlspecialchars($end_date) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Pencarian</label>
                        <input type="text" class="form-control" name="search" 
                               placeholder="Nama, NIM/NIP, Ruangan..." 
                               value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="fas fa-search me-2"></i>Cari
                        </button>
                        <a href="riwayat.php" class="btn btn-secondary">
                            <i class="fas fa-redo me-2"></i>Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Data Table -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-list me-2"></i>Daftar Riwayat Booking (<?= count($bookings) ?> data)
            </div>
            <div class="card-body">
                <?php if (empty($bookings)): ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <h5>Tidak ada data riwayat</h5>
                        <p class="text-muted">Belum ada booking yang selesai atau dibatalkan.</p>
                    </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th width="5%">ID</th>
                                <th width="15%">User</th>
                                <th width="12%">Ruangan</th>
                                <th width="10%">Tanggal</th>
                                <th width="12%">Waktu</th>
                                <th width="15%">Keperluan</th>
                                <th width="8%">Status</th>
                                <th width="12%">Diproses Oleh</th>
                                <th width="11%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $booking): ?>
                            <tr>
                                <td><strong>#<?= $booking['id'] ?></strong></td>
                                <td>
                                    <strong><?= htmlspecialchars($booking['nama']) ?></strong><br>
                                    <small class="text-muted"><?= htmlspecialchars($booking['nim_nip']) ?></small>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($booking['kode_ruangan']) ?></strong><br>
                                    <small class="text-muted"><?= htmlspecialchars($booking['nama_gedung']) ?></small>
                                </td>
                                <td><?= formatTanggal($booking['tanggal_booking']) ?></td>
                                <td><?= formatWaktu($booking['jam_mulai']) ?> - <?= formatWaktu($booking['jam_selesai']) ?></td>
                                <td>
                                    <small><?= substr(htmlspecialchars($booking['keperluan']), 0, 40) ?><?= strlen($booking['keperluan']) > 40 ? '...' : '' ?></small>
                                </td>
                                <td>
                                    <?php if ($booking['status_booking'] === 'selesai'): ?>
                                        <span class="badge badge-info">
                                            <i class="fas fa-check-circle me-1"></i>Selesai
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">
                                            <i class="fas fa-times-circle me-1"></i>Dibatalkan
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($booking['admin_nama']): ?>
                                        <small><?= htmlspecialchars($booking['admin_nama']) ?></small><br>
                                        <small class="text-muted"><?= date('d/m/Y H:i', strtotime($booking['approved_at'])) ?></small>
                                    <?php else: ?>
                                        <small class="text-muted">-</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-primary" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#detailModal<?= $booking['id'] ?>"
                                            title="Lihat Detail">
                                        <i class="fas fa-eye"></i> Detail
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Detail Modals -->
    <?php foreach ($bookings as $booking): ?>
        <div class="modal fade" id="detailModal<?= $booking['id'] ?>" tabindex="-1" aria-labelledby="detailModalLabel<?= $booking['id'] ?>" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="detailModalLabel<?= $booking['id'] ?>">
                            <i class="fas fa-info-circle me-2"></i>Detail Riwayat Booking #<?= $booking['id'] ?>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="fw-bold text-primary mb-3">
                                    <i class="fas fa-user me-2"></i>Informasi User
                                </h6>
                                <table class="table table-borderless table-sm">
                                    <tbody>
                                        <tr>
                                            <th>Nama:</th>
                                            <td><?= htmlspecialchars($booking['nama']) ?></td>
                                        </tr>
                                        <tr>
                                            <th>NIM/NIP:</th>
                                            <td><?= htmlspecialchars($booking['nim_nip']) ?></td>
                                        </tr>
                                        <tr>
                                            <th>Email:</th>
                                            <td><?= htmlspecialchars($booking['email']) ?></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h6 class="fw-bold text-primary mb-3">
                                    <i class="fas fa-door-open me-2"></i>Informasi Ruangan
                                </h6>
                                <table class="table table-borderless table-sm">
                                    <tbody>
                                        <tr>
                                            <th>Ruangan:</th>
                                            <td><?= htmlspecialchars($booking['nama_ruangan']) ?></td>
                                        </tr>
                                        <tr>
                                            <th>Kode:</th>
                                            <td><?= htmlspecialchars($booking['kode_ruangan']) ?></td>
                                        </tr>
                                        <tr>
                                            <th>Gedung:</th>
                                            <td><?= htmlspecialchars($booking['nama_gedung']) ?></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <h6 class="fw-bold text-primary mb-3">
                            <i class="fas fa-calendar-alt me-2"></i>Informasi Booking
                        </h6>
                        <table class="table table-borderless">
                            <tbody>
                                <tr>
                                    <th width="30%"><i class="fas fa-calendar me-2"></i>Tanggal:</th>
                                    <td><?= formatTanggal($booking['tanggal_booking']) ?></td>
                                </tr>
                                <tr>
                                    <th><i class="fas fa-clock me-2"></i>Waktu:</th>
                                    <td><?= formatWaktu($booking['jam_mulai']) ?> - <?= formatWaktu($booking['jam_selesai']) ?></td>
                                </tr>
                                <tr>
                                    <th><i class="fas fa-users me-2"></i>Jumlah Peserta:</th>
                                    <td><?= $booking['jumlah_peserta'] ?> orang</td>
                                </tr>
                                <tr>
                                    <th><i class="fas fa-clipboard me-2"></i>Keperluan:</th>
                                    <td><?= nl2br(htmlspecialchars($booking['keperluan'])) ?></td>
                                </tr>
                                <tr>
                                    <th><i class="fas fa-info-circle me-2"></i>Status:</th>
                                    <td>
                                        <?php if ($booking['status_booking'] === 'selesai'): ?>
                                            <span class="badge badge-info">
                                                <i class="fas fa-check-circle me-1"></i>Selesai
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary">
                                                <i class="fas fa-times-circle me-1"></i>Dibatalkan
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php if ($booking['catatan_admin']): ?>
                                <tr>
                                    <th><i class="fas fa-sticky-note me-2"></i>Catatan Admin:</th>
                                    <td><?= nl2br(htmlspecialchars($booking['catatan_admin'])) ?></td>
                                </tr>
                                <?php endif; ?>
                                <?php if ($booking['admin_nama']): ?>
                                <tr>
                                    <th><i class="fas fa-user-shield me-2"></i>Diproses Oleh:</th>
                                    <td>
                                        <?= htmlspecialchars($booking['admin_nama']) ?><br>
                                        <small class="text-muted"><?= date('d/m/Y H:i:s', strtotime($booking['approved_at'])) ?></small>
                                    </td>
                                </tr>
                                <?php endif; ?>
                                <tr>
                                    <th><i class="fas fa-calendar-plus me-2"></i>Dibuat:</th>
                                    <td><?= date('d/m/Y H:i:s', strtotime($booking['created_at'])) ?></td>
                                </tr>
                                <tr>
                                    <th><i class="fas fa-calendar-edit me-2"></i>Terakhir Diupdate:</th>
                                    <td><?= date('d/m/Y H:i:s', strtotime($booking['updated_at'])) ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
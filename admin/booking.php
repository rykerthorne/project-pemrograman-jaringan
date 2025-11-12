<?php
// admin/booking.php
require_once '../includes/functions.php';
requireAdmin();

$db = getDB();
$success = '';

// Handle Approve/Reject/Complete
if (isset($_POST['action'])) {
    $booking_id = $_POST['booking_id'];
    $action = $_POST['action'];
    $catatan = trim($_POST['catatan'] ?? '');
    
    if ($action === 'approve') {
        $status = 'approved';
        $title = 'Booking Disetujui';
        $message = 'Booking Anda telah disetujui oleh admin';
        $successMsg = 'disetujui';
    } elseif ($action === 'reject') {
        $status = 'rejected';
        $title = 'Booking Ditolak';
        $message = 'Booking Anda ditolak. ' . $catatan;
        $successMsg = 'ditolak';
    } elseif ($action === 'complete') {
        $status = 'selesai';
        $title = 'Booking Selesai';
        $message = 'Booking Anda telah selesai dilaksanakan';
        $successMsg = 'diselesaikan';
    }
    
    $stmt = $db->prepare("UPDATE booking SET status_booking = ?, catatan_admin = ?, 
                          approved_by = ?, approved_at = NOW() WHERE id = ?");
    if ($stmt->execute([$status, $catatan, $_SESSION['user_id'], $booking_id])) {
        // Get booking user
        $bookingStmt = $db->prepare("SELECT user_id FROM booking WHERE id = ?");
        $bookingStmt->execute([$booking_id]);
        $booking = $bookingStmt->fetch();
        
        // Add notification
        addNotifikasi($booking['user_id'], $booking_id, $title, $message);
        
        logAktivitas($_SESSION['user_id'], ucfirst($action) . ' booking #' . $booking_id);
        $success = 'Booking berhasil ' . $successMsg . '!';
    }
}

// Get filter
$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build query - Show all active bookings (not cancelled or finished)
$sql = "SELECT b.*, u.nama, u.email, r.nama_ruangan, r.kode_ruangan, g.nama_gedung,
        CONCAT(b.tanggal_booking, ' ', b.jam_mulai) as booking_datetime
        FROM booking b
        JOIN users u ON b.user_id = u.id
        JOIN ruangan r ON b.ruangan_id = r.id
        JOIN gedung g ON r.gedung_id = g.id
        WHERE b.status_booking NOT IN ('dibatalkan', 'selesai')";

$params = [];

if ($filter !== 'all') {
    $sql .= " AND b.status_booking = :status";
    $params[':status'] = $filter;
}

if ($search) {
    $sql .= " AND (u.nama LIKE :search OR r.kode_ruangan LIKE :search OR r.nama_ruangan LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

$sql .= " ORDER BY b.created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Booking - Admin</title>
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
        
        .filter-tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }
        
        .filter-tab {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            background: white;
            border: 2px solid #e9ecef;
            text-decoration: none;
            color: var(--dark-blue);
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .filter-tab:hover,
        .filter-tab.active {
            background: var(--primary-blue);
            color: white;
            border-color: var(--primary-blue);
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
        
        .badge-secondary {
            background: #E2E3E5;
            color: #383D41;
        }
        
        .badge-info {
            background: #D1ECF1;
            color: #0C5460;
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
            
            .filter-tabs {
                flex-direction: column;
            }
            
            .filter-tab {
                width: 100%;
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
            <a href="booking.php" class="menu-item active">
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
        <div class="topbar">
            <h1 class="topbar-title">Manajemen Booking</h1>
        </div>
        
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i><?= $success ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <i class="fas fa-filter me-2"></i>Filter & Pencarian
            </div>
            <div class="card-body">
                <div class="filter-tabs">
                    <a href="?filter=all<?= $search ? '&search=' . urlencode($search) : '' ?>" 
                       class="filter-tab <?= $filter === 'all' ? 'active' : '' ?>">
                        Semua Booking
                    </a>
                    <a href="?filter=pending<?= $search ? '&search=' . urlencode($search) : '' ?>" 
                       class="filter-tab <?= $filter === 'pending' ? 'active' : '' ?>">
                        Pending
                    </a>
                    <a href="?filter=approved<?= $search ? '&search=' . urlencode($search) : '' ?>" 
                       class="filter-tab <?= $filter === 'approved' ? 'active' : '' ?>">
                        Disetujui
                    </a>
                    <a href="?filter=rejected<?= $search ? '&search=' . urlencode($search) : '' ?>" 
                       class="filter-tab <?= $filter === 'rejected' ? 'active' : '' ?>">
                        Ditolak
                    </a>
                </div>
                
                <form method="GET" class="row g-3">
                    <?php if ($filter !== 'all'): ?>
                        <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
                    <?php endif; ?>
                    <div class="col-md-10">
                        <input type="text" class="form-control" name="search" 
                               placeholder="Cari berdasarkan nama user, kode ruangan, atau nama ruangan..." 
                               value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search me-2"></i>Cari
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <i class="fas fa-list me-2"></i>Daftar Booking Aktif
            </div>
            <div class="card-body">
                <?php if (empty($bookings)): ?>
                    <div class="empty-state">
                        <i class="fas fa-calendar-times"></i>
                        <h5>Tidak ada data booking</h5>
                        <p class="text-muted">Belum ada booking yang sesuai dengan filter yang dipilih.</p>
                    </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>User</th>
                                <th>Ruangan</th>
                                <th>Tanggal</th>
                                <th>Waktu</th>
                                <th>Keperluan</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $booking): ?>
                            <tr>
                                <td><strong>#<?= $booking['id'] ?></strong></td>
                                <td>
                                    <strong><?= htmlspecialchars($booking['nama']) ?></strong><br>
                                    <small class="text-muted"><?= htmlspecialchars($booking['email']) ?></small>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($booking['kode_ruangan']) ?></strong><br>
                                    <small class="text-muted"><?= htmlspecialchars($booking['nama_gedung']) ?></small>
                                </td>
                                <td><?= formatTanggal($booking['tanggal_booking']) ?></td>
                                <td><?= formatWaktu($booking['jam_mulai']) ?> - <?= formatWaktu($booking['jam_selesai']) ?></td>
                                <td><?= substr(htmlspecialchars($booking['keperluan']), 0, 30) ?>...</td>
                                <td>
                                    <span class="badge <?= getStatusBadge($booking['status_booking']) ?>">
                                        <?= getStatusLabel($booking['status_booking']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <button class="btn btn-sm btn-primary" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#detailModal<?= $booking['id'] ?>"
                                                title="Lihat Detail">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <?php if ($booking['status_booking'] === 'pending'): ?>
                                            <button class="btn btn-sm btn-success" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#approveModal<?= $booking['id'] ?>"
                                                    title="Setujui">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#rejectModal<?= $booking['id'] ?>"
                                                    title="Tolak">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        <?php elseif ($booking['status_booking'] === 'approved'): ?>
                                            <button class="btn btn-sm btn-info text-white" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#completeModal<?= $booking['id'] ?>"
                                                    title="Selesaikan">
                                                <i class="fas fa-check-double"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
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
    
    <!-- Modals -->
    <?php foreach ($bookings as $booking): ?>
        <!-- Detail Modal -->
        <div class="modal fade" id="detailModal<?= $booking['id'] ?>" tabindex="-1" aria-labelledby="detailModalLabel<?= $booking['id'] ?>" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="detailModalLabel<?= $booking['id'] ?>">
                            <i class="fas fa-info-circle me-2"></i>Detail Booking #<?= $booking['id'] ?>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <table class="table table-borderless">
                            <tbody>
                                <tr>
                                    <th><i class="fas fa-user me-2"></i>User:</th>
                                    <td><?= htmlspecialchars($booking['nama']) ?></td>
                                </tr>
                                <tr>
                                    <th><i class="fas fa-envelope me-2"></i>Email:</th>
                                    <td><?= htmlspecialchars($booking['email']) ?></td>
                                </tr>
                                <tr>
                                    <th><i class="fas fa-door-open me-2"></i>Ruangan:</th>
                                    <td><?= htmlspecialchars($booking['nama_ruangan']) ?> (<?= htmlspecialchars($booking['kode_ruangan']) ?>)</td>
                                </tr>
                                <tr>
                                    <th><i class="fas fa-building me-2"></i>Gedung:</th>
                                    <td><?= htmlspecialchars($booking['nama_gedung']) ?></td>
                                </tr>
                                <tr>
                                    <th><i class="fas fa-calendar me-2"></i>Tanggal:</th>
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
                                        <span class="badge <?= getStatusBadge($booking['status_booking']) ?>">
                                            <?= getStatusLabel($booking['status_booking']) ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php if ($booking['catatan_admin']): ?>
                                <tr>
                                    <th><i class="fas fa-sticky-note me-2"></i>Catatan Admin:</th>
                                    <td><?= nl2br(htmlspecialchars($booking['catatan_admin'])) ?></td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if ($booking['status_booking'] === 'pending'): ?>
        <!-- Approve Modal -->
        <div class="modal fade" id="approveModal<?= $booking['id'] ?>" tabindex="-1" aria-labelledby="approveModalLabel<?= $booking['id'] ?>" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="">
                        <div class="modal-header bg-success text-white">
                            <h5 class="modal-title" id="approveModalLabel<?= $booking['id'] ?>">
                                <i class="fas fa-check-circle me-2"></i>Setujui Booking
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">
                            <input type="hidden" name="action" value="approve">
                            <p>Apakah Anda yakin ingin menyetujui booking berikut?</p>
                            <div class="alert alert-info">
                                <strong>User:</strong> <?= htmlspecialchars($booking['nama']) ?><br>
                                <strong>Ruangan:</strong> <?= htmlspecialchars($booking['kode_ruangan']) ?><br>
                                <strong>Tanggal:</strong> <?= formatTanggal($booking['tanggal_booking']) ?><br>
                                <strong>Waktu:</strong> <?= formatWaktu($booking['jam_mulai']) ?> - <?= formatWaktu($booking['jam_selesai']) ?>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Catatan (Opsional)</label>
                                <textarea class="form-control" name="catatan" rows="2" placeholder="Tambahkan catatan jika diperlukan..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-check me-2"></i>Ya, Setujui
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Reject Modal -->
        <div class="modal fade" id="rejectModal<?= $booking['id'] ?>" tabindex="-1" aria-labelledby="rejectModalLabel<?= $booking['id'] ?>" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="">
                        <div class="modal-header bg-danger text-white">
                            <h5 class="modal-title" id="rejectModalLabel<?= $booking['id'] ?>">
                                <i class="fas fa-times-circle me-2"></i>Tolak Booking
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">
                            <input type="hidden" name="action" value="reject">
                            <p>Apakah Anda yakin ingin menolak booking berikut?</p>
                            <div class="alert alert-warning">
                                <strong>User:</strong> <?= htmlspecialchars($booking['nama']) ?><br>
                                <strong>Ruangan:</strong> <?= htmlspecialchars($booking['kode_ruangan']) ?><br>
                                <strong>Tanggal:</strong> <?= formatTanggal($booking['tanggal_booking']) ?><br>
                                <strong>Waktu:</strong> <?= formatWaktu($booking['jam_mulai']) ?> - <?= formatWaktu($booking['jam_selesai']) ?>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Alasan Penolakan <span class="text-danger">*</span></label>
                                <textarea class="form-control" name="catatan" rows="3" required placeholder="Jelaskan alasan penolakan booking ini..."></textarea>
                                <small class="text-muted">Alasan ini akan dikirimkan ke user sebagai notifikasi</small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-times me-2"></i>Ya, Tolak
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>
    <?php endforeach; ?>
    
    <!-- Complete Booking Modals -->
    <?php foreach ($bookings as $booking): ?>
        <?php if ($booking['status_booking'] === 'approved'): ?>
        <!-- Complete Modal -->
        <div class="modal fade" id="completeModal<?= $booking['id'] ?>" tabindex="-1" aria-labelledby="completeModalLabel<?= $booking['id'] ?>" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="">
                        <div class="modal-header bg-info text-white">
                            <h5 class="modal-title" id="completeModalLabel<?= $booking['id'] ?>">
                                <i class="fas fa-check-double me-2"></i>Selesaikan Booking
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">
                            <input type="hidden" name="action" value="complete">
                            <p>Apakah booking berikut sudah selesai dilaksanakan?</p>
                            <div class="alert alert-info">
                                <strong>User:</strong> <?= htmlspecialchars($booking['nama']) ?><br>
                                <strong>Ruangan:</strong> <?= htmlspecialchars($booking['kode_ruangan']) ?><br>
                                <strong>Tanggal:</strong> <?= formatTanggal($booking['tanggal_booking']) ?><br>
                                <strong>Waktu:</strong> <?= formatWaktu($booking['jam_mulai']) ?> - <?= formatWaktu($booking['jam_selesai']) ?>
                            </div>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <small>Booking yang sudah diselesaikan akan dipindahkan ke riwayat dan tidak akan muncul di daftar booking aktif.</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Catatan (Opsional)</label>
                                <textarea class="form-control" name="catatan" rows="2" placeholder="Tambahkan catatan tentang pelaksanaan booking..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-info text-white">
                                <i class="fas fa-check-double me-2"></i>Ya, Selesaikan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>
    <?php endforeach; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto close alerts after 5 seconds
        setTimeout(function() {
            var alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                var bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>
</html>
<?php
// user/riwayat.php
require_once '../includes/functions.php';
requireLogin();

$db = getDB();
$user_id = $_SESSION['user_id'];

// Handle cancel booking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_booking'])) {
    $booking_id = (int)$_POST['booking_id'];
    
    // Verify ownership
    $check = $db->prepare("SELECT * FROM booking WHERE id = ? AND user_id = ?");
    $check->execute([$booking_id, $user_id]);
    $booking = $check->fetch();
    
    if ($booking && $booking['status_booking'] === 'pending') {
        $stmt = $db->prepare("UPDATE booking SET status_booking = 'dibatalkan' WHERE id = ?");
        $stmt->execute([$booking_id]);
        
        logAktivitas($user_id, "Membatalkan booking ID: $booking_id");
        setAlert('success', 'Booking berhasil dibatalkan!');
    } else {
        setAlert('error', 'Tidak dapat membatalkan booking ini!');
    }
    
    header('Location: riwayat.php');
    exit();
}

// Get filter
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'all';

// Build query
$sql = "SELECT b.*, r.nama_ruangan, r.kode_ruangan, g.nama_gedung
        FROM booking b
        JOIN ruangan r ON b.ruangan_id = r.id
        JOIN gedung g ON r.gedung_id = g.id
        WHERE b.user_id = :user_id";

if ($filter_status !== 'all') {
    $sql .= " AND b.status_booking = :status";
}

$sql .= " ORDER BY b.created_at DESC";

$stmt = $db->prepare($sql);
$stmt->bindParam(':user_id', $user_id);
if ($filter_status !== 'all') {
    $stmt->bindParam(':status', $filter_status);
}
$stmt->execute();
$bookings = $stmt->fetchAll();

// Get statistics
$stats = [
    'total' => $db->prepare("SELECT COUNT(*) as c FROM booking WHERE user_id = ?")->execute([$user_id]) ? $db->query("SELECT COUNT(*) as c FROM booking WHERE user_id = $user_id")->fetch()['c'] : 0,
    'pending' => $db->prepare("SELECT COUNT(*) as c FROM booking WHERE user_id = ? AND status_booking='pending'")->execute([$user_id]) ? $db->query("SELECT COUNT(*) as c FROM booking WHERE user_id = $user_id AND status_booking='pending'")->fetch()['c'] : 0,
    'approved' => $db->prepare("SELECT COUNT(*) as c FROM booking WHERE user_id = ? AND status_booking='approved'")->execute([$user_id]) ? $db->query("SELECT COUNT(*) as c FROM booking WHERE user_id = $user_id AND status_booking='approved'")->fetch()['c'] : 0,
    'rejected' => $db->prepare("SELECT COUNT(*) as c FROM booking WHERE user_id = ? AND status_booking='rejected'")->execute([$user_id]) ? $db->query("SELECT COUNT(*) as c FROM booking WHERE user_id = $user_id AND status_booking='rejected'")->fetch()['c'] : 0,
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Booking - UCA</title>
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
        }
        
        .topbar-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--dark-blue);
        }
        
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-box {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(107, 163, 215, 0.08);
            text-align: center;
        }
        
        .stat-box h3 {
            font-size: 2rem;
            font-weight: 800;
            color: var(--dark-blue);
            margin-bottom: 0.5rem;
        }
        
        .stat-box p { color: #6c757d; margin: 0; }
        
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
        
        .card-body { padding: 1.5rem; }
        
        .filter-bar {
            background: white;
            border-radius: 12px;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 15px rgba(107, 163, 215, 0.08);
        }
        
        .filter-btn {
            padding: 0.5rem 1.5rem;
            border-radius: 8px;
            border: 2px solid #e9ecef;
            background: white;
            color: var(--dark-blue);
            font-weight: 600;
            transition: all 0.3s ease;
            margin: 0 0.3rem;
        }
        
        .filter-btn.active {
            background: var(--primary-blue);
            color: white;
            border-color: var(--primary-blue);
        }
        
        .booking-card {
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }
        
        .booking-card:hover {
            border-color: var(--primary-blue);
            box-shadow: 0 4px 15px rgba(107, 163, 215, 0.1);
        }
        
        .badge {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.85rem;
        }
        
        .badge-warning { background: #FFF3CD; color: #856404; }
        .badge-success { background: #D4EDDA; color: #155724; }
        .badge-danger { background: #F8D7DA; color: #721C24; }
        .badge-secondary { background: #E2E3E5; color: #383D41; }
        .badge-dark { background: #D6D8DB; color: #1B1E21; }
        
        .btn-sm { padding: 0.4rem 1rem; font-size: 0.85rem; border-radius: 6px; }
        
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
            <a href="riwayat.php" class="menu-item active">
                <i class="fas fa-history"></i><span>Riwayat Booking</span>
            </a>
            <a href="notifikasi.php" class="menu-item">
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
            <h1 class="topbar-title"><i class="fas fa-history me-2"></i>Riwayat Booking</h1>
        </div>
        
        <?php showAlert(); ?>
        
        <div class="stats-row">
            <div class="stat-box">
                <h3><?= $stats['total'] ?></h3>
                <p><i class="fas fa-list me-1"></i>Total Booking</p>
            </div>
            <div class="stat-box">
                <h3><?= $stats['pending'] ?></h3>
                <p><i class="fas fa-clock me-1"></i>Pending</p>
            </div>
            <div class="stat-box">
                <h3><?= $stats['approved'] ?></h3>
                <p><i class="fas fa-check-circle me-1"></i>Disetujui</p>
            </div>
            <div class="stat-box">
                <h3><?= $stats['rejected'] ?></h3>
                <p><i class="fas fa-times-circle me-1"></i>Ditolak</p>
            </div>
        </div>
        
        <div class="filter-bar">
            <a href="riwayat.php?status=all" class="filter-btn <?= $filter_status === 'all' ? 'active' : '' ?>">
                <i class="fas fa-list me-1"></i>Semua
            </a>
            <a href="riwayat.php?status=pending" class="filter-btn <?= $filter_status === 'pending' ? 'active' : '' ?>">
                <i class="fas fa-clock me-1"></i>Pending
            </a>
            <a href="riwayat.php?status=approved" class="filter-btn <?= $filter_status === 'approved' ? 'active' : '' ?>">
                <i class="fas fa-check-circle me-1"></i>Disetujui
            </a>
            <a href="riwayat.php?status=rejected" class="filter-btn <?= $filter_status === 'rejected' ? 'active' : '' ?>">
                <i class="fas fa-times-circle me-1"></i>Ditolak
            </a>
            <a href="riwayat.php?status=selesai" class="filter-btn <?= $filter_status === 'selesai' ? 'active' : '' ?>">
                <i class="fas fa-check me-1"></i>Selesai
            </a>
        </div>
        
        <div class="card">
            <div class="card-header">
                <i class="fas fa-list-ul me-2"></i>Daftar Booking Anda
            </div>
            <div class="card-body">
                <?php if (count($bookings) > 0): ?>
                    <?php foreach ($bookings as $b): ?>
                    <div class="booking-card">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <h5 class="mb-1"><?= htmlspecialchars($b['nama_ruangan']) ?></h5>
                                        <p class="text-muted mb-0">
                                            <i class="fas fa-building me-1"></i><?= htmlspecialchars($b['nama_gedung']) ?> 
                                            | <i class="fas fa-tag me-1"></i><?= htmlspecialchars($b['kode_ruangan']) ?>
                                        </p>
                                    </div>
                                    <span class="badge <?= getStatusBadge($b['status_booking']) ?>">
                                        <?= getStatusLabel($b['status_booking']) ?>
                                    </span>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <p class="mb-1">
                                            <i class="fas fa-calendar text-primary me-2"></i>
                                            <strong><?= formatTanggal($b['tanggal_booking']) ?></strong>
                                        </p>
                                        <p class="mb-1">
                                            <i class="fas fa-clock text-primary me-2"></i>
                                            <?= formatWaktu($b['jam_mulai']) ?> - <?= formatWaktu($b['jam_selesai']) ?>
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="mb-1">
                                            <i class="fas fa-users text-primary me-2"></i>
                                            <?= $b['jumlah_peserta'] ?> peserta
                                        </p>
                                        <p class="mb-1">
                                            <i class="fas fa-file-alt text-primary me-2"></i>
                                            <?= htmlspecialchars(substr($b['keperluan'], 0, 50)) ?>...
                                        </p>
                                    </div>
                                </div>
                                
                                <?php if (!empty($b['catatan_admin'])): ?>
                                <div class="alert alert-warning mt-2 mb-0" role="alert">
                                    <strong><i class="fas fa-info-circle me-1"></i>Catatan Admin:</strong><br>
                                    <?= htmlspecialchars($b['catatan_admin']) ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="col-md-4 text-end">
                                <button class="btn btn-sm btn-info mb-2 w-100" onclick="viewDetail(<?= htmlspecialchars(json_encode($b)) ?>)">
                                    <i class="fas fa-eye me-1"></i>Detail
                                </button>
                                
                                <?php if ($b['status_booking'] === 'pending'): ?>
                                <form method="POST" onsubmit="return confirm('Batalkan booking ini?');" style="display:inline-block; width:100%;">
                                    <input type="hidden" name="cancel_booking" value="1">
                                    <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger w-100">
                                        <i class="fas fa-times me-1"></i>Batalkan
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <h4>Belum Ada Booking</h4>
                        <p class="text-muted">Anda belum memiliki riwayat booking</p>
                        <a href="booking.php" class="btn btn-primary mt-3">
                            <i class="fas fa-plus-circle me-2"></i>Buat Booking Baru
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Detail Modal -->
    <div class="modal fade" id="detailModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, var(--primary-blue), var(--light-blue)); color: white;">
                    <h5 class="modal-title"><i class="fas fa-info-circle me-2"></i>Detail Booking</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detailContent"></div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewDetail(data) {
            const statusBadge = {
                'pending': 'badge-warning',
                'approved': 'badge-success',
                'rejected': 'badge-danger',
                'selesai': 'badge-secondary',
                'dibatalkan': 'badge-dark'
            };
            
            const content = `
                <div class="mb-3">
                    <h6 class="text-muted">Status Booking</h6>
                    <span class="badge ${statusBadge[data.status_booking]}">${data.status_booking.toUpperCase()}</span>
                </div>
                <hr>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <h6 class="text-muted">Ruangan</h6>
                        <p><strong>${data.nama_ruangan}</strong><br>
                        ${data.nama_gedung} - ${data.kode_ruangan}</p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted">Tanggal & Waktu</h6>
                        <p><strong>${data.tanggal_booking}</strong><br>
                        ${data.jam_mulai} - ${data.jam_selesai}</p>
                    </div>
                </div>
                <div class="mb-3">
                    <h6 class="text-muted">Jumlah Peserta</h6>
                    <p>${data.jumlah_peserta} orang</p>
                </div>
                <div class="mb-3">
                    <h6 class="text-muted">Keperluan</h6>
                    <p>${data.keperluan}</p>
                </div>
                ${data.catatan_admin ? `
                <div class="alert alert-warning">
                    <h6 class="mb-2">Catatan Admin</h6>
                    <p class="mb-0">${data.catatan_admin}</p>
                </div>
                ` : ''}
                <div class="text-muted small">
                    <i class="fas fa-clock me-1"></i>Dibuat pada: ${new Date(data.created_at).toLocaleString('id-ID')}
                </div>
            `;
            
            document.getElementById('detailContent').innerHTML = content;
            new bootstrap.Modal(document.getElementById('detailModal')).show();
        }
    </script>
</body>
</html>
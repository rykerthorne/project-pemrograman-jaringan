<?php
// admin/notifikasi.php
require_once '../includes/functions.php';
requireAdmin();

$db = getDB();

// Handle send notification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_notif'])) {
    $type = $_POST['type']; // all, specific
    $judul = clean($_POST['judul']);
    $pesan = clean($_POST['pesan']);
    
    if ($type === 'all') {
        // Send to all users
        $users = $db->query("SELECT id FROM users WHERE role='user' AND status='active'")->fetchAll();
        
        $stmt = $db->prepare("INSERT INTO notifikasi (user_id, judul, pesan) VALUES (?, ?, ?)");
        
        foreach ($users as $user) {
            $stmt->execute([$user['id'], $judul, $pesan]);
        }
        
        logAktivitas($_SESSION['user_id'], "Mengirim notifikasi broadcast ke semua user");
        setAlert('success', 'Notifikasi berhasil dikirim ke ' . count($users) . ' user!');
        
    } elseif ($type === 'specific') {
        $user_ids = $_POST['user_ids'];
        
        $stmt = $db->prepare("INSERT INTO notifikasi (user_id, judul, pesan) VALUES (?, ?, ?)");
        
        foreach ($user_ids as $user_id) {
            $stmt->execute([$user_id, $judul, $pesan]);
        }
        
        logAktivitas($_SESSION['user_id'], "Mengirim notifikasi ke " . count($user_ids) . " user");
        setAlert('success', 'Notifikasi berhasil dikirim ke ' . count($user_ids) . ' user!');
    }
    
    header('Location: notifikasi.php');
    exit();
}

// Get all active users
$users = $db->query("SELECT id, nama, email, nim_nip FROM users WHERE role='user' AND status='active' ORDER BY nama")->fetchAll();

// Get notification history
$history = $db->query("
    SELECT n.judul, n.pesan, n.created_at, COUNT(*) as total_sent
    FROM notifikasi n
    WHERE n.booking_id IS NULL
    GROUP BY n.judul, n.pesan, DATE(n.created_at)
    ORDER BY n.created_at DESC
    LIMIT 10
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kirim Notifikasi - Admin</title>
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
        
        .card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(107, 163, 215, 0.08);
            border: none;
            margin-bottom: 2rem;
        }
        
        .card-header {
            background: linear-gradient(135deg, var(--primary-blue), var(--light-blue));
            color: white;
            border-radius: 15px 15px 0 0;
            padding: 1.5rem;
            font-weight: 700;
        }
        
        .card-body { padding: 2rem; }
        
        .form-label {
            font-weight: 600;
            color: var(--dark-blue);
            margin-bottom: 0.5rem;
        }
        
        .form-control, .form-select {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 0.75rem 1rem;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 0.2rem rgba(107, 163, 215, 0.15);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-blue), var(--light-blue));
            border: none;
            padding: 0.85rem 2rem;
            border-radius: 10px;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(107, 163, 215, 0.3);
        }
        
        .user-checkbox {
            padding: 0.75rem;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            margin-bottom: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .user-checkbox:hover {
            background: var(--soft-blue);
            border-color: var(--primary-blue);
        }
        
        .user-checkbox input:checked + label {
            color: var(--primary-blue);
            font-weight: 600;
        }
        
        .history-item {
            padding: 1rem;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .history-item:last-child {
            border-bottom: none;
        }
        
        .table thead th {
            border-bottom: 2px solid var(--soft-blue);
            color: var(--dark-blue);
            font-weight: 600;
            padding: 1rem;
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
            <a href="ruangan.php" class="menu-item">
                <i class="fas fa-door-open"></i><span>Kelola Ruangan</span>
            </a>
            <a href="booking.php" class="menu-item">
                <i class="fas fa-calendar-check"></i><span>Kelola Booking</span>
            </a>
            </a>
             <a href="riwayat.php" class="menu-item">
                <i class="fas fa-history"></i>
                <span>Riwayat Booking</span>
            </a>
            <a href="users.php" class="menu-item">
                <i class="fas fa-users"></i><span>Kelola User</span>
            </a>
            <a href="laporan.php" class="menu-item">
                <i class="fas fa-chart-bar"></i><span>Laporan</span>
            </a>
            <a href="notifikasi.php" class="menu-item active">
                <i class="fas fa-bell"></i><span>Kirim Notifikasi</span>
            </a>
            <hr>
            <a href="../auth/logout.php" class="menu-item">
                <i class="fas fa-sign-out-alt"></i><span>Logout</span>
            </a>
        </div>
    </div>
    
    <div class="main-content">
        <div class="topbar">
            <h1 class="topbar-title"><i class="fas fa-paper-plane me-2"></i>Kirim Notifikasi</h1>
        </div>
        
        <?php showAlert(); ?>
        
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-envelope me-2"></i>Form Notifikasi
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="send_notif" value="1">
                            
                            <div class="mb-3">
                                <label class="form-label">Tipe Penerima</label>
                                <select class="form-select" name="type" id="typeSelect" required onchange="toggleUserList()">
                                    <option value="all">Semua User</option>
                                    <option value="specific">Pilih User Tertentu</option>
                                </select>
                            </div>
                            
                            <div class="mb-3" id="userListDiv" style="display: none;">
                                <label class="form-label">Pilih User</label>
                                <div style="max-height: 300px; overflow-y: auto; border: 2px solid #e9ecef; border-radius: 10px; padding: 1rem;">
                                    <div class="mb-2">
                                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="selectAll()">Pilih Semua</button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="deselectAll()">Batal Pilih</button>
                                    </div>
                                    <?php foreach ($users as $user): ?>
                                    <div class="user-checkbox">
                                        <input type="checkbox" class="form-check-input" name="user_ids[]" value="<?= $user['id'] ?>" id="user_<?= $user['id'] ?>">
                                        <label class="form-check-label ms-2" for="user_<?= $user['id'] ?>">
                                            <?= htmlspecialchars($user['nama']) ?> (<?= htmlspecialchars($user['nim_nip']) ?>)
                                        </label>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Judul Notifikasi</label>
                                <input type="text" class="form-control" name="judul" placeholder="Contoh: Pengumuman Penting" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Pesan</label>
                                <textarea class="form-control" name="pesan" rows="5" placeholder="Tulis pesan notifikasi..." required></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-paper-plane me-2"></i>Kirim Notifikasi
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-history me-2"></i>Riwayat Pengiriman
                    </div>
                    <div class="card-body">
                        <?php if (count($history) > 0): ?>
                            <?php foreach ($history as $h): ?>
                            <div class="history-item">
                                <strong><?= htmlspecialchars($h['judul']) ?></strong>
                                <p class="text-muted mb-1" style="font-size: 0.9rem;">
                                    <?= htmlspecialchars(substr($h['pesan'], 0, 60)) ?>...
                                </p>
                                <small class="text-muted">
                                    <i class="fas fa-users me-1"></i><?= $h['total_sent'] ?> penerima
                                    • <?= date('d/m/Y H:i', strtotime($h['created_at'])) ?>
                                </small>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted text-center">Belum ada riwayat pengiriman</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-info-circle me-2"></i>Tips
                    </div>
                    <div class="card-body">
                        <ul class="mb-0">
                            <li>Gunakan judul yang jelas dan menarik</li>
                            <li>Buat pesan singkat dan informatif</li>
                            <li>Periksa penerima sebelum mengirim</li>
                            <li>Notifikasi akan muncul real-time</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleUserList() {
            const type = document.getElementById('typeSelect').value;
            const userListDiv = document.getElementById('userListDiv');
            
            if (type === 'specific') {
                userListDiv.style.display = 'block';
            } else {
                userListDiv.style.display = 'none';
            }
        }
        
        function selectAll() {
            document.querySelectorAll('input[name="user_ids[]"]').forEach(cb => {
                cb.checked = true;
            });
        }
        
        function deselectAll() {
            document.querySelectorAll('input[name="user_ids[]"]').forEach(cb => {
                cb.checked = false;
            });
        }
    </script>
</body>
</html>
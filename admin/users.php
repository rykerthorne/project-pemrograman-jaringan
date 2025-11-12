<?php
// admin/users.php
require_once '../includes/functions.php';
requireAdmin();

$db = getDB();

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'toggle_status') {
        $user_id = (int)$_POST['user_id'];
        $status = $_POST['status'] === 'active' ? 'inactive' : 'active';
        
        $stmt = $db->prepare("UPDATE users SET status = ? WHERE id = ?");
        $stmt->execute([$status, $user_id]);
        
        logAktivitas($_SESSION['user_id'], "Mengubah status user ID: $user_id menjadi $status");
        setAlert('success', 'Status user berhasil diubah!');
        
    } elseif ($action === 'delete') {
        $user_id = (int)$_POST['user_id'];
        
        // Cek apakah user memiliki booking aktif
        $check = $db->prepare("SELECT COUNT(*) as c FROM booking WHERE user_id = ? AND status_booking IN ('pending', 'approved')");
        $check->execute([$user_id]);
        $hasActive = $check->fetch()['c'] > 0;
        
        if ($hasActive) {
            setAlert('error', 'Tidak dapat menghapus user yang memiliki booking aktif!');
        } else {
            $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            
            logAktivitas($_SESSION['user_id'], "Menghapus user ID: $user_id");
            setAlert('success', 'User berhasil dihapus!');
        }
    }
    
    header('Location: users.php');
    exit();
}

// Get all users
$users = $db->query("
    SELECT u.*, 
    (SELECT COUNT(*) FROM booking WHERE user_id = u.id) as total_booking,
    (SELECT COUNT(*) FROM booking WHERE user_id = u.id AND status_booking = 'approved') as booking_approved
    FROM users u
    WHERE role = 'user'
    ORDER BY u.created_at DESC
")->fetchAll();

// Statistics
$total_users = count($users);
$active_users = count(array_filter($users, fn($u) => $u['status'] === 'active'));
$inactive_users = $total_users - $active_users;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola User - Admin</title>
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
        
        .stat-box p {
            color: #6c757d;
            margin: 0;
        }
        
        .card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(107, 163, 215, 0.08);
            border: none;
        }
        
        .card-header {
            background: transparent;
            border-bottom: 1px solid #f0f0f0;
            padding: 1.5rem;
            font-weight: 700;
            color: var(--dark-blue);
        }
        
        .card-body { padding: 1.5rem; }
        
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
        
        .badge {
            padding: 0.4rem 0.8rem;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.75rem;
        }
        
        .badge-success { background: #D4EDDA; color: #155724; }
        .badge-danger { background: #F8D7DA; color: #721C24; }
        
        .btn-sm { padding: 0.4rem 0.8rem; font-size: 0.85rem; border-radius: 6px; }
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
            <a href="users.php" class="menu-item active">
                <i class="fas fa-users"></i><span>Kelola User</span>
            </a>
            <a href="laporan.php" class="menu-item">
                <i class="fas fa-chart-bar"></i><span>Laporan</span>
            </a>
            <a href="notifikasi.php" class="menu-item">
                <i class="fas fa-bell"></i>
                <span>Kirim Notifikasi</span>
            </a>
            <hr>
            <a href="../auth/logout.php" class="menu-item">
                <i class="fas fa-sign-out-alt"></i><span>Logout</span>
            </a>
        </div>
    </div>
    
    <div class="main-content">
        <div class="topbar">
            <h1 class="topbar-title">Kelola User</h1>
        </div>
        
        <?php showAlert(); ?>
        
        <div class="stats-row">
            <div class="stat-box">
                <h3><?= $total_users ?></h3>
                <p><i class="fas fa-users me-1"></i>Total User</p>
            </div>
            <div class="stat-box">
                <h3><?= $active_users ?></h3>
                <p><i class="fas fa-check-circle me-1"></i>User Aktif</p>
            </div>
            <div class="stat-box">
                <h3><?= $inactive_users ?></h3>
                <p><i class="fas fa-times-circle me-1"></i>User Nonaktif</p>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <i class="fas fa-list me-2"></i>Daftar User
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Email</th>
                                <th>NIM/NIP</th>
                                <th>Fakultas</th>
                                <th>Total Booking</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $u): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="user-avatar">
                                            <?= strtoupper(substr($u['nama'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <strong><?= htmlspecialchars($u['nama']) ?></strong><br>
                                            <small class="text-muted">Bergabung <?= date('d M Y', strtotime($u['created_at'])) ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($u['email']) ?></td>
                                <td><?= htmlspecialchars($u['nim_nip']) ?></td>
                                <td><?= htmlspecialchars($u['fakultas']) ?></td>
                                <td>
                                    <strong><?= $u['total_booking'] ?></strong> booking<br>
                                    <small class="text-success"><?= $u['booking_approved'] ?> disetujui</small>
                                </td>
                                <td>
                                    <?php if ($u['status'] === 'active'): ?>
                                        <span class="badge badge-success">Aktif</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Nonaktif</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                        <input type="hidden" name="status" value="<?= $u['status'] ?>">
                                        <button type="submit" class="btn btn-sm btn-warning">
                                            <i class="fas fa-<?= $u['status'] === 'active' ? 'ban' : 'check' ?>"></i>
                                            <?= $u['status'] === 'active' ? 'Nonaktifkan' : 'Aktifkan' ?>
                                        </button>
                                    </form>
                                    <button class="btn btn-sm btn-danger" onclick="deleteUser(<?= $u['id'] ?>, '<?= htmlspecialchars($u['nama']) ?>')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <form method="POST" id="deleteForm" style="display:none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="user_id" id="delete_user_id">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function deleteUser(id, nama) {
            if (confirm('Hapus user "' + nama + '"?\n\nPerhatian: Riwayat booking user akan tetap tersimpan.')) {
                document.getElementById('delete_user_id').value = id;
                document.getElementById('deleteForm').submit();
            }
        }
    </script>
</body>
</html>
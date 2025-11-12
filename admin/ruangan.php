<?php
// admin/ruangan.php
require_once '../includes/functions.php';
requireAdmin();

$db = getDB();
$error = '';
$success = '';

// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $db->prepare("DELETE FROM ruangan WHERE id = ?");
    if ($stmt->execute([$id])) {
        logAktivitas($_SESSION['user_id'], 'Menghapus ruangan #' . $id);
        header('Location: ruangan.php?success=delete');
        exit;
    }
}

// Handle Add/Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $gedung_id = $_POST['gedung_id'];
    $nama_ruangan = trim($_POST['nama_ruangan']);
    $kode_ruangan = trim($_POST['kode_ruangan']);
    $kapasitas = $_POST['kapasitas'];
    $fasilitas = trim($_POST['fasilitas']);
    $status = $_POST['status'];
    
    if (empty($gedung_id) || empty($nama_ruangan) || empty($kode_ruangan) || empty($kapasitas)) {
        $error = 'Semua field wajib diisi!';
    } else {
        if ($id) {
            // Update
            $sql = "UPDATE ruangan SET gedung_id = ?, nama_ruangan = ?, kode_ruangan = ?, 
                    kapasitas = ?, fasilitas = ?, status = ? WHERE id = ?";
            $stmt = $db->prepare($sql);
            if ($stmt->execute([$gedung_id, $nama_ruangan, $kode_ruangan, $kapasitas, $fasilitas, $status, $id])) {
                logAktivitas($_SESSION['user_id'], 'Mengupdate ruangan #' . $id);
                $success = 'Ruangan berhasil diupdate!';
            }
        } else {
            // Insert
            $sql = "INSERT INTO ruangan (gedung_id, nama_ruangan, kode_ruangan, kapasitas, fasilitas, status) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($sql);
            if ($stmt->execute([$gedung_id, $nama_ruangan, $kode_ruangan, $kapasitas, $fasilitas, $status])) {
                logAktivitas($_SESSION['user_id'], 'Menambah ruangan baru: ' . $nama_ruangan);
                $success = 'Ruangan berhasil ditambahkan!';
            }
        }
    }
}

// Get all ruangan
$ruanganList = $db->query("
    SELECT r.*, g.nama_gedung 
    FROM ruangan r
    JOIN gedung g ON r.gedung_id = g.id
    ORDER BY g.nama_gedung, r.nama_ruangan
")->fetchAll();

// Get gedung list
$gedungList = $db->query("SELECT * FROM gedung ORDER BY nama_gedung")->fetchAll();

// Get edit data
$editData = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM ruangan WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $editData = $stmt->fetch();
}

$page_title = 'Manajemen Ruangan';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - Admin</title>
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
        }
        
        .table thead th {
            border-bottom: 2px solid var(--soft-blue);
            color: var(--dark-blue);
            font-weight: 600;
            padding: 1rem;
        }
        
        .btn-primary {
            background: var(--primary-blue);
            border: none;
        }
        
        .btn-sm {
            padding: 0.4rem 0.8rem;
            font-size: 0.85rem;
            border-radius: 6px;
        }
        
        .badge {
            padding: 0.4rem 0.8rem;
            border-radius: 6px;
            font-weight: 600;
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
            <a href="ruangan.php" class="menu-item active">
                <i class="fas fa-door-open"></i>
                <span>Kelola Ruangan</span>
            </a>
            <a href="booking.php" class="menu-item">
                <i class="fas fa-calendar-check"></i>
                <span>Kelola Booking</span>
            </a>
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
            <h1 class="topbar-title">Manajemen Ruangan</h1>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalRuangan">
                <i class="fas fa-plus me-2"></i>Tambah Ruangan
            </button>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i><?= $error ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i><?= $success ?></div>
        <?php endif; ?>
        
        <?php if (isset($_GET['success']) && $_GET['success'] === 'delete'): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>Ruangan berhasil dihapus!</div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header"><i class="fas fa-list me-2"></i>Daftar Ruangan</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Nama Ruangan</th>
                                <th>Gedung</th>
                                <th>Kapasitas</th>
                                <th>Fasilitas</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ruanganList as $ruangan): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($ruangan['kode_ruangan']) ?></strong></td>
                                <td><?= htmlspecialchars($ruangan['nama_ruangan']) ?></td>
                                <td><?= htmlspecialchars($ruangan['nama_gedung']) ?></td>
                                <td><?= $ruangan['kapasitas'] ?> orang</td>
                                <td><?= htmlspecialchars(substr($ruangan['fasilitas'], 0, 30)) ?>...</td>
                                <td>
                                    <?php if ($ruangan['status'] === 'tersedia'): ?>
                                        <span class="badge bg-success">Tersedia</span>
                                    <?php elseif ($ruangan['status'] === 'maintenance'): ?>
                                        <span class="badge bg-warning">Maintenance</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Tidak Tersedia</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="?edit=<?= $ruangan['id'] ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="?delete=<?= $ruangan['id'] ?>" class="btn btn-sm btn-danger" 
                                       onclick="return confirm('Yakin ingin menghapus?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal Form -->
    <div class="modal fade" id="modalRuangan" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?= $editData ? 'Edit' : 'Tambah' ?> Ruangan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="id" value="<?= $editData['id'] ?? '' ?>">
                        
                        <div class="mb-3">
                            <label class="form-label">Gedung <span class="text-danger">*</span></label>
                            <select class="form-select" name="gedung_id" required>
                                <option value="">Pilih Gedung</option>
                                <?php foreach ($gedungList as $gedung): ?>
                                    <option value="<?= $gedung['id'] ?>" 
                                            <?= ($editData && $editData['gedung_id'] == $gedung['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($gedung['nama_gedung']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Nama Ruangan <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nama_ruangan" 
                                   value="<?= $editData['nama_ruangan'] ?? '' ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Kode Ruangan <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="kode_ruangan" 
                                   value="<?= $editData['kode_ruangan'] ?? '' ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Kapasitas <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="kapasitas" 
                                   value="<?= $editData['kapasitas'] ?? '' ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Fasilitas</label>
                            <textarea class="form-control" name="fasilitas" rows="3"><?= $editData['fasilitas'] ?? '' ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <option value="tersedia" <?= ($editData && $editData['status'] === 'tersedia') ? 'selected' : '' ?>>Tersedia</option>
                                <option value="maintenance" <?= ($editData && $editData['status'] === 'maintenance') ? 'selected' : '' ?>>Maintenance</option>
                                <option value="tidak_tersedia" <?= ($editData && $editData['status'] === 'tidak_tersedia') ? 'selected' : '' ?>>Tidak Tersedia</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php if ($editData): ?>
    <script>
        var modal = new bootstrap.Modal(document.getElementById('modalRuangan'));
        modal.show();
    </script>
    <?php endif; ?>
</body>
</html>
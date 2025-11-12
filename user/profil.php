 <?php
// user/profil.php
require_once '../includes/functions.php';
requireLogin();

$db = getDB();
$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Get user data
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $nama = trim($_POST['nama']);
        $nim_nip = trim($_POST['nim_nip']);
        $fakultas = trim($_POST['fakultas']);
        $no_telp = trim($_POST['no_telp']);
        
        if (empty($nama) || empty($nim_nip)) {
            $error = 'Nama dan NIM/NIP wajib diisi!';
        } else {
            $sql = "UPDATE users SET nama = ?, nim_nip = ?, fakultas = ?, no_telp = ? WHERE id = ?";
            $stmt = $db->prepare($sql);
            if ($stmt->execute([$nama, $nim_nip, $fakultas, $no_telp, $user_id])) {
                $_SESSION['nama'] = $nama;
                logAktivitas($user_id, 'Mengupdate profil');
                $success = 'Profil berhasil diupdate!';
                
                // Refresh user data
                $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch();
            } else {
                $error = 'Gagal mengupdate profil!';
            }
        }
    } elseif (isset($_POST['change_password'])) {
        $old_password = $_POST['old_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if (!password_verify($old_password, $user['password'])) {
            $error = 'Password lama salah!';
        } elseif (strlen($new_password) < 6) {
            $error = 'Password baru minimal 6 karakter!';
        } elseif ($new_password !== $confirm_password) {
            $error = 'Konfirmasi password tidak cocok!';
        } else {
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
            if ($stmt->execute([$hashed, $user_id])) {
                logAktivitas($user_id, 'Mengubah password');
                $success = 'Password berhasil diubah!';
            } else {
                $error = 'Gagal mengubah password!';
            }
        }
    }
}

$page_title = 'Profil Saya';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - Booking UCA</title>
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
        
        .card-body {
            padding: 2rem;
        }
        
        .profile-header {
            text-align: center;
            padding: 2rem;
            background: linear-gradient(135deg, var(--primary-blue), var(--light-blue));
            border-radius: 15px;
            color: white;
            margin-bottom: 2rem;
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 3rem;
            color: var(--primary-blue);
            font-weight: 800;
        }
        
        .form-label {
            font-weight: 600;
            color: var(--dark-blue);
            margin-bottom: 0.5rem;
        }
        
        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 0.2rem rgba(107, 163, 215, 0.15);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-blue), var(--light-blue));
            border: none;
            padding: 0.8rem 2rem;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(107, 163, 215, 0.3);
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
            <a href="booking.php" class="menu-item">
                <i class="fas fa-plus-circle"></i>
                <span>Booking Baru</span>
            </a>
            <a href="riwayat.php" class="menu-item">
                <i class="fas fa-history"></i>
                <span>Riwayat Booking</span>
            </a>
            <a href="notifikasi.php" class="menu-item">
                <i class="fas fa-bell"></i><span>Notifikasi</span>
            </a>
            <a href="profil.php" class="menu-item active">
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
            <h1 class="topbar-title">Profil Saya</h1>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i><?= $error ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i><?= $success ?>
            </div>
        <?php endif; ?>
        
        <div class="profile-header">
            <div class="profile-avatar">
                <?= strtoupper(substr($user['nama'], 0, 1)) ?>
            </div>
            <h3><?= htmlspecialchars($user['nama']) ?></h3>
            <p class="mb-0"><?= htmlspecialchars($user['email']) ?></p>
        </div>
        
        <div class="row">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-user-edit me-2"></i>Update Profil
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label for="nama" class="form-label">Nama Lengkap</label>
                                <input type="text" class="form-control" id="nama" name="nama" 
                                       value="<?= htmlspecialchars($user['nama']) ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" disabled>
                                <small class="text-muted">Email tidak dapat diubah</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="nim_nip" class="form-label">NIM/NIP</label>
                                <input type="text" class="form-control" id="nim_nip" name="nim_nip" 
                                       value="<?= htmlspecialchars($user['nim_nip']) ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="fakultas" class="form-label">Fakultas/Unit</label>
                                <input type="text" class="form-control" id="fakultas" name="fakultas" 
                                       value="<?= htmlspecialchars($user['fakultas']) ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="no_telp" class="form-label">No. Telepon</label>
                                <input type="tel" class="form-control" id="no_telp" name="no_telp" 
                                       value="<?= htmlspecialchars($user['no_telp']) ?>">
                            </div>
                            
                            <button type="submit" name="update_profile" class="btn btn-primary w-100">
                                <i class="fas fa-save me-2"></i>Simpan Perubahan
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-lock me-2"></i>Ubah Password
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label for="old_password" class="form-label">Password Lama</label>
                                <input type="password" class="form-control" id="old_password" name="old_password" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="new_password" class="form-label">Password Baru</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" required>
                                <small class="text-muted">Minimal 6 karakter</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Konfirmasi Password Baru</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            
                            <button type="submit" name="change_password" class="btn btn-primary w-100">
                                <i class="fas fa-key me-2"></i>Ubah Password
                            </button>
                        </form>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-info-circle me-2"></i>Informasi Akun
                    </div>
                    <div class="card-body">
                        <table class="table table-borderless mb-0">
                            <tr>
                                <td><strong>Status</strong></td>
                                <td><span class="badge bg-success">Aktif</span></td>
                            </tr>
                            <tr>
                                <td><strong>Role</strong></td>
                                <td><?= ucfirst($user['role']) ?></td>
                            </tr>
                            <tr>
                                <td><strong>Terdaftar</strong></td>
                                <td><?= formatTanggal(date('Y-m-d', strtotime($user['created_at']))) ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

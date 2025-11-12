 <?php
// auth/register.php
session_start();
require_once '../config/database.php';

if (isset($_SESSION['user_id'])) {
    header('Location: ../user/index.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $nim_nip = trim($_POST['nim_nip']);
    $fakultas = trim($_POST['fakultas']);
    $no_telp = trim($_POST['no_telp']);
    
    // Validasi
    if (empty($nama) || empty($email) || empty($password) || empty($nim_nip)) {
        $error = 'Semua field wajib diisi!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid!';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter!';
    } elseif ($password !== $confirm_password) {
        $error = 'Password dan konfirmasi password tidak cocok!';
    } else {
        $db = getDB();
        
        // Cek email sudah terdaftar
        $checkEmail = $db->prepare("SELECT id FROM users WHERE email = ?");
        $checkEmail->execute([$email]);
        
        if ($checkEmail->fetch()) {
            $error = 'Email sudah terdaftar!';
        } else {
            // Insert user baru
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            $sql = "INSERT INTO users (nama, email, password, role, nim_nip, fakultas, no_telp) 
                    VALUES (:nama, :email, :password, 'user', :nim_nip, :fakultas, :no_telp)";
            
            $stmt = $db->prepare($sql);
            $result = $stmt->execute([
                ':nama' => $nama,
                ':email' => $email,
                ':password' => $hashedPassword,
                ':nim_nip' => $nim_nip,
                ':fakultas' => $fakultas,
                ':no_telp' => $no_telp
            ]);
            
            if ($result) {
                $success = 'Registrasi berhasil! Silakan login.';
            } else {
                $error = 'Terjadi kesalahan saat registrasi!';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Booking UCA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-blue: #6BA3D7;
            --light-blue: #A8D0F0;
            --soft-blue: #E8F4F8;
            --dark-blue: #4A7BA7;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--soft-blue) 0%, #ffffff 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 0;
        }
        
        .register-container {
            max-width: 600px;
            width: 100%;
            padding: 20px;
        }
        
        .register-card {
            background: white;
            border-radius: 25px;
            padding: 3rem;
            box-shadow: 0 20px 60px rgba(107, 163, 215, 0.15);
            animation: fadeInUp 0.6s ease;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .register-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .register-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary-blue), var(--light-blue));
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 2.5rem;
            color: white;
        }
        
        .register-title {
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--dark-blue);
            margin-bottom: 0.5rem;
        }
        
        .register-subtitle {
            color: #6c757d;
            font-size: 0.95rem;
        }
        
        .form-label {
            font-weight: 600;
            color: var(--dark-blue);
            margin-bottom: 0.5rem;
        }
        
        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 0.2rem rgba(107, 163, 215, 0.15);
        }
        
        .btn-register {
            background: linear-gradient(135deg, var(--primary-blue), var(--light-blue));
            border: none;
            border-radius: 12px;
            padding: 0.85rem;
            font-weight: 600;
            font-size: 1.05rem;
            width: 100%;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(107, 163, 215, 0.3);
        }
        
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(107, 163, 215, 0.4);
        }
        
        .login-link {
            text-align: center;
            margin-top: 1.5rem;
            color: #6c757d;
        }
        
        .login-link a {
            color: var(--primary-blue);
            font-weight: 600;
            text-decoration: none;
        }
        
        .back-home {
            text-align: center;
            margin-top: 1rem;
        }
        
        .back-home a {
            color: var(--primary-blue);
            text-decoration: none;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-card">
            <div class="register-header">
                <div class="register-icon">
                    <i class="fas fa-user-plus"></i>
                </div>
                <h1 class="register-title">Buat Akun Baru</h1>
                <p class="register-subtitle">Daftar untuk mulai booking ruangan</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><?= $error ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?= $success ?>
                    <a href="login.php" class="alert-link">Login sekarang</a>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="nama" class="form-label">Nama Lengkap</label>
                        <input type="text" class="form-control" id="nama" name="nama" required>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="nim_nip" class="form-label">NIM/NIP</label>
                        <input type="text" class="form-control" id="nim_nip" name="nim_nip" required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" 
                           placeholder="nama@uca.ac.id" required>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="fakultas" class="form-label">Fakultas/Unit</label>
                        <input type="text" class="form-control" id="fakultas" name="fakultas">
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="no_telp" class="form-label">No. Telepon</label>
                        <input type="tel" class="form-control" id="no_telp" name="no_telp">
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    
                    <div class="col-md-6 mb-4">
                        <label for="confirm_password" class="form-label">Konfirmasi Password</label>
                        <input type="password" class="form-control" id="confirm_password" 
                               name="confirm_password" required>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-register">
                    <i class="fas fa-user-plus me-2"></i>Daftar
                </button>
            </form>
            
            <div class="login-link">
                Sudah punya akun? <a href="login.php">Login di sini</a>
            </div>
            
            <div class="back-home">
                <a href="../index.php">
                    <i class="fas fa-arrow-left me-1"></i>Kembali ke Beranda
                </a>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

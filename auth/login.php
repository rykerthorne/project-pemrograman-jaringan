 
<?php
// auth/login.php
session_start();
require_once '../config/database.php';

// Redirect jika sudah login
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: ../admin/index.php');
    } else {
        header('Location: ../user/index.php');
    }
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error = 'Email dan password harus diisi!';
    } else {
        $db = getDB();
        $sql = "SELECT * FROM users WHERE email = :email AND status = 'active'";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['nama'] = $user['nama'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['foto_profil'] = $user['foto_profil'];
            
            // Log aktivitas
            $ip_address = $_SERVER['REMOTE_ADDR'];
            $user_agent = $_SERVER['HTTP_USER_AGENT'];
            $aktivitas = "Login ke sistem";
            
            $logSql = "INSERT INTO log_aktivitas (user_id, aktivitas, ip_address, user_agent) 
                       VALUES (:user_id, :aktivitas, :ip_address, :user_agent)";
            $logStmt = $db->prepare($logSql);
            $logStmt->execute([
                ':user_id' => $user['id'],
                ':aktivitas' => $aktivitas,
                ':ip_address' => $ip_address,
                ':user_agent' => $user_agent
            ]);
            
            if ($user['role'] === 'admin') {
                header('Location: ../admin/index.php');
            } else {
                header('Location: ../user/index.php');
            }
            exit();
        } else {
            $error = 'Email atau password salah!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Booking Ruangan UCA</title>
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
        }
        
        .login-container {
            max-width: 450px;
            width: 100%;
            padding: 20px;
        }
        
        .login-card {
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
        
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-icon {
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
        
        .login-title {
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--dark-blue);
            margin-bottom: 0.5rem;
        }
        
        .login-subtitle {
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
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 0.2rem rgba(107, 163, 215, 0.15);
        }
        
        .input-group-text {
            background: transparent;
            border: 2px solid #e9ecef;
            border-right: none;
            border-radius: 12px 0 0 12px;
            color: var(--primary-blue);
        }
        
        .input-group .form-control {
            border-left: none;
            border-radius: 0 12px 12px 0;
        }
        
        .input-group:focus-within .input-group-text {
            border-color: var(--primary-blue);
        }
        
        .btn-login {
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
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(107, 163, 215, 0.4);
        }
        
        .alert {
            border-radius: 12px;
            border: none;
            padding: 1rem;
        }
        
        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 1.5rem 0;
            color: #6c757d;
        }
        
        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #e9ecef;
        }
        
        .divider span {
            padding: 0 1rem;
            font-size: 0.9rem;
        }
        
        .register-link {
            text-align: center;
            margin-top: 1.5rem;
            color: #6c757d;
        }
        
        .register-link a {
            color: var(--primary-blue);
            font-weight: 600;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .register-link a:hover {
            color: var(--dark-blue);
        }
        
        .back-home {
            text-align: center;
            margin-top: 1rem;
        }
        
        .back-home a {
            color: var(--primary-blue);
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }
        
        .back-home a:hover {
            color: var(--dark-blue);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="login-icon">
                    <i class="fas fa-building"></i>
                </div>
                <h1 class="login-title">Selamat Datang!</h1>
                <p class="login-subtitle">Masuk ke Sistem Booking Ruangan UCA</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><?= $error ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-envelope"></i>
                        </span>
                        <input type="email" class="form-control" id="email" name="email" 
                               placeholder="Masukan Email" required>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input type="password" class="form-control" id="password" name="password" 
                               placeholder="Masukkan password" required>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-login">
                    <i class="fas fa-sign-in-alt me-2"></i>Masuk
                </button>
            </form>
            
            <div class="divider">
                <span>atau</span>
            </div>
            
            <div class="register-link">
                Belum punya akun? <a href="register.php">Daftar Sekarang</a>
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
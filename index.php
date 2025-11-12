<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Booking Ruangan - Kampus UCA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-blue: #6BA3D7;
            --light-blue: #A8D0F0;
            --soft-blue: #E8F4F8;
            --dark-blue: #4A7BA7;
            --gradient-start: #7FB3D5;
            --gradient-end: #B3D9F2;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--soft-blue) 0%, #ffffff 100%);
            min-height: 100vh;
        }
        
        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 20px rgba(107, 163, 215, 0.1);
            padding: 1rem 0;
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            color: var(--dark-blue) !important;
        }
        
        .nav-link {
            color: var(--dark-blue) !important;
            font-weight: 500;
            margin: 0 0.5rem;
            transition: all 0.3s ease;
        }
        
        .nav-link:hover {
            color: var(--primary-blue) !important;
            transform: translateY(-2px);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-blue), var(--light-blue));
            border: none;
            padding: 0.6rem 2rem;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(107, 163, 215, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(107, 163, 215, 0.4);
        }
        
        .btn-outline-primary {
            color: var(--primary-blue);
            border: 2px solid var(--primary-blue);
            padding: 0.6rem 2rem;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-outline-primary:hover {
            background: var(--primary-blue);
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(107, 163, 215, 0.4);
        }
        
        .hero-section {
            padding: 100px 0;
            text-align: center;
        }
        
        .hero-title {
            font-size: 3.3rem;
            font-weight: 600;
            background: linear-gradient(135deg, var(--dark-blue), var(--primary-blue));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 1.5rem;
            animation: fadeInUp 0.8s ease;
        }
        
        .hero-subtitle {
            font-size: 1.3rem;
            color: var(--dark-blue);
            margin-bottom: 2.5rem;
            animation: fadeInUp 0.8s ease 0.2s both;
        }
        
        .hero-buttons {
            animation: fadeInUp 0.8s ease 0.4s both;
        }
        
        .hero-image {
            animation: fadeInUp 0.8s ease 0.6s both;
            margin-top: 3rem;
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
        
        .feature-card {
            background: white;
            border-radius: 20px;
            padding: 2.5rem;
            text-align: center;
            box-shadow: 0 10px 30px rgba(107, 163, 215, 0.1);
            transition: all 0.3s ease;
            border: 1px solid rgba(107, 163, 215, 0.1);
            height: 100%;
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(107, 163, 215, 0.2);
        }
        
        .feature-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 2rem;
            color: white;
        }
        
        .feature-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--dark-blue);
            margin-bottom: 1rem;
        }
        
        .feature-text {
            color: #6c757d;
            line-height: 1.6;
        }
        
        .section-title {
            font-size: 2.5rem;
            font-weight: 600;
            text-align: center;
            margin-bottom: 3rem;
            color: var(--dark-blue);
        }
        
        .stats-section {
            background: linear-gradient(135deg, var(--primary-blue), var(--light-blue));
            padding: 80px 0;
            color: white;
            margin: 80px 0;
        }
        
        .stat-card {
            text-align: center;
            padding: 2rem;
        }
        
        .stat-number {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        footer {
            background: var(--dark-blue);
            color: white;
            padding: 3rem 0 1rem;
            margin-top: 80px;
        }
        
        .footer-links a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .footer-links a:hover {
            color: white;
        }
        
        .illustration {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            opacity: 0.9;
        }

        .about-image {
            max-width: 90%;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(107, 163, 215, 0.15);
        }

    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-building"></i> Booking UCA
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#beranda">Beranda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#fitur">Fitur</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#tentang">Tentang</a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-primary ms-3" href="auth/login.php">
                            <i class="fas fa-sign-in-alt"></i> Masuk
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section" id="beranda" style="margin-top: 80px;">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="hero-title">Booking Ruangan Kampus Universitas Cendekia Abditama Jadi Lebih Mudah</h1>
                    <p class="hero-subtitle">Sistem booking ruangan modern untuk Kampus UCA. Booking ruangan untuk agenda rapat organisasi, seminar, dan meeting dengan cepat dan efisien.</p>
                    <div class="hero-buttons">
                        <a href="auth/register.php" class="btn btn-primary btn-lg me-3">
                            <i class="fas fa-user-plus"></i> Daftar Sekarang
                        </a>
                        <a href="auth/login.php" class="btn btn-outline-primary btn-lg">
                            <i class="fas fa-sign-in-alt"></i> Masuk
                        </a>
                    </div>
                </div>
                <div class="col-lg-6 hero-image">
                    <div class="illustration">
                        <svg viewBox="0 0 500 400" xmlns="http://www.w3.org/2000/svg">
                            <rect x="50" y="100" width="400" height="250" rx="20" fill="#E8F4F8"/>
                            <rect x="70" y="120" width="160" height="200" rx="10" fill="#ffffff"/>
                            <rect x="270" y="120" width="160" height="200" rx="10" fill="#ffffff"/>
                            <circle cx="150" cy="180" r="30" fill="#6BA3D7"/>
                            <circle cx="350" cy="180" r="30" fill="#A8D0F0"/>
                            <rect x="90" y="230" width="120" height="15" rx="7" fill="#E8F4F8"/>
                            <rect x="90" y="260" width="100" height="15" rx="7" fill="#E8F4F8"/>
                            <rect x="290" y="230" width="120" height="15" rx="7" fill="#E8F4F8"/>
                            <rect x="290" y="260" width="100" height="15" rx="7" fill="#E8F4F8"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    </section>

   

    <!-- Features Section -->
    <section class="py-5" id="fitur">
        <div class="container">
            <h2 class="section-title">Fitur Unggulan</h2>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <h3 class="feature-title">Booking Cepat</h3>
                        <p class="feature-text">Pesan ruangan dengan mudah dan cepat dalam hitungan detik. Lihat ketersediaan real-time.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-bell"></i>
                        </div>
                        <h3 class="feature-title">Notifikasi Otomatis</h3>
                        <p class="feature-text">Dapatkan notifikasi instant untuk setiap perubahan status booking Anda.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h3 class="feature-title">Dashboard Lengkap</h3>
                        <p class="feature-text">Monitor semua booking dan aktivitas Anda dalam satu dashboard yang intuitif.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h3 class="feature-title">Aman & Terpercaya</h3>
                        <p class="feature-text">Data Anda terlindungi dengan sistem keamanan berlapis.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                        <h3 class="feature-title">Responsive Design</h3>
                        <p class="feature-text">Akses dari perangkat apapun - desktop, tablet, atau smartphone.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-headset"></i>
                        </div>
                        <h3 class="feature-title">Support 24/7</h3>
                        <p class="feature-text">Tim support kami siap membantu Anda kapan saja.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
<section class="py-5" id="tentang">
    <div class="container">
        <div class="row align-items-center">
            <!-- Kolom teks -->
            <div class="col-lg-6 order-2 order-lg-1">
                <h2 class="section-title text-start">Tentang Sistem Kami</h2>
                <p class="lead mb-4">Sistem Booking Ruangan Kampus UCA dirancang khusus untuk memudahkan mahasiswa, dosen, dan staff dalam memesan ruangan kampus.</p>
                <p class="lead mb-4">Dengan antarmuka yang modern dan user-friendly, proses booking menjadi lebih efisien dan terorganisir dengan baik.</p>
                <ul class="list-unstyled">
                    <li class="mb-2"><i class="fas fa-check-circle text-primary me-2"></i> Proses booking yang cepat</li>
                    <li class="mb-2"><i class="fas fa-check-circle text-primary me-2"></i> Real-time availability</li>
                    <li class="mb-2"><i class="fas fa-check-circle text-primary me-2"></i> Approval system yang terstruktur</li>
                    <li class="mb-2"><i class="fas fa-check-circle text-primary me-2"></i> Laporan dan analytics</li>
                </ul>
            </div>

            <!-- Kolom gambar -->
            <div class="col-lg-6 order-1 order-lg-2 text-center text-lg-end">
                <img src="assets/images/uca.png" alt="About" class="img-fluid rounded shadow about-image">
            </div>
        </div>
    </div>
</section>


    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <h5 class="mb-3"><i class="fas fa-building"></i> Booking UCA</h5>
                    <p>Sistem booking ruangan modern untuk Universitas Cendekia Abditama. </p>
                    <p>Membantu civitas akademik dalam mempermudah proses peminjaman ruangan secara online.</p>
                </div>
                <div class="col-md-4 mb-4">
                    <h5 class="mb-3">Quick Links</h5>
                    <div class="footer-links">
                        <a href="#" class="d-block mb-2">Beranda</a>
                        <a href="#" class="d-block mb-2">Fitur</a>
                        <a href="#" class="d-block mb-2">Tentang</a>
                        <a href="#" class="d-block mb-2">Kontak</a>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <h5 class="mb-3">Kontak</h5>
                    <p><i class="fas fa-envelope me-2"></i> info@uca.ac.id</p>
                    <p><i class="fas fa-phone me-2"></i> (021) 1234-5678</p>
                    <p><i class="fas fa-map-marker-alt me-2"></i> Kampus UCA, Jakarta</p>
                </div>
            </div>
            <hr style="border-color: rgba(255,255,255,0.1);">
            <div class="text-center py-3">
                <p class="mb-0">&copy; 2025 Sistem Booking Ruangan UCA. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
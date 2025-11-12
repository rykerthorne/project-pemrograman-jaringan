<?php
// user/booking.php
require_once '../includes/functions.php';
requireLogin();

$db = getDB();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ruangan_id = (int)$_POST['ruangan_id'];
    $tanggal = clean($_POST['tanggal_booking']);
    $jam_mulai = clean($_POST['jam_mulai']);
    $jam_selesai = clean($_POST['jam_selesai']);
    $keperluan = clean($_POST['keperluan']);
    $jumlah_peserta = (int)$_POST['jumlah_peserta'];
    
    // Validasi
    if (empty($ruangan_id) || empty($tanggal) || empty($jam_mulai) || empty($jam_selesai) || empty($keperluan)) {
        setAlert('error', 'Semua field harus diisi!');
    } elseif ($tanggal < date('Y-m-d')) {
        setAlert('error', 'Tidak dapat booking tanggal yang sudah lewat!');
    } elseif ($jam_mulai >= $jam_selesai) {
        setAlert('error', 'Jam selesai harus lebih besar dari jam mulai!');
    } elseif (!cekKetersediaan($ruangan_id, $tanggal, $jam_mulai, $jam_selesai)) {
        setAlert('error', 'Ruangan sudah dibooking pada waktu tersebut!');
    } else {
        // Insert booking
        $sql = "INSERT INTO booking (user_id, ruangan_id, tanggal_booking, jam_mulai, jam_selesai, keperluan, jumlah_peserta) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        $result = $stmt->execute([
            $_SESSION['user_id'],
            $ruangan_id,
            $tanggal,
            $jam_mulai,
            $jam_selesai,
            $keperluan,
            $jumlah_peserta
        ]);
        
        if ($result) {
            $booking_id = $db->lastInsertId();
            
            // Add notification
            addNotifikasi(
                $_SESSION['user_id'],
                $booking_id,
                'Booking Berhasil Dibuat',
                'Booking Anda telah dibuat dan menunggu persetujuan admin.'
            );
            
            logAktivitas($_SESSION['user_id'], "Membuat booking ruangan untuk tanggal $tanggal");
            setAlert('success', 'Booking berhasil dibuat! Menunggu persetujuan admin.');
            header('Location: riwayat.php');
            exit();
        } else {
            setAlert('error', 'Gagal membuat booking!');
        }
    }
}

// Get all buildings
$gedung = $db->query("SELECT * FROM gedung ORDER BY nama_gedung")->fetchAll();

// Get all available rooms with photo
$ruangan = $db->query("
    SELECT r.*, g.nama_gedung 
    FROM ruangan r
    JOIN gedung g ON r.gedung_id = g.id
    WHERE r.status = 'tersedia'
    ORDER BY g.nama_gedung, r.kode_ruangan
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Ruangan - UCA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-blue: #6BA3D7;
            --light-blue: #A8D0F0;
            --soft-blue: #E8F4F8;
            --dark-blue: #4A7BA7;
            --sidebar-width: 260px;
            --success-green: #28a745;
            --warning-orange: #ffc107;
            --danger-red: #dc3545;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #E8F4F8 0%, #ffffff 100%);
            min-height: 100vh;
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
        
        .menu-item:hover, .menu-item.active {
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
        
        .page-header {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 8px 30px rgba(107, 163, 215, 0.12);
            position: relative;
            overflow: hidden;
        }
        
        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 300px;
            height: 300px;
            background: linear-gradient(135deg, var(--primary-blue), var(--light-blue));
            border-radius: 50%;
            opacity: 0.1;
            transform: translate(30%, -30%);
        }
        
        .page-title {
            font-size: 2rem;
            font-weight: 800;
            color: var(--dark-blue);
            margin-bottom: 0.5rem;
            position: relative;
        }
        
        .page-subtitle {
            color: #6c757d;
            font-size: 1rem;
            position: relative;
        }
        
        .progress-steps {
            display: flex;
            justify-content: space-between;
            margin: 2rem 0;
            position: relative;
        }
        
        .progress-steps::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 0;
            right: 0;
            height: 2px;
            background: #e9ecef;
            z-index: 0;
        }
        
        .step {
            flex: 1;
            text-align: center;
            position: relative;
            z-index: 1;
        }
        
        .step-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: white;
            border: 3px solid #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 0.5rem;
            font-weight: bold;
            color: #6c757d;
            transition: all 0.3s ease;
        }
        
        .step.active .step-circle {
            background: linear-gradient(135deg, var(--primary-blue), var(--light-blue));
            border-color: var(--primary-blue);
            color: white;
            box-shadow: 0 4px 15px rgba(107, 163, 215, 0.4);
        }
        
        .step.completed .step-circle {
            background: var(--success-green);
            border-color: var(--success-green);
            color: white;
        }
        
        .step-label {
            font-size: 0.85rem;
            color: #6c757d;
            font-weight: 600;
        }
        
        .step.active .step-label {
            color: var(--primary-blue);
        }
        
        .card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 8px 30px rgba(107, 163, 215, 0.12);
            border: none;
            margin-bottom: 2rem;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .card:hover {
            box-shadow: 0 12px 40px rgba(107, 163, 215, 0.18);
        }
        
        .card-header-custom {
            background: linear-gradient(135deg, var(--primary-blue), var(--light-blue));
            color: white;
            padding: 1.5rem 2rem;
            font-weight: 700;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .card-body {
            padding: 2rem;
        }
        
        .info-banner {
            background: linear-gradient(135deg, #fff3cd, #ffeaa7);
            border-left: 4px solid var(--warning-orange);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .info-banner-icon {
            font-size: 2rem;
            color: var(--warning-orange);
            margin-bottom: 1rem;
        }
        
        .info-list {
            margin: 0;
            padding-left: 1.5rem;
        }
        
        .info-list li {
            margin-bottom: 0.5rem;
            color: #856404;
        }
        
        .filter-section {
            background: var(--soft-blue);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .room-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        .room-card {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
        }
        
        .room-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 30px rgba(107, 163, 215, 0.2);
            border-color: var(--primary-blue);
        }
        
        .room-card.selected {
            border-color: var(--primary-blue);
            box-shadow: 0 12px 30px rgba(107, 163, 215, 0.3);
            transform: translateY(-5px);
        }
        
        .room-card.selected::before {
            content: '✓';
            position: absolute;
            top: 15px;
            right: 15px;
            width: 35px;
            height: 35px;
            background: var(--success-green);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            font-weight: bold;
            z-index: 10;
            box-shadow: 0 4px 10px rgba(40, 167, 69, 0.3);
        }
        
        .room-image {
            width: 100%;
            height: 180px;
            object-fit: cover;
            background: linear-gradient(135deg, var(--soft-blue), var(--light-blue));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: var(--primary-blue);
        }
        
        .room-content {
            padding: 1.5rem;
        }
        
        .room-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--dark-blue);
            margin-bottom: 0.5rem;
        }
        
        .room-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin: 1rem 0;
            padding: 1rem;
            background: var(--soft-blue);
            border-radius: 8px;
        }
        
        .room-meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--dark-blue);
            font-size: 0.9rem;
        }
        
        .room-meta-item i {
            color: var(--primary-blue);
        }
        
        .room-facilities {
            color: #6c757d;
            font-size: 0.85rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .form-section {
            position: sticky;
            top: 2rem;
        }
        
        .form-label {
            font-weight: 600;
            color: var(--dark-blue);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .form-control, .form-select {
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 0.85rem 1.2rem;
            transition: all 0.3s ease;
            font-size: 0.95rem;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 0.2rem rgba(107, 163, 215, 0.15);
        }
        
        .time-input-group {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        .availability-status {
            padding: 1.2rem;
            border-radius: 12px;
            margin: 1.5rem 0;
            display: none;
            font-weight: 600;
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .availability-status.available {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
            border-left: 4px solid var(--success-green);
            display: block;
        }
        
        .availability-status.unavailable {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            color: #721c24;
            border-left: 4px solid var(--danger-red);
            display: block;
        }
        
        .availability-status i {
            font-size: 1.2rem;
            margin-right: 0.5rem;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-blue), var(--light-blue));
            border: none;
            padding: 1rem 2rem;
            border-radius: 12px;
            font-weight: 700;
            font-size: 1rem;
            transition: all 0.3s ease;
            box-shadow: 0 6px 20px rgba(107, 163, 215, 0.3);
            position: relative;
            overflow: hidden;
        }
        
        .btn-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s ease;
        }
        
        .btn-primary:hover::before {
            left: 100%;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(107, 163, 215, 0.4);
        }
        
        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: #6c757d;
        }
        
        .empty-state-icon {
            font-size: 4rem;
            opacity: 0.3;
            margin-bottom: 1rem;
        }
        
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
            
            .sidebar {
                transform: translateX(-100%);
            }
            
            .room-grid {
                grid-template-columns: 1fr;
            }
            
            .time-input-group {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <a href="index.php" class="sidebar-brand">
                <i class="fas fa-building"></i>
                <span>Booking UCA</span>
            </a>
        </div>
        <div class="sidebar-menu">
            <a href="index.php" class="menu-item">
                <i class="fas fa-home"></i><span>Dashboard</span>
            </a>
            <a href="booking.php" class="menu-item active">
                <i class="fas fa-plus-circle"></i><span>Booking Baru</span>
            </a>
            <a href="riwayat.php" class="menu-item">
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
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-calendar-plus me-3"></i>Buat Booking Ruangan
            </h1>
            <p class="page-subtitle">Pilih ruangan dan tentukan waktu booking Anda dengan mudah</p>
        </div>
        
        <?php showAlert(); ?>
        
        <div class="progress-steps">
            <div class="step active" id="step1">
                <div class="step-circle">1</div>
                <div class="step-label">Pilih Ruangan</div>
            </div>
            <div class="step" id="step2">
                <div class="step-circle">2</div>
                <div class="step-label">Tentukan Waktu</div>
            </div>
            <div class="step" id="step3">
                <div class="step-circle">3</div>
                <div class="step-label">Detail Booking</div>
            </div>
            <div class="step" id="step4">
                <div class="step-circle">4</div>
                <div class="step-label">Konfirmasi</div>
            </div>
        </div>
        
        <div class="info-banner">
            <div class="info-banner-icon">
                <i class="fas fa-info-circle"></i>
            </div>
            <strong style="color: #856404; font-size: 1.1rem;">Informasi Penting</strong>
            <ul class="info-list mt-2">
                <li>Booking akan diproses oleh admin maksimal <strong>1x24 jam</strong></li>
                <li>Pastikan mengisi data dengan <strong>benar dan lengkap</strong></li>
                <li>Ruangan yang sudah dibooking tidak dapat dibooking lagi pada waktu yang sama</li>
                <li>Anda akan menerima <strong>notifikasi</strong> setelah booking disetujui/ditolak</li>
            </ul>
        </div>
        
        <form method="POST" id="bookingForm">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header-custom">
                            <i class="fas fa-door-open"></i>
                            Pilih Ruangan yang Tersedia
                        </div>
                        <div class="card-body">
                            <div class="filter-section">
                                <label class="form-label">
                                    <i class="fas fa-filter"></i>
                                    Filter Berdasarkan Gedung
                                </label>
                                <select class="form-select" id="filter_gedung">
                                    <option value="">Tampilkan Semua Gedung</option>
                                    <?php foreach ($gedung as $g): ?>
                                        <option value="<?= $g['id'] ?>"><?= htmlspecialchars($g['nama_gedung']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="room-grid" id="roomList">
                                <?php if (empty($ruangan)): ?>
                                    <div class="empty-state">
                                        <div class="empty-state-icon">
                                            <i class="fas fa-door-closed"></i>
                                        </div>
                                        <h5>Tidak Ada Ruangan Tersedia</h5>
                                        <p>Saat ini tidak ada ruangan yang tersedia untuk dibooking</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($ruangan as $r): ?>
                                    <div class="room-card" data-gedung="<?= $r['gedung_id'] ?>" onclick="selectRoom(<?= $r['id'] ?>, this)">
                                        <div class="room-image">
                                            <i class="fas fa-door-open"></i>
                                        </div>
                                        <div class="room-content">
                                            <h3 class="room-title"><?= htmlspecialchars($r['nama_ruangan']) ?></h3>
                                            <div class="room-meta">
                                                <div class="room-meta-item">
                                                    <i class="fas fa-building"></i>
                                                    <span><?= htmlspecialchars($r['nama_gedung']) ?></span>
                                                </div>
                                                <div class="room-meta-item">
                                                    <i class="fas fa-tag"></i>
                                                    <span><?= htmlspecialchars($r['kode_ruangan']) ?></span>
                                                </div>
                                                <div class="room-meta-item">
                                                    <i class="fas fa-users"></i>
                                                    <span><?= $r['kapasitas'] ?> orang</span>
                                                </div>
                                            </div>
                                            <div class="room-facilities">
                                                <i class="fas fa-toolbox" style="color: var(--primary-blue);"></i>
                                                <?= htmlspecialchars($r['fasilitas']) ?>
                                            </div>
                                            <input type="radio" name="ruangan_id" value="<?= $r['id'] ?>" required style="display:none;">
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <div class="form-section">
                        <div class="card">
                            <div class="card-header-custom">
                                <i class="fas fa-calendar-alt"></i>
                                Detail Booking
                            </div>
                            <div class="card-body">
                                <div class="mb-4">
                                    <label class="form-label">
                                        <i class="fas fa-calendar"></i>
                                        Tanggal Booking
                                    </label>
                                    <input type="date" class="form-control" name="tanggal_booking" 
                                           min="<?= date('Y-m-d') ?>" required id="tanggal_booking">
                                </div>
                                
                                <div class="mb-4">
                                    <label class="form-label">
                                        <i class="fas fa-clock"></i>
                                        Waktu Penggunaan
                                    </label>
                                    <div class="time-input-group">
                                        <div>
                                            <label class="form-label" style="font-size: 0.85rem; font-weight: 500;">Jam Mulai</label>
                                            <input type="time" class="form-control" name="jam_mulai" required id="jam_mulai">
                                        </div>
                                        <div>
                                            <label class="form-label" style="font-size: 0.85rem; font-weight: 500;">Jam Selesai</label>
                                            <input type="time" class="form-control" name="jam_selesai" required id="jam_selesai">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <label class="form-label">
                                        <i class="fas fa-users"></i>
                                        Jumlah Peserta
                                    </label>
                                    <input type="number" class="form-control" name="jumlah_peserta" 
                                           placeholder="Masukkan jumlah peserta" min="1" required>
                                </div>
                                
                                <div class="mb-4">
                                    <label class="form-label">
                                        <i class="fas fa-clipboard"></i>
                                        Keperluan
                                    </label>
                                    <textarea class="form-control" name="keperluan" rows="4" 
                                              placeholder="Jelaskan keperluan booking ruangan (contoh: Rapat BEM, Seminar, Kuliah, dll)" required></textarea>
                                </div>
                                
                                <div id="availabilityStatus" class="availability-status"></div>
                                
                                <button type="submit" class="btn btn-primary w-100" id="submitBtn">
                                    <i class="fas fa-paper-plane me-2"></i>Kirim Booking Sekarang
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Progress steps tracking
        function updateProgressSteps() {
            const ruanganSelected = document.querySelector('input[name="ruangan_id"]:checked');
            const tanggal = document.getElementById('tanggal_booking').value;
            const jamMulai = document.getElementById('jam_mulai').value;
            const jamSelesai = document.getElementById('jam_selesai').value;
            
            // Reset all steps
            document.querySelectorAll('.step').forEach(step => {
                step.classList.remove('active', 'completed');
            });
            
            // Step 1: Ruangan selected
            if (ruanganSelected) {
                document.getElementById('step1').classList.add('completed');
                document.getElementById('step2').classList.add('active');
            } else {
                document.getElementById('step1').classList.add('active');
                return;
            }
            
            // Step 2: Date and time filled
            if (tanggal && jamMulai && jamSelesai) {
                document.getElementById('step2').classList.add('completed');
                document.getElementById('step3').classList.add('active');
            }
        }
        
        // Filter gedung
        document.getElementById('filter_gedung').addEventListener('change', function() {
            const gedungId = this.value;
            const rooms = document.querySelectorAll('.room-card');
            
            rooms.forEach(room => {
                if (gedungId === '' || room.getAttribute('data-gedung') === gedungId) {
                    room.style.display = 'block';
                } else {
                    room.style.display = 'none';
                }
            });
        });
        
        // Select room
        function selectRoom(id, element) {
            document.querySelectorAll('.room-card').forEach(card => {
                card.classList.remove('selected');
            });
            element.classList.add('selected');
            element.querySelector('input[type="radio"]').checked = true;
            updateProgressSteps();
            checkAvailability();
            
            // Smooth scroll to form on mobile
            if (window.innerWidth < 768) {
                document.querySelector('.form-section').scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }
        
        // Check availability
        function checkAvailability() {
            const ruangan_id = document.querySelector('input[name="ruangan_id"]:checked')?.value;
            const tanggal = document.getElementById('tanggal_booking').value;
            const jam_mulai = document.getElementById('jam_mulai').value;
            const jam_selesai = document.getElementById('jam_selesai').value;
            
            if (ruangan_id && tanggal && jam_mulai && jam_selesai) {
                // Disable submit button while checking
                const submitBtn = document.getElementById('submitBtn');
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Memeriksa ketersediaan...';
                
                fetch('../api/check_availability.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `ruangan_id=${ruangan_id}&tanggal=${tanggal}&jam_mulai=${jam_mulai}&jam_selesai=${jam_selesai}`
                })
                .then(res => res.json())
                .then(data => {
                    const status = document.getElementById('availabilityStatus');
                    status.className = 'availability-status ' + (data.available ? 'available' : 'unavailable');
                    status.innerHTML = '<i class="fas fa-' + (data.available ? 'check-circle' : 'times-circle') + '"></i>' + data.message;
                    
                    // Enable/disable submit based on availability
                    submitBtn.disabled = !data.available;
                    if (data.available) {
                        submitBtn.innerHTML = '<i class="fas fa-paper-plane me-2"></i>Kirim Booking Sekarang';
                        document.getElementById('step3').classList.add('completed');
                        document.getElementById('step4').classList.add('active');
                    } else {
                        submitBtn.innerHTML = '<i class="fas fa-ban me-2"></i>Ruangan Tidak Tersedia';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-paper-plane me-2"></i>Kirim Booking Sekarang';
                });
            }
        }
        
        // Add event listeners
        ['tanggal_booking', 'jam_mulai', 'jam_selesai'].forEach(id => {
            document.getElementById(id).addEventListener('change', function() {
                updateProgressSteps();
                checkAvailability();
            });
        });
        
        // Form validation before submit
        document.getElementById('bookingForm').addEventListener('submit', function(e) {
            const ruanganSelected = document.querySelector('input[name="ruangan_id"]:checked');
            
            if (!ruanganSelected) {
                e.preventDefault();
                alert('Silakan pilih ruangan terlebih dahulu!');
                document.querySelector('.room-card').scrollIntoView({ behavior: 'smooth' });
                return false;
            }
            
            // Show loading state
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Memproses booking...';
        });
        
        // Initialize progress on page load
        updateProgressSteps();
    </script>
</body>
</html>
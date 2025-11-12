<?php
// admin/laporan.php
require_once '../includes/functions.php';
requireAdmin();

$db = getDB();

// Get date range from filter or default to this month
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');

// Total bookings by status
$bookingStats = $db->prepare("
    SELECT status_booking, COUNT(*) as total 
    FROM booking 
    WHERE tanggal_booking BETWEEN ? AND ?
    GROUP BY status_booking
");
$bookingStats->execute([$start_date, $end_date]);
$statusData = $bookingStats->fetchAll(PDO::FETCH_KEY_PAIR);

// Most booked rooms
$popularRooms = $db->prepare("
    SELECT r.nama_ruangan, r.kode_ruangan, COUNT(b.id) as total_booking
    FROM booking b
    JOIN ruangan r ON b.ruangan_id = r.id
    WHERE b.tanggal_booking BETWEEN ? AND ?
    GROUP BY b.ruangan_id
    ORDER BY total_booking DESC
    LIMIT 5
");
$popularRooms->execute([$start_date, $end_date]);
$popularRooms = $popularRooms->fetchAll();

// Most active users
$activeUsers = $db->prepare("
    SELECT u.nama, u.nim_nip, COUNT(b.id) as total_booking
    FROM booking b
    JOIN users u ON b.user_id = u.id
    WHERE b.tanggal_booking BETWEEN ? AND ?
    GROUP BY b.user_id
    ORDER BY total_booking DESC
    LIMIT 5
");
$activeUsers->execute([$start_date, $end_date]);
$activeUsers = $activeUsers->fetchAll();

// Booking by day of week
$bookingByDay = $db->prepare("
    SELECT DAYNAME(tanggal_booking) as hari, COUNT(*) as total
    FROM booking
    WHERE tanggal_booking BETWEEN ? AND ?
    GROUP BY DAYOFWEEK(tanggal_booking), DAYNAME(tanggal_booking)
    ORDER BY DAYOFWEEK(tanggal_booking)
");
$bookingByDay->execute([$start_date, $end_date]);
$dayData = $bookingByDay->fetchAll();

// Booking trend by date
$bookingTrend = $db->prepare("
    SELECT DATE(tanggal_booking) as tanggal, COUNT(*) as total
    FROM booking
    WHERE tanggal_booking BETWEEN ? AND ?
    GROUP BY DATE(tanggal_booking)
    ORDER BY tanggal
");
$bookingTrend->execute([$start_date, $end_date]);
$trendData = $bookingTrend->fetchAll();

// Overall statistics
$totalBookings = array_sum($statusData);
$completionRate = $totalBookings > 0 ? (($statusData['selesai'] ?? 0) / $totalBookings) * 100 : 0;

// Recent logs
$recentLogs = $db->prepare("
    SELECT l.*, u.nama
    FROM log_aktivitas l
    LEFT JOIN users u ON l.user_id = u.id
    ORDER BY l.created_at DESC
    LIMIT 10
");
$recentLogs->execute();
$logs = $recentLogs->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan & Analytics - Admin</title>
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
        
        .card-body { padding: 1.5rem; }
        
        .filter-bar {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 15px rgba(107, 163, 215, 0.08);
        }
        
        .stat-card {
            background: linear-gradient(135deg, var(--primary-blue), var(--light-blue));
            border-radius: 15px;
            padding: 1.5rem;
            color: white;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 15px rgba(107, 163, 215, 0.2);
        }
        
        .stat-card h3 {
            font-size: 2.5rem;
            font-weight: 800;
            margin: 0;
        }
        
        .stat-card p {
            margin: 0;
            opacity: 0.9;
            font-size: 0.95rem;
        }
        
        .stat-card .icon {
            font-size: 3rem;
            opacity: 0.3;
            position: absolute;
            right: 1.5rem;
            top: 50%;
            transform: translateY(-50%);
        }
        
        .chart-container {
            position: relative;
            height: 300px;
            padding: 1rem 0;
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
        
        .btn-primary {
            background: var(--primary-blue);
            border: none;
            padding: 0.6rem 1.5rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            background: var(--dark-blue);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(107, 163, 215, 0.3);
        }
        
        .badge-custom {
            padding: 0.4rem 0.8rem;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.75rem;
        }
        
        @media print {
            .sidebar, .topbar button, .filter-bar { display: none; }
            .main-content { margin-left: 0; }
        }
        
        @media (max-width: 768px) {
            .main-content { margin-left: 0; padding: 1rem; }
            .sidebar { transform: translateX(-100%); }
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
            <a href="laporan.php" class="menu-item active">
                <i class="fas fa-chart-bar"></i><span>Laporan</span>
            </a>
            <a href="notifikasi.php" class="menu-item">
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
            <h1 class="topbar-title">Laporan & Analytics</h1>
            <button class="btn btn-primary" onclick="window.print()">
                <i class="fas fa-print me-2"></i>Cetak Laporan
            </button>
        </div>
        
        <div class="filter-bar">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label fw-bold">Tanggal Mulai</label>
                    <input type="date" class="form-control" name="start_date" value="<?= $start_date ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Tanggal Akhir</label>
                    <input type="date" class="form-control" name="end_date" value="<?= $end_date ?>">
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter me-2"></i>Tampilkan Laporan
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Summary Stats -->
        <div class="row">
            <div class="col-md-3">
                <div class="stat-card position-relative">
                    <i class="fas fa-calendar-check icon"></i>
                    <p>Total Booking</p>
                    <h3><?= $totalBookings ?></h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card position-relative" style="background: linear-gradient(135deg, #28a745, #20c997);">
                    <i class="fas fa-check-circle icon"></i>
                    <p>Disetujui</p>
                    <h3><?= $statusData['approved'] ?? 0 ?></h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card position-relative" style="background: linear-gradient(135deg, #ffc107, #fd7e14);">
                    <i class="fas fa-clock icon"></i>
                    <p>Pending</p>
                    <h3><?= $statusData['pending'] ?? 0 ?></h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card position-relative" style="background: linear-gradient(135deg, #17a2b8, #138496);">
                    <i class="fas fa-flag-checkered icon"></i>
                    <p>Selesai</p>
                    <h3><?= $statusData['selesai'] ?? 0 ?></h3>
                </div>
            </div>
        </div>
        
        <!-- Charts Row 1 -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-chart-pie me-2"></i>Distribusi Status Booking
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="statusChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-chart-line me-2"></i>Tren Booking Harian
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="trendChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Charts Row 2 -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-star me-2"></i>Ruangan Terpopuler
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="roomsChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-calendar-week me-2"></i>Booking per Hari dalam Minggu
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="dayChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- User Activity Table -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-users me-2"></i>User Teraktif
            </div>
            <div class="card-body">
                <?php if (count($activeUsers) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th width="10%">#</th>
                                    <th>Nama</th>
                                    <th>NIM/NIP</th>
                                    <th width="20%">Total Booking</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($activeUsers as $index => $user): ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-primary"><?= $index + 1 ?></span>
                                    </td>
                                    <td><strong><?= htmlspecialchars($user['nama']) ?></strong></td>
                                    <td><?= htmlspecialchars($user['nim_nip']) ?></td>
                                    <td>
                                        <span class="badge bg-success"><?= $user['total_booking'] ?> booking</span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-users fa-3x mb-3 opacity-25"></i>
                        <p>Tidak ada data user</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Activity Log -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-history me-2"></i>Log Aktivitas Terbaru
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-sm">
                        <thead>
                            <tr>
                                <th>Waktu</th>
                                <th>User</th>
                                <th>Aktivitas</th>
                                <th>IP Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><small><?= date('d/m/Y H:i', strtotime($log['created_at'])) ?></small></td>
                                <td><strong><?= htmlspecialchars($log['nama'] ?? 'System') ?></strong></td>
                                <td><?= htmlspecialchars($log['aktivitas']) ?></td>
                                <td><code class="text-muted"><?= htmlspecialchars($log['ip_address']) ?></code></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        // Chart.js default config
        Chart.defaults.font.family = "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif";
        Chart.defaults.color = '#6c757d';
        
        // Status Distribution - Doughnut Chart
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Pending', 'Disetujui', 'Ditolak', 'Selesai'],
                datasets: [{
                    data: [
                        <?= $statusData['pending'] ?? 0 ?>,
                        <?= $statusData['approved'] ?? 0 ?>,
                        <?= $statusData['rejected'] ?? 0 ?>,
                        <?= $statusData['selesai'] ?? 0 ?>
                    ],
                    backgroundColor: [
                        '#ffc107',
                        '#28a745',
                        '#dc3545',
                        '#17a2b8'
                    ],
                    borderWidth: 3,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { padding: 15, font: { size: 12 } }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((context.parsed / total) * 100).toFixed(1);
                                return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });
        
        // Booking Trend - Line Chart
        const trendCtx = document.getElementById('trendChart').getContext('2d');
        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: [<?php foreach ($trendData as $t) echo "'" . date('d/m', strtotime($t['tanggal'])) . "',"; ?>],
                datasets: [{
                    label: 'Booking',
                    data: [<?php foreach ($trendData as $t) echo $t['total'] . ','; ?>],
                    borderColor: '#6BA3D7',
                    backgroundColor: 'rgba(107, 163, 215, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointBackgroundColor: '#6BA3D7',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 },
                        grid: { color: 'rgba(0,0,0,0.05)' }
                    },
                    x: {
                        grid: { display: false }
                    }
                }
            }
        });
        
        // Popular Rooms - Horizontal Bar Chart
        const roomsCtx = document.getElementById('roomsChart').getContext('2d');
        new Chart(roomsCtx, {
            type: 'bar',
            data: {
                labels: [<?php foreach ($popularRooms as $r) echo "'" . addslashes($r['kode_ruangan']) . "',"; ?>],
                datasets: [{
                    label: 'Total Booking',
                    data: [<?php foreach ($popularRooms as $r) echo $r['total_booking'] . ','; ?>],
                    backgroundColor: [
                        '#6BA3D7',
                        '#A8D0F0',
                        '#28a745',
                        '#ffc107',
                        '#17a2b8'
                    ],
                    borderRadius: 8,
                    borderWidth: 0
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 },
                        grid: { color: 'rgba(0,0,0,0.05)' }
                    },
                    y: {
                        grid: { display: false }
                    }
                }
            }
        });
        
        // Booking by Day - Radar Chart
        const dayCtx = document.getElementById('dayChart').getContext('2d');
        new Chart(dayCtx, {
            type: 'polarArea',
            data: {
                labels: [<?php foreach ($dayData as $d) echo "'" . $d['hari'] . "',"; ?>],
                datasets: [{
                    data: [<?php foreach ($dayData as $d) echo $d['total'] . ','; ?>],
                    backgroundColor: [
                        'rgba(107, 163, 215, 0.7)',
                        'rgba(168, 208, 240, 0.7)',
                        'rgba(40, 167, 69, 0.7)',
                        'rgba(255, 193, 7, 0.7)',
                        'rgba(23, 162, 184, 0.7)',
                        'rgba(220, 53, 69, 0.7)',
                        'rgba(108, 117, 125, 0.7)'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { padding: 10, font: { size: 11 } }
                    }
                },
                scales: {
                    r: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    }
                }
            }
        });
    </script>
</body>
</html>
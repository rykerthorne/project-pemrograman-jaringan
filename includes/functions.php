<?php
// includes/functions.php
// Helper Functions

session_start();
require_once __DIR__ . '/../config/database.php';

// Cek apakah user sudah login
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Cek role user
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Redirect jika belum login
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ../auth/login.php');
        exit();
    }
}

// Redirect jika bukan admin
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: ../user/index.php');
        exit();
    }
}

// Sanitasi input
function clean($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Format tanggal Indonesia
function formatTanggal($date) {
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    
    $split = explode('-', $date);
    return $split[2] . ' ' . $bulan[(int)$split[1]] . ' ' . $split[0];
}

// Format waktu
function formatWaktu($time) {
    return date('H:i', strtotime($time));
}

// Get status badge class
function getStatusBadge($status) {
    $badges = [
        'pending' => 'badge-warning',
        'approved' => 'badge-success',
        'rejected' => 'badge-danger',
        'selesai' => 'badge-secondary',
        'dibatalkan' => 'badge-dark'
    ];
    return $badges[$status] ?? 'badge-secondary';
}

// Get status label
function getStatusLabel($status) {
    $labels = [
        'pending' => 'Menunggu Persetujuan',
        'approved' => 'Disetujui',
        'rejected' => 'Ditolak',
        'selesai' => 'Selesai',
        'dibatalkan' => 'Dibatalkan'
    ];
    return $labels[$status] ?? $status;
}

// Cek ketersediaan ruangan
function cekKetersediaan($ruangan_id, $tanggal, $jam_mulai, $jam_selesai, $booking_id = null) {
    $db = getDB();
    
    $sql = "SELECT COUNT(*) as total FROM booking 
            WHERE ruangan_id = :ruangan_id 
            AND tanggal_booking = :tanggal 
            AND status_booking IN ('pending', 'approved')
            AND (
                (jam_mulai < :jam_selesai AND jam_selesai > :jam_mulai)
            )";
    
    if ($booking_id) {
        $sql .= " AND id != :booking_id";
    }
    
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':ruangan_id', $ruangan_id);
    $stmt->bindParam(':tanggal', $tanggal);
    $stmt->bindParam(':jam_mulai', $jam_mulai);
    $stmt->bindParam(':jam_selesai', $jam_selesai);
    
    if ($booking_id) {
        $stmt->bindParam(':booking_id', $booking_id);
    }
    
    $stmt->execute();
    $result = $stmt->fetch();
    
    return $result['total'] == 0;
}

// Tambah notifikasi
function addNotifikasi($user_id, $booking_id, $judul, $pesan) {
    $db = getDB();
    
    $sql = "INSERT INTO notifikasi (user_id, booking_id, judul, pesan) 
            VALUES (:user_id, :booking_id, :judul, :pesan)";
    
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':booking_id', $booking_id);
    $stmt->bindParam(':judul', $judul);
    $stmt->bindParam(':pesan', $pesan);
    
    return $stmt->execute();
}

// Kirim notifikasi broadcast
function sendBroadcastNotif($judul, $pesan, $exclude_admin = true) {
    $db = getDB();
    
    $sql = "SELECT id FROM users WHERE status='active'";
    if ($exclude_admin) {
        $sql .= " AND role='user'";
    }
    
    $users = $db->query($sql)->fetchAll();
    
    $stmt = $db->prepare("INSERT INTO notifikasi (user_id, judul, pesan) VALUES (?, ?, ?)");
    
    $count = 0;
    foreach ($users as $user) {
        if ($stmt->execute([$user['id'], $judul, $pesan])) {
            $count++;
        }
    }
    
    return $count;
}

// Get unread notification count
function getUnreadNotifCount($user_id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM notifikasi WHERE user_id = ? AND status = 'unread'");
    $stmt->execute([$user_id]);
    return $stmt->fetch()['total'];
}

// Get recent notifications
function getRecentNotifications($user_id, $limit = 5) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT n.*, b.tanggal_booking, b.status_booking
        FROM notifikasi n
        LEFT JOIN booking b ON n.booking_id = b.id
        WHERE n.user_id = ? AND n.status = 'unread'
        ORDER BY n.created_at DESC
        LIMIT ?
    ");
    $stmt->execute([$user_id, $limit]);
    return $stmt->fetchAll();
}

// Log aktivitas
function logAktivitas($user_id, $aktivitas) {
    $db = getDB();
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    
    $sql = "INSERT INTO log_aktivitas (user_id, aktivitas, ip_address, user_agent) 
            VALUES (:user_id, :aktivitas, :ip_address, :user_agent)";
    
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':aktivitas', $aktivitas);
    $stmt->bindParam(':ip_address', $ip_address);
    $stmt->bindParam(':user_agent', $user_agent);
    
    return $stmt->execute();
}

// Upload foto
function uploadFoto($file, $folder = 'ruangan') {
    $target_dir = __DIR__ . "/../assets/images/uploads/" . $folder . "/";
    
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $new_filename = uniqid() . '.' . $file_extension;
    $target_file = $target_dir . $new_filename;
    
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
    
    if (!in_array($file_extension, $allowed_types)) {
        return ['success' => false, 'message' => 'Format file tidak didukung'];
    }
    
    if ($file["size"] > 5000000) { // 5MB
        return ['success' => false, 'message' => 'Ukuran file terlalu besar (max 5MB)'];
    }
    
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return ['success' => true, 'filename' => $new_filename];
    } else {
        return ['success' => false, 'message' => 'Gagal mengupload file'];
    }
}

// Alert messages
function setAlert($type, $message) {
    $_SESSION['alert'] = [
        'type' => $type,
        'message' => $message
    ];
}

function showAlert() {
    if (isset($_SESSION['alert'])) {
        $alert = $_SESSION['alert'];
        $alertClass = [
            'success' => 'alert-success',
            'error' => 'alert-danger',
            'warning' => 'alert-warning',
            'info' => 'alert-info'
        ];
        
        echo '<div class="alert ' . $alertClass[$alert['type']] . ' alert-dismissible fade show" role="alert">';
        echo $alert['message'];
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        echo '</div>';
        
        unset($_SESSION['alert']);
    }
}
?>
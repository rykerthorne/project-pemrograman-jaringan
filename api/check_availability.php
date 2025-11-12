<?php
// api/check_availability.php
// API untuk mengecek ketersediaan ruangan

header('Content-Type: application/json');
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

$ruangan_id = isset($_POST['ruangan_id']) ? (int)$_POST['ruangan_id'] : 0;
$tanggal = isset($_POST['tanggal']) ? $_POST['tanggal'] : '';
$jam_mulai = isset($_POST['jam_mulai']) ? $_POST['jam_mulai'] : '';
$jam_selesai = isset($_POST['jam_selesai']) ? $_POST['jam_selesai'] : '';
$booking_id = isset($_POST['booking_id']) ? (int)$_POST['booking_id'] : null;

// Validasi input
if (empty($ruangan_id) || empty($tanggal) || empty($jam_mulai) || empty($jam_selesai)) {
    echo json_encode([
        'available' => false,
        'message' => 'Data tidak lengkap'
    ]);
    exit();
}

// Validasi waktu
if ($jam_mulai >= $jam_selesai) {
    echo json_encode([
        'available' => false,
        'message' => 'Jam selesai harus lebih besar dari jam mulai'
    ]);
    exit();
}

// Validasi tanggal tidak boleh masa lalu
if ($tanggal < date('Y-m-d')) {
    echo json_encode([
        'available' => false,
        'message' => 'Tidak dapat booking tanggal yang sudah lewat'
    ]);
    exit();
}

try {
    $db = getDB();
    
    // Cek status ruangan
    $checkRoom = $db->prepare("SELECT status FROM ruangan WHERE id = ?");
    $checkRoom->execute([$ruangan_id]);
    $room = $checkRoom->fetch();
    
    if (!$room) {
        echo json_encode([
            'available' => false,
            'message' => 'Ruangan tidak ditemukan'
        ]);
        exit();
    }
    
    if ($room['status'] !== 'tersedia') {
        echo json_encode([
            'available' => false,
            'message' => 'Ruangan sedang ' . $room['status']
        ]);
        exit();
    }
    
    // Cek konflik booking
    $sql = "SELECT b.*, u.nama, r.nama_ruangan 
            FROM booking b
            JOIN users u ON b.user_id = u.id
            JOIN ruangan r ON b.ruangan_id = r.id
            WHERE b.ruangan_id = :ruangan_id 
            AND b.tanggal_booking = :tanggal 
            AND b.status_booking IN ('pending', 'approved')
            AND (
                (b.jam_mulai < :jam_selesai AND b.jam_selesai > :jam_mulai)
            )";
    
    if ($booking_id) {
        $sql .= " AND b.id != :booking_id";
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
    $conflicts = $stmt->fetchAll();
    
    if (count($conflicts) > 0) {
        $conflict = $conflicts[0];
        echo json_encode([
            'available' => false,
            'message' => 'Ruangan sudah dibooking pada waktu tersebut',
            'conflict' => [
                'user' => $conflict['nama'],
                'waktu' => $conflict['jam_mulai'] . ' - ' . $conflict['jam_selesai'],
                'status' => $conflict['status_booking']
            ]
        ]);
        exit();
    }
    
    // Ruangan tersedia
    echo json_encode([
        'available' => true,
        'message' => 'Ruangan tersedia untuk waktu yang dipilih'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'available' => false,
        'message' => 'Terjadi kesalahan: ' . $e->getMessage()
    ]);
}
?>
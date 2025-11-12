<?php
// api/get_ruangan.php
// API untuk mendapatkan data ruangan

header('Content-Type: application/json');
require_once '../config/database.php';

try {
    $db = getDB();
    
    // Get gedung_id from query parameter
    $gedung_id = isset($_GET['gedung_id']) ? (int)$_GET['gedung_id'] : 0;
    
    if ($gedung_id > 0) {
        // Get rooms by building
        $sql = "SELECT r.*, g.nama_gedung 
                FROM ruangan r
                JOIN gedung g ON r.gedung_id = g.id
                WHERE r.gedung_id = ? AND r.status = 'tersedia'
                ORDER BY r.kode_ruangan";
        $stmt = $db->prepare($sql);
        $stmt->execute([$gedung_id]);
    } else {
        // Get all available rooms
        $sql = "SELECT r.*, g.nama_gedung 
                FROM ruangan r
                JOIN gedung g ON r.gedung_id = g.id
                WHERE r.status = 'tersedia'
                ORDER BY g.nama_gedung, r.kode_ruangan";
        $stmt = $db->query($sql);
    }
    
    $ruangan = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'data' => $ruangan,
        'count' => count($ruangan)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
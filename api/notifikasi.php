<?php
// api/notifikasi.php
// API untuk mengelola notifikasi

header('Content-Type: application/json');
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$db = getDB();
$user_id = $_SESSION['user_id'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

try {
    switch ($action) {
        case 'get':
            // Get all notifications
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            $stmt = $db->prepare("
                SELECT n.*, b.tanggal_booking, b.jam_mulai, b.jam_selesai
                FROM notifikasi n
                LEFT JOIN booking b ON n.booking_id = b.id
                WHERE n.user_id = ?
                ORDER BY n.created_at DESC
                LIMIT ?
            ");
            $stmt->execute([$user_id, $limit]);
            $notifikasi = $stmt->fetchAll();
            
            // Format data
            $formatted = [];
            foreach ($notifikasi as $n) {
                $formatted[] = [
                    'id' => $n['id'],
                    'judul' => $n['judul'],
                    'pesan' => $n['pesan'],
                    'status' => $n['status'],
                    'booking_id' => $n['booking_id'],
                    'created_at' => $n['created_at'],
                    'time_ago' => timeAgo($n['created_at'])
                ];
            }
            
            echo json_encode([
                'success' => true,
                'data' => $formatted,
                'count' => count($formatted)
            ]);
            break;
            
        case 'unread_count':
            // Get unread count
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM notifikasi WHERE user_id = ? AND status = 'unread'");
            $stmt->execute([$user_id]);
            $result = $stmt->fetch();
            
            echo json_encode([
                'success' => true,
                'count' => (int)$result['count']
            ]);
            break;
            
        case 'mark_read':
            // Mark notification as read
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $notif_id = isset($_POST['notif_id']) ? (int)$_POST['notif_id'] : 0;
                
                if ($notif_id > 0) {
                    $stmt = $db->prepare("UPDATE notifikasi SET status = 'read' WHERE id = ? AND user_id = ?");
                    $stmt->execute([$notif_id, $user_id]);
                    
                    echo json_encode([
                        'success' => true,
                        'message' => 'Notifikasi ditandai sebagai dibaca'
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'ID notifikasi tidak valid'
                    ]);
                }
            }
            break;
            
        case 'mark_all_read':
            // Mark all notifications as read
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $stmt = $db->prepare("UPDATE notifikasi SET status = 'read' WHERE user_id = ? AND status = 'unread'");
                $stmt->execute([$user_id]);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Semua notifikasi ditandai sebagai dibaca'
                ]);
            }
            break;
            
        case 'delete':
            // Delete notification
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $notif_id = isset($_POST['notif_id']) ? (int)$_POST['notif_id'] : 0;
                
                if ($notif_id > 0) {
                    $stmt = $db->prepare("DELETE FROM notifikasi WHERE id = ? AND user_id = ?");
                    $stmt->execute([$notif_id, $user_id]);
                    
                    echo json_encode([
                        'success' => true,
                        'message' => 'Notifikasi dihapus'
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'ID notifikasi tidak valid'
                    ]);
                }
            }
            break;
            
        case 'delete_all':
            // Delete all read notifications
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $stmt = $db->prepare("DELETE FROM notifikasi WHERE user_id = ? AND status = 'read'");
                $stmt->execute([$user_id]);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Semua notifikasi yang sudah dibaca dihapus'
                ]);
            }
            break;
            
        default:
            echo json_encode([
                'success' => false,
                'message' => 'Action tidak valid'
            ]);
            break;
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

// Helper function untuk time ago
function timeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $difference = time() - $timestamp;
    
    if ($difference < 60) {
        return 'Baru saja';
    } elseif ($difference < 3600) {
        $minutes = floor($difference / 60);
        return $minutes . ' menit yang lalu';
    } elseif ($difference < 86400) {
        $hours = floor($difference / 3600);
        return $hours . ' jam yang lalu';
    } elseif ($difference < 604800) {
        $days = floor($difference / 86400);
        return $days . ' hari yang lalu';
    } else {
        return date('d M Y H:i', $timestamp);
    }
}
?>
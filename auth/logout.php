 
<?php
// auth/logout.php
session_start();
require_once '../config/database.php';

if (isset($_SESSION['user_id'])) {
    $db = getDB();
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    $aktivitas = "Logout dari sistem";
    
    $sql = "INSERT INTO log_aktivitas (user_id, aktivitas, ip_address, user_agent) 
            VALUES (:user_id, :aktivitas, :ip_address, :user_agent)";
    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':user_id' => $_SESSION['user_id'],
        ':aktivitas' => $aktivitas,
        ':ip_address' => $ip_address,
        ':user_agent' => $user_agent
    ]);
}

session_destroy();
header('Location: login.php');
exit();
?>

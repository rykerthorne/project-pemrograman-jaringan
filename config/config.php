 <?php
// config/config.php
// Konfigurasi Umum Aplikasi

// Timezone
date_default_timezone_set('Asia/Jakarta');

// Site Configuration
define('SITE_NAME', 'Booking Ruangan UCA');
define('SITE_URL', 'http://localhost/booking-ruangan-uca');
define('SITE_EMAIL', 'info@uca.ac.id');

// Path Configuration
define('BASE_PATH', __DIR__ . '/../');
define('UPLOAD_PATH', BASE_PATH . 'assets/images/uploads/');
define('UPLOAD_URL', SITE_URL . '/assets/images/uploads/');

// Session Configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set 1 jika menggunakan HTTPS

// Upload Configuration
define('MAX_FILE_SIZE', 5242880); // 5MB in bytes
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif']);

// Booking Configuration
define('MIN_BOOKING_DAYS', 1); // Minimal booking H+1
define('MAX_BOOKING_DAYS', 30); // Maksimal booking 30 hari ke depan
define('MIN_BOOKING_DURATION', 60); // Minimal durasi booking (menit)
define('MAX_BOOKING_DURATION', 480); // Maksimal durasi booking (menit)

// Jam Operasional
define('JAM_BUKA', '07:00');
define('JAM_TUTUP', '21:00');

// Pagination
define('ITEMS_PER_PAGE', 10);

// Error Reporting (Development)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Error Reporting (Production) - Uncomment for production
// error_reporting(0);
// ini_set('display_errors', 0);
// ini_set('log_errors', 1);
// ini_set('error_log', BASE_PATH . 'logs/error.log');
?>

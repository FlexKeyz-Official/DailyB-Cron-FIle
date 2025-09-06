 <?php
// Database configuration
$host = 'sql200.infinityfree.com';        // or your server IP
$db   = 'if0_39800294_dailybred';        // your database name
$user = 'if0_39800294';     // your DB username
$pass = '0246570672';       // your DB password
$charset = 'utf8mb4';

// Set DSN (Data Source Name)
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

// PDO options
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,  // throws exceptions on errors
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,        // fetches associative arrays
    PDO::ATTR_EMULATE_PREPARES   => false,                   // disables emulated prepared statements
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}
?>
<?php
$host = 'localhost'; 
$db   = 'HAYEON';
$user = 'root'; 
$pass = 'm:Xa/RGQ4K5P';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    try {
        $pdo = new PDO($dsn, $user, $pass, $options);
        
        $stmt = $pdo->prepare("SELECT filename FROM photo_event WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        
        if ($row) {
            $filepath = './uploads/' . $row['filename'];
            if (file_exists($filepath)) {
                unlink($filepath);
            }
            $stmt = $pdo->prepare("DELETE FROM photo_event WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Not found']);
        }
    } catch (\PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'DB error']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
}
?>

<?php
session_start();
require_once 'config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $pdo = getDBConnection();

    // Ensure table exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sender_id INT NOT NULL,
        receiver_id INT NOT NULL,
        content TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (sender_id),
        INDEX (receiver_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $withUserId = (int)($_GET['with_user_id'] ?? 0);
    if ($withUserId <= 0) {
        echo json_encode(['success' => false, 'message' => 'with_user_id is required']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM messages 
        WHERE (sender_id = :me AND receiver_id = :other) OR (sender_id = :other AND receiver_id = :me)
        ORDER BY created_at ASC");
    $stmt->execute([':me' => $_SESSION['user_id'], ':other' => $withUserId]);
    $rows = $stmt->fetchAll();

    echo json_encode(['success' => true, 'messages' => $rows]);
} catch (PDOException $e) {
    error_log('get_messages error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>



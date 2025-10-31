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
        INDEX (receiver_id),
        FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $receiverId = (int)($_POST['receiver_id'] ?? 0);
    $content = trim($_POST['content'] ?? '');

    if ($receiverId <= 0 || $content === '') {
        echo json_encode(['success' => false, 'message' => 'Receiver and content are required']);
        exit;
    }

    if ($receiverId === (int)$_SESSION['user_id']) {
        echo json_encode(['success' => false, 'message' => 'Cannot message yourself']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, content) VALUES (:s, :r, :c)");
    $stmt->execute([
        ':s' => $_SESSION['user_id'],
        ':r' => $receiverId,
        ':c' => $content,
    ]);

    echo json_encode(['success' => true, 'id' => (int)$pdo->lastInsertId()]);
} catch (PDOException $e) {
    error_log('send_message error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>



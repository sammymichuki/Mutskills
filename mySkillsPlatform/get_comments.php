<?php
require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'message' => 'Invalid method']);
    exit();
}

$postId = isset($_GET['post_id']) ? (int)$_GET['post_id'] : 0;
if ($postId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid post id']);
    exit();
}

try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT c.id, c.content, c.created_at, u.first_name, u.last_name, u.email
                           FROM post_comments c
                           JOIN users u ON u.id = c.user_id
                           WHERE c.post_id = :post_id
                           ORDER BY c.created_at ASC
                           LIMIT 50");
    $stmt->execute([':post_id' => $postId]);
    $comments = $stmt->fetchAll();
    echo json_encode(['success' => true, 'comments' => $comments]);
} catch (PDOException $e) {
    error_log('Get comments failed: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>



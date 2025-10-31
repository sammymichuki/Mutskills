<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid method']);
    exit();
}

$postId = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;
$content = isset($_POST['content']) ? trim($_POST['content']) : '';
$userId = (int)$_SESSION['user_id'];

if ($postId <= 0 || $content === '') {
    echo json_encode(['success' => false, 'message' => 'Missing fields']);
    exit();
}

try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("INSERT INTO post_comments (post_id, user_id, content, created_at) VALUES (:post_id, :user_id, :content, NOW())");
    $stmt->execute([
        ':post_id' => $postId,
        ':user_id' => $userId,
        ':content' => $content
    ]);

    // updated count
    $countStmt = $pdo->prepare("SELECT COUNT(*) AS comments FROM post_comments WHERE post_id = :post_id");
    $countStmt->execute([':post_id' => $postId]);
    $row = $countStmt->fetch();

    echo json_encode(['success' => true, 'comments' => (int)($row['comments'] ?? 0)]);
} catch (PDOException $e) {
    error_log('Add comment failed: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>



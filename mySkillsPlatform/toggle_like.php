<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postId = intval($_POST['post_id'] ?? 0);
    $userId = $_SESSION['user_id'];
    
    if ($postId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid post ID']);
        exit();
    }
    
    try {
        $pdo = getDBConnection();
        
        // Check if already liked
        $stmt = $pdo->prepare("SELECT id FROM post_likes WHERE post_id = :post_id AND user_id = :user_id");
        $stmt->execute([':post_id' => $postId, ':user_id' => $userId]);
        $like = $stmt->fetch();
        
        if ($like) {
            // Unlike - remove the like
            $stmt = $pdo->prepare("DELETE FROM post_likes WHERE post_id = :post_id AND user_id = :user_id");
            $stmt->execute([':post_id' => $postId, ':user_id' => $userId]);
            $liked = false;
        } else {
            // Like - add the like
            $stmt = $pdo->prepare("INSERT INTO post_likes (post_id, user_id) VALUES (:post_id, :user_id)");
            $stmt->execute([':post_id' => $postId, ':user_id' => $userId]);
            $liked = true;
        }
        
        // Get updated like count
        $stmt = $pdo->prepare("SELECT COUNT(*) as likes FROM post_likes WHERE post_id = :post_id");
        $stmt->execute([':post_id' => $postId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'likes' => intval($result['likes']),
            'liked' => $liked
        ]);
        
    } catch (PDOException $e) {
        error_log("Toggle like error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
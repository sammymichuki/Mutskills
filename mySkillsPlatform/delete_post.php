<?php
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
        
        // Verify the post belongs to the user
        $stmt = $pdo->prepare("SELECT user_id FROM posts WHERE id = :post_id");
        $stmt->execute([':post_id' => $postId]);
        $post = $stmt->fetch();
        
        if (!$post) {
            echo json_encode(['success' => false, 'message' => 'Post not found']);
            exit();
        }
        
        if ($post['user_id'] != $userId) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit();
        }
        
        // Delete the post (CASCADE will handle related records)
        $stmt = $pdo->prepare("DELETE FROM posts WHERE id = :post_id");
        $stmt->execute([':post_id' => $postId]);
        
        echo json_encode(['success' => true, 'message' => 'Post deleted successfully']);
        
    } catch (PDOException $e) {
        error_log("Delete post error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
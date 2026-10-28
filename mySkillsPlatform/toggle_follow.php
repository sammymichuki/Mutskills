<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$targetUserId = intval($_POST['user_id'] ?? 0);
$currentUserId = (int)$_SESSION['user_id'];

if ($targetUserId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
    exit();
}

if ($targetUserId === $currentUserId) {
    echo json_encode(['success' => false, 'message' => 'Cannot follow yourself']);
    exit();
}

try {
    $pdo = getDBConnection();

    // Check if already following
    $stmt = $pdo->prepare("SELECT id FROM followers WHERE user_id = :target AND follower_id = :me");
    $stmt->execute([':target' => $targetUserId, ':me' => $currentUserId]);
    $follow = $stmt->fetch();

    if ($follow) {
        // Unfollow
        $del = $pdo->prepare("DELETE FROM followers WHERE user_id = :target AND follower_id = :me");
        $del->execute([':target' => $targetUserId, ':me' => $currentUserId]);
        $isFollowing = false;
    } else {
        // Follow
        $ins = $pdo->prepare("INSERT INTO followers (user_id, follower_id, created_at) VALUES (:target, :me, NOW())");
        $ins->execute([':target' => $targetUserId, ':me' => $currentUserId]);
        $isFollowing = true;
    }

    // Get updated follower counts
    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM followers WHERE user_id = :target");
    $stmtCount->execute([':target' => $targetUserId]);
    $targetFollowers = (int)$stmtCount->fetchColumn();

    $stmtMyFollowing = $pdo->prepare("SELECT COUNT(*) FROM followers WHERE follower_id = :me");
    $stmtMyFollowing->execute([':me' => $currentUserId]);
    $myFollowingCount = (int)$stmtMyFollowing->fetchColumn();

    echo json_encode([
        'success' => true,
        'following' => $isFollowing,
        'target_followers' => $targetFollowers,
        'my_following' => $myFollowingCount
    ]);
} catch (PDOException $e) {
    error_log("Toggle follow error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>

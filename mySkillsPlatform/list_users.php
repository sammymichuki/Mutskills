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
    $q = trim($_GET['q'] ?? '');
    if ($q !== '') {
        $stmt = $pdo->prepare("SELECT id, first_name, last_name, email FROM users WHERE (first_name LIKE :q OR last_name LIKE :q OR email LIKE :q) AND id <> :me LIMIT 50");
        $stmt->execute([':q' => "%$q%", ':me' => $_SESSION['user_id']]);
    } else {
        $stmt = $pdo->prepare("SELECT id, first_name, last_name, email FROM users WHERE id <> :me ORDER BY id DESC LIMIT 50");
        $stmt->execute([':me' => $_SESSION['user_id']]);
    }
    echo json_encode(['success' => true, 'users' => $stmt->fetchAll()]);
} catch (PDOException $e) {
    error_log('list_users error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>



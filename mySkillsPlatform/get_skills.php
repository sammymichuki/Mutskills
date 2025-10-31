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

    // Ensure table exists (safe/no-op if already exists)
    $pdo->exec("CREATE TABLE IF NOT EXISTS skills (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        name VARCHAR(100) NOT NULL,
        level VARCHAR(50) NULL,
        description TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (user_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $scope = $_GET['scope'] ?? 'all'; // 'mine' or 'all'
    $q = trim($_GET['q'] ?? '');
    $params = [];
    if ($scope === 'mine') {
        if ($q !== '') {
            $sql = "SELECT s.*, u.first_name, u.last_name, u.email
                    FROM skills s JOIN users u ON s.user_id = u.id
                    WHERE s.user_id = :uid AND (s.name LIKE :q OR s.level LIKE :q OR s.description LIKE :q)
                    ORDER BY s.created_at DESC";
            $params[':q'] = "%$q%";
        } else {
            $sql = "SELECT s.*, u.first_name, u.last_name, u.email FROM skills s JOIN users u ON s.user_id = u.id WHERE s.user_id = :uid ORDER BY s.created_at DESC";
        }
        $params[':uid'] = $_SESSION['user_id'];
    } else {
        if ($q !== '') {
            $sql = "SELECT s.*, u.first_name, u.last_name, u.email, u.id AS user_id
                    FROM skills s JOIN users u ON s.user_id = u.id
                    WHERE (s.name LIKE :q OR s.level LIKE :q OR s.description LIKE :q OR u.first_name LIKE :q OR u.last_name LIKE :q OR u.email LIKE :q)
                    ORDER BY s.created_at DESC LIMIT 100";
            $params[':q'] = "%$q%";
        } else {
            $sql = "SELECT s.*, u.first_name, u.last_name, u.email, u.id AS user_id FROM skills s JOIN users u ON s.user_id = u.id ORDER BY s.created_at DESC LIMIT 100";
        }
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $skills = $stmt->fetchAll();

    echo json_encode(['success' => true, 'skills' => $skills]);
} catch (PDOException $e) {
    error_log('get_skills error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>



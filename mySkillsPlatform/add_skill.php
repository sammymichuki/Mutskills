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
    $pdo->exec("CREATE TABLE IF NOT EXISTS skills (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        name VARCHAR(100) NOT NULL,
        level VARCHAR(50) NULL,
        description TEXT NULL,
        image_path VARCHAR(255) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (user_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $name = trim($_POST['name'] ?? '');
    $level = trim($_POST['level'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($name === '') {
        echo json_encode(['success' => false, 'message' => 'Skill name is required']);
        exit;
    }

    // Handle optional image upload
    $imagePath = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $tmpName = $_FILES['image']['tmp_name'];
        $originalName = basename($_FILES['image']['name']);
        $fileSize = isset($_FILES['image']['size']) ? (int)$_FILES['image']['size'] : 0;
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (in_array($ext, $allowed, true) && $fileSize <= 5 * 1024 * 1024) {
            $safeName = bin2hex(random_bytes(8)) . '.' . $ext;
            $destinationDir = __DIR__ . '/uploads';
            if (!is_dir($destinationDir)) {
                @mkdir($destinationDir, 0755, true);
            }
            $destination = $destinationDir . '/' . $safeName;
            if (is_uploaded_file($tmpName) && move_uploaded_file($tmpName, $destination)) {
                @chmod($destination, 0644);
                $imagePath = 'uploads/' . $safeName;
            }
        }
    }

    $stmt = $pdo->prepare("INSERT INTO skills (user_id, name, level, description, image_path) VALUES (:uid, :name, :level, :description, :img)");
    $stmt->execute([
        ':uid' => $_SESSION['user_id'],
        ':name' => $name,
        ':level' => $level,
        ':description' => $description,
        ':img' => $imagePath,
    ]);

    echo json_encode(['success' => true, 'id' => (int)$pdo->lastInsertId()]);
} catch (PDOException $e) {
    error_log('add_skill error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>



<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit();
}

// Validate post content: allow image-only or text-only
$rawContent = isset($_POST['content']) ? $_POST['content'] : '';
$hasText = !empty(trim($rawContent));
$hasImageUpload = isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE;
if (!$hasText && !$hasImageUpload) {
    $_SESSION['post_error'] = 'Please add text or attach an image';
    header('Location: dashboard.php');
    exit();
}

// Sanitize input
$content = trim($rawContent);
$user_id = $_SESSION['user_id'] ?? null;

// Handle optional image upload
$imagePath = null;
if ($hasImageUpload) {
    $fileError = $_FILES['image']['error'];
    if ($fileError === UPLOAD_ERR_OK) {
        $tmpName = $_FILES['image']['tmp_name'];
        $originalName = basename($_FILES['image']['name']);
        $fileSize = isset($_FILES['image']['size']) ? (int)$_FILES['image']['size'] : 0;
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array($ext, $allowed, true)) {
            $_SESSION['post_error'] = 'Invalid image type. Allowed: jpg, jpeg, png, gif, webp';
            header('Location: dashboard.php');
            exit();
        }
        if ($fileSize > 5 * 1024 * 1024) { // 5MB
            $_SESSION['post_error'] = 'Image too large. Max 5MB';
            header('Location: dashboard.php');
            exit();
        }
        $safeName = bin2hex(random_bytes(8)) . '.' . $ext;
        $destinationDir = __DIR__ . '/uploads';
        if (!is_dir($destinationDir)) {
            @mkdir($destinationDir, 0755, true);
        }
        $destination = $destinationDir . '/' . $safeName;
        if (!is_uploaded_file($tmpName) || !move_uploaded_file($tmpName, $destination)) {
            $_SESSION['post_error'] = 'Failed to save uploaded image';
            header('Location: dashboard.php');
            exit();
        }
        @chmod($destination, 0644);
        $imagePath = 'uploads/' . $safeName; // relative path to serve in HTML
        error_log("Image uploaded successfully: " . $imagePath);
    } else {
        $errorMap = [
            UPLOAD_ERR_INI_SIZE => 'Image exceeds server upload limit. Reduce size or increase php.ini limits.',
            UPLOAD_ERR_FORM_SIZE => 'Image exceeds form size limit.',
            UPLOAD_ERR_PARTIAL => 'Image was only partially uploaded. Please try again.',
            UPLOAD_ERR_NO_FILE => 'No file uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder on server.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload.'
        ];
        $_SESSION['post_error'] = $errorMap[$fileError] ?? 'Image upload error';
        error_log("Image upload error: " . $fileError . " - " . ($errorMap[$fileError] ?? 'Unknown error'));
        header('Location: dashboard.php');
        exit();
    }
}

try {
    $pdo = getDBConnection();
    
    // Prepare SQL statement
    $sql = "INSERT INTO posts (user_id, content, image_path, created_at) VALUES (:user_id, :content, :image_path, NOW())";
    $stmt = $pdo->prepare($sql);
    
    // Execute the statement
    $stmt->execute([
        ':user_id' => $user_id,
        ':content' => $content,
        ':image_path' => $imagePath
    ]);
    
    error_log("Post created with image_path: " . ($imagePath ?? 'NULL'));
    
    // Set success message
    $_SESSION['post_success'] = 'Post created successfully!';
    
} catch (PDOException $e) {
    // Log error (in production, log to file instead of showing)
    error_log("Database error: " . $e->getMessage());
    $_SESSION['post_error'] = 'Failed to create post. Please try again.';
}

// Redirect back to dashboard
header('Location: dashboard.php');
exit();
?>
<?php
// Simple test to debug image upload
session_start();
require_once 'config.php';

// Simulate a logged-in user
$_SESSION['user_id'] = 1;

echo "<h2>Upload Test</h2>";
echo "<form method='POST' action='create_post.php' enctype='multipart/form-data'>";
echo "<textarea name='content' placeholder='Test content'></textarea><br><br>";
echo "<input type='file' name='image' accept='image/*'><br><br>";
echo "<button type='submit'>Test Upload</button>";
echo "</form>";

echo "<h3>Debug Info:</h3>";
echo "Uploads directory exists: " . (is_dir(__DIR__ . '/uploads') ? 'YES' : 'NO') . "<br>";
echo "Uploads directory writable: " . (is_writable(__DIR__ . '/uploads') ? 'YES' : 'NO') . "<br>";
echo "PHP upload_max_filesize: " . ini_get('upload_max_filesize') . "<br>";
echo "PHP post_max_size: " . ini_get('post_max_size') . "<br>";
echo "PHP file_uploads: " . (ini_get('file_uploads') ? 'ON' : 'OFF') . "<br>";

if (isset($_FILES['image'])) {
    echo "<h3>File Upload Debug:</h3>";
    echo "File error: " . $_FILES['image']['error'] . "<br>";
    echo "File name: " . $_FILES['image']['name'] . "<br>";
    echo "File size: " . $_FILES['image']['size'] . "<br>";
    echo "File type: " . $_FILES['image']['type'] . "<br>";
    echo "Temp name: " . $_FILES['image']['tmp_name'] . "<br>";
}
?>

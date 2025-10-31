<?php
session_start();
require_once 'config.php';

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Sanitize and validate input
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $password = trim($_POST['password']);
    $remember = isset($_POST['remember']) ? true : false;
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Invalid email format";
        header("Location: login.php");
        exit();
    }
    
    // Check if fields are empty
    if (empty($email) || empty($password)) {
        $_SESSION['error'] = "Please fill in all fields";
        header("Location: login.php");
        exit();
    }
    
    try {
        $pdo = getDBConnection();
        // Fetch user
        $stmt = $pdo->prepare("SELECT id, email, password, first_name, last_name FROM users WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();
    } catch (PDOException $e) {
        error_log('Login select failed: ' . $e->getMessage());
        $_SESSION['error'] = "Unexpected error. Please try again.";
        header("Location: login.php");
        exit();
    }

    // Check if user exists
    if ($user) {
        
        // Verify password
        if (password_verify($password, $user['password'])) {
            
            // Password is correct, start session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $fullName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
            $_SESSION['user_name'] = $fullName ?: ($user['email'] ?? '');
            $_SESSION['logged_in'] = true;
            
            // Handle "Remember Me" functionality
            if ($remember) {
                // Create a token
                $token = bin2hex(random_bytes(32));
                $hashed_token = hash('sha256', $token);
                
                // Store token in database
                $expiry = date('Y-m-d H:i:s', time() + (30 * 24 * 60 * 60)); // 30 days
                try {
                    $stmtToken = $pdo->prepare("INSERT INTO remember_tokens (user_id, token, expiry) VALUES (:user_id, :token, :expiry)");
                    $stmtToken->execute([
                        ':user_id' => $user['id'],
                        ':token' => $hashed_token,
                        ':expiry' => $expiry
                    ]);
                } catch (PDOException $e) {
                    error_log('Remember token insert failed: ' . $e->getMessage());
                }
                
                // Set cookie
                setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/', '', true, true);
            }
            
            // Update last login time
            try {
                $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = :id");
                $updateStmt->execute([':id' => $user['id']]);
            } catch (PDOException $e) {
                error_log('Update last_login failed: ' . $e->getMessage());
            }
            
            // Redirect to dashboard or home page
            $_SESSION['success'] = "Welcome back!";
            header("Location: dashboard.php");
            exit();
            
        } else {
            // Invalid password
            $_SESSION['error'] = "Invalid email or password";
            header("Location: login.php");
            exit();
        }
    } else {
        // User not found
        $_SESSION['error'] = "Invalid email or password";
        header("Location: login.php");
        exit();
    }
} else {
    // If accessed directly without POST
    header("Location: login.php");
    exit();
}
?>
<?php
session_start();
require_once 'config.php';

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
        $message_type = 'error';
    } else {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();
            // Show reassuring message regardless to prevent email enumeration
            $message = 'If an account exists with this email, password reset instructions have been sent.';
            $message_type = 'success';
        } catch (Exception $e) {
            $message = 'Unable to process your request at this moment.';
            $message_type = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>mutSkills - Forgot Password</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; height: 100vh; overflow: hidden; }
        .container { display: flex; height: 100vh; }
        .left-section { flex: 1; display: flex; align-items: center; justify-content: center; padding: 40px; background: #ffffff; }
        .form-box { width: 100%; max-width: 400px; }
        h1 { font-family: 'Playfair Display', serif; font-size: 38px; color: #2d2d2d; margin-bottom: 8px; font-weight: 600; }
        .subtitle { color: #666; margin-bottom: 32px; font-size: 14px; }
        .form-group { margin-bottom: 24px; }
        label { display: block; margin-bottom: 8px; color: #2d2d2d; font-size: 14px; font-weight: 500; }
        input[type="email"] { width: 100%; padding: 14px 16px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; font-family: 'Inter', sans-serif; }
        input[type="email"]:focus { outline: none; border-color: #22c55e; }
        .submit-btn { width: 100%; padding: 14px; background: #22c55e; color: white; border: none; border-radius: 6px; font-size: 15px; font-weight: 600; cursor: pointer; transition: background 0.3s; margin-top: 8px; }
        .submit-btn:hover { background: #16a34a; }
        .back-link { text-align: center; margin-top: 24px; font-size: 14px; color: #666; }
        .back-link a { color: #22c55e; text-decoration: none; font-weight: 600; }
        .alert { padding: 12px 16px; border-radius: 6px; margin-bottom: 20px; font-size: 14px; }
        .alert.error { background: #fee2e2; color: #991b1b; border: 1px solid #ef4444; }
        .alert.success { background: #d1fae5; color: #065f46; border: 1px solid #10b981; }
        .right-section { flex: 1; background: linear-gradient(135deg, #10b981, #059669); display: flex; align-items: center; justify-content: center; position: relative; }
        .overlay { color: white; text-align: center; padding: 40px; }
        .overlay h2 { font-family: 'Playfair Display', serif; font-size: 48px; line-height: 1.2; font-weight: 700; }
        @media (max-width: 968px) { .right-section { display: none; } }
    </style>
</head>
<body>
    <div class="container">
        <div class="left-section">
            <div class="form-box">
                <h1>Reset Password</h1>
                <p class="subtitle">Enter your account email to receive reset instructions</p>

                <?php if (!empty($message)): ?>
                    <div class="alert <?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>

                <form method="POST" action="forgot_password.php">
                    <div class="form-group">
                        <label for="email">Account Email</label>
                        <input type="email" id="email" name="email" placeholder="Enter your email" required autofocus>
                    </div>

                    <button type="submit" class="submit-btn">Send Reset Link</button>

                    <div class="back-link">
                        Remember your password? <a href="login.php">Sign In</a>
                    </div>
                </form>
            </div>
        </div>
        <div class="right-section">
            <div class="overlay">
                <h2>Account<br>Recovery</h2>
            </div>
        </div>
    </div>
</body>
</html>

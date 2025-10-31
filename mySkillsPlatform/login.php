<?php
session_start();

// Display error or success messages
$error_message = isset($_SESSION['error']) ? $_SESSION['error'] : '';
$success_message = isset($_SESSION['success']) ? $_SESSION['success'] : '';

// Clear messages after displaying
unset($_SESSION['error']);
unset($_SESSION['success']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>mutSkills - Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            height: 100vh;
            overflow: hidden;
        }

        .container {
            display: flex;
            height: 100vh;
        }

        .left-section {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
            background: #ffffff;
        }

        .login-form {
            width: 100%;
            max-width: 400px;
        }

        h1 {
            font-family: 'Playfair Display', serif;
            font-size: 42px;
            color: #2d2d2d;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .subtitle {
            color: #666;
            margin-bottom: 40px;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 24px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #2d2d2d;
            font-size: 14px;
            font-weight: 500;
        }

        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 14px 16px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s;
            font-family: 'Inter', sans-serif;
        }

        input[type="email"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #22c55e;
        }

        .password-input {
            position: relative;
        }

        .toggle-password {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #999;
            font-size: 18px;
        }

        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            font-size: 14px;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .remember-me input[type="checkbox"] {
            width: 16px;
            height: 16px;
            cursor: pointer;
        }

        .forgot-password {
            color: #22c55e;
            text-decoration: none;
            font-weight: 500;
        }

        .forgot-password:hover {
            text-decoration: underline;
        }

        .sign-in-btn {
            width: 100%;
            padding: 14px;
            background: #22c55e;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.3s;
            margin-bottom: 16px;
        }

        .sign-in-btn:hover {
            background: #16a34a;
        }

        .sign-in-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        .google-btn {
            width: 100%;
            padding: 14px;
            background: white;
            color: #2d2d2d;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: background 0.3s;
            margin-bottom: 24px;
        }

        .google-btn:hover {
            background: #f8f8f8;
        }

        .google-icon {
            width: 18px;
            height: 18px;
        }

        .signup-link {
            text-align: center;
            font-size: 14px;
            color: #666;
        }

        .signup-link a {
            color: #22c55e;
            text-decoration: none;
            font-weight: 500;
        }

        .signup-link a:hover {
            text-decoration: underline;
        }

        .right-section {
            flex: 1;
            position: relative;
            background: linear-gradient(135deg, #34d399 0%, #10b981 25%, #059669 50%, #047857 75%, #065f46 100%);
            overflow: hidden;
        }

        .right-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 30%, rgba(52, 211, 153, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 80% 70%, rgba(16, 185, 129, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 40% 80%, rgba(5, 150, 105, 0.3) 0%, transparent 50%);
        }

        .overlay {
            position: absolute;
            bottom: 60px;
            left: 60px;
            background: rgba(5, 150, 105, 0.92);
            padding: 50px 60px;
            border-radius: 8px;
            max-width: 400px;
        }

        .overlay h2 {
            font-family: 'Playfair Display', serif;
            color: white;
            font-size: 56px;
            line-height: 1.1;
            font-weight: 600;
        }

        .error-message {
            background: #fee;
            color: #c33;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 16px;
            font-size: 14px;
        }

        .success-message {
            background: #d1fae5;
            color: #065f46;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 16px;
            font-size: 14px;
        }

        @media (max-width: 968px) {
            .container {
                flex-direction: column;
            }

            .right-section {
                display: none;
            }

            .left-section {
                flex: 1;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="left-section">
            <div class="login-form">
                <h1>Welcome Back</h1>
                <p class="subtitle">Enter your email and password to access your account</p>

                <?php if (!empty($error_message)): ?>
                    <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
                <?php endif; ?>

                <?php if (!empty($success_message)): ?>
                    <div class="success-message"><?php echo htmlspecialchars($success_message); ?></div>
                <?php endif; ?>

                <form id="loginForm" method="POST" action="process_login.php">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" placeholder="Enter your email" required>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="password-input">
                            <input type="password" id="password" name="password" placeholder="Enter your password" required>
                            <span class="toggle-password" onclick="togglePassword()">👁️</span>
                        </div>
                    </div>

                    <div class="form-options">
                        <div class="remember-me">
                            <input type="checkbox" id="remember" name="remember">
                            <label for="remember" style="margin: 0;">Remember me</label>
                        </div>
                        <a href="forgot_password.php" class="forgot-password">Forgot Password?</a>
                    </div>

                    <button type="submit" class="sign-in-btn">Sign In</button>

                    <button type="button" class="google-btn" onclick="window.location.href='google_login.php'">
                        <svg class="google-icon" viewBox="0 0 24 24">
                            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                        </svg>
                        Sign In with Google
                    </button>

                    <div class="signup-link">
                        Don't have an account? <a href="signUp.php">Sign Up</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="right-section">
            <div class="overlay">
                <h2>Connect<br>Create<br>Succeed</h2>
            </div>
        </div>
    </div>
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleBtn = document.querySelector('.toggle-password');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleBtn.textContent = '🙈';
            } else {
                passwordInput.type = 'password';
                toggleBtn.textContent = '👁️';
            }
        }
    </script>
</body>
</html>
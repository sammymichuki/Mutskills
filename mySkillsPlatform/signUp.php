<?php
session_start();

// Display error or success messages
$error_message = isset($_SESSION['error']) ? $_SESSION['error'] : '';
$success_message = isset($_SESSION['success']) ? $_SESSION['success'] : '';

// Preserve form data if there was an error
$firstname = isset($_SESSION['form_data']['firstname']) ? $_SESSION['form_data']['firstname'] : '';
$lastname = isset($_SESSION['form_data']['lastname']) ? $_SESSION['form_data']['lastname'] : '';
$email = isset($_SESSION['form_data']['email']) ? $_SESSION['form_data']['email'] : '';

// Clear messages and form data after displaying
unset($_SESSION['error']);
unset($_SESSION['success']);
unset($_SESSION['form_data']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>mutSkills - Sign Up</title>
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
            overflow-y: auto;
        }

        .signup-form {
            width: 100%;
            max-width: 400px;
            padding: 20px 0;
        }

        h1 {
            font-family: 'Playfair Display', serif;
            font-size: 42px;
            color: #2d2d2d;
            margin-bottom: 8px;
            margin-top: 60px;
            font-weight: 600;
        }

        .subtitle {
            color: #666;
            margin-bottom: 32px;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #2d2d2d;
            font-size: 14px;
            font-weight: 500;
        }

        input[type="text"],
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

        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #22c55e;
        }

        .name-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
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

        .password-strength {
            margin-top: 8px;
            font-size: 12px;
        }

        .strength-bar {
            height: 4px;
            background: #e0e0e0;
            border-radius: 2px;
            margin-top: 4px;
            overflow: hidden;
        }

        .strength-fill {
            height: 100%;
            width: 0%;
            transition: width 0.3s, background 0.3s;
        }

        .strength-weak { background: #ff4444; width: 33%; }
        .strength-medium { background: #ffaa00; width: 66%; }
        .strength-strong { background: #22c55e; width: 100%; }

        .terms-checkbox {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            margin-bottom: 24px;
            font-size: 14px;
        }

        .terms-checkbox input[type="checkbox"] {
            width: 16px;
            height: 16px;
            cursor: pointer;
            margin-top: 2px;
            flex-shrink: 0;
        }

        .terms-checkbox label {
            margin: 0;
            color: #666;
            font-weight: 400;
        }

        .terms-checkbox a {
            color: #22c55e;
            text-decoration: none;
            font-weight: 500;
        }

        .terms-checkbox a:hover {
            text-decoration: underline;
        }

        .sign-up-btn {
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

        .sign-up-btn:hover {
            background: #16a34a;
        }

        .sign-up-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        .divider {
            text-align: center;
            margin: 20px 0;
            color: #999;
            font-size: 14px;
            position: relative;
        }

        .divider::before,
        .divider::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 42%;
            height: 1px;
            background: #ddd;
        }

        .divider::before {
            left: 0;
        }

        .divider::after {
            right: 0;
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

        .login-link {
            text-align: center;
            font-size: 14px;
            color: #666;
        }

        .login-link a {
            color: #22c55e;
            text-decoration: none;
            font-weight: 500;
        }

        .login-link a:hover {
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

        .overlay p {
            color: rgba(255, 255, 255, 0.9);
            font-size: 16px;
            margin-top: 16px;
            line-height: 1.5;
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

            .name-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="left-section">
            <div class="signup-form">
                <h1>Create Account</h1>
                <p class="subtitle">Join mutSkills to start your learning journey</p>

                <?php if (!empty($error_message)): ?>
                    <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
                <?php endif; ?>

                <?php if (!empty($success_message)): ?>
                    <div class="success-message"><?php echo htmlspecialchars($success_message); ?></div>
                <?php endif; ?>

                <form id="signupForm" method="POST" action="process_signup.php">
                    <div class="name-row">
                        <div class="form-group">
                            <label for="firstname">First Name</label>
                            <input type="text" id="firstname" name="firstname" placeholder="John" value="<?php echo htmlspecialchars($firstname); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="lastname">Last Name</label>
                            <input type="text" id="lastname" name="lastname" placeholder="Doe" value="<?php echo htmlspecialchars($lastname); ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" placeholder="john.doe@example.com" value="<?php echo htmlspecialchars($email); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="password-input">
                            <input type="password" id="password" name="password" placeholder="Create a strong password" required>
                            <span class="toggle-password" onclick="togglePassword('password')">👁️</span>
                        </div>
                        <div class="password-strength">
                            <div class="strength-bar">
                                <div class="strength-fill" id="strengthFill"></div>
                            </div>
                            <span id="strengthText"></span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="confirmPassword">Confirm Password</label>
                        <div class="password-input">
                            <input type="password" id="confirmPassword" name="confirmPassword" placeholder="Re-enter your password" required>
                            <span class="toggle-password" onclick="togglePassword('confirmPassword')">👁️</span>
                        </div>
                    </div>

                    <div class="terms-checkbox">
                        <input type="checkbox" id="terms" name="terms" required>
                        <label for="terms">
                            I agree to the <a href="terms.php">Terms of Service</a> and <a href="privacy.php">Privacy Policy</a>
                        </label>
                    </div>

                    <button type="submit" class="sign-up-btn">Create Account</button>

                    <div class="divider">OR</div>

                    <button type="button" class="google-btn" onclick="window.location.href='google_signup.php'">
                        <svg class="google-icon" viewBox="0 0 24 24">
                            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                        </svg>
                        Sign Up with Google
                    </button>

                    <div class="login-link">
                        Already have an account? <a href="login.php">Sign In</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="right-section">
            <div class="overlay">
                <h2>Start Your Journey</h2>
                <p>Unlock unlimited skills and connect with a community of learners worldwide.</p>
            </div>
        </div>
    </div>

    <script>
        function togglePassword(fieldId) {
            const passwordInput = document.getElementById(fieldId);
            const toggleBtn = passwordInput.nextElementSibling;
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleBtn.textContent = '🙈';
            } else {
                passwordInput.type = 'password';
                toggleBtn.textContent = '👁️';
            }
        }

        // Password strength checker
        const passwordInput = document.getElementById('password');
        const strengthFill = document.getElementById('strengthFill');
        const strengthText = document.getElementById('strengthText');

        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;

            if (password.length >= 8) strength++;
            if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
            if (password.match(/[0-9]/)) strength++;
            if (password.match(/[^a-zA-Z0-9]/)) strength++;

            strengthFill.className = 'strength-fill';
            
            if (password.length === 0) {
                strengthText.textContent = '';
            } else if (strength <= 2) {
                strengthFill.classList.add('strength-weak');
                strengthText.textContent = 'Weak password';
                strengthText.style.color = '#ff4444';
            } else if (strength === 3) {
                strengthFill.classList.add('strength-medium');
                strengthText.textContent = 'Medium password';
                strengthText.style.color = '#ffaa00';
            } else {
                strengthFill.classList.add('strength-strong');
                strengthText.textContent = 'Strong password';
                strengthText.style.color = '#22c55e';
            }
        });

        // Client-side validation
        document.getElementById('signupForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirmPassword').value;

            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return false;
            }

            if (password.length < 8) {
                e.preventDefault();
                alert('Password must be at least 8 characters long!');
                return false;
            }
        });
    </script>
</body>
</html>
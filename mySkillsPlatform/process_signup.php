<?php
session_start();
require_once 'config.php';

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Sanitize and validate input
    $firstname = trim($_POST['firstname']);
    $lastname = trim($_POST['lastname']);
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $password = trim($_POST['password']);
    $confirmPassword = trim($_POST['confirmPassword']);
    $terms = isset($_POST['terms']) ? true : false;
    
    // Store form data in session to repopulate form if there's an error
    $_SESSION['form_data'] = [
        'firstname' => $firstname,
        'lastname' => $lastname,
        'email' => $email
    ];
    
    // Validation
    $errors = [];
    
    // Check if fields are empty
    if (empty($firstname) || empty($lastname) || empty($email) || empty($password) || empty($confirmPassword)) {
        $errors[] = "All fields are required";
    }
    
    // Validate name fields (only letters and spaces)
    if (!preg_match("/^[a-zA-Z\s]+$/", $firstname)) {
        $errors[] = "First name should contain only letters";
    }
    
    if (!preg_match("/^[a-zA-Z\s]+$/", $lastname)) {
        $errors[] = "Last name should contain only letters";
    }
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    // Check password length
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    }
    
    // Check password strength
    if (!preg_match("/[A-Z]/", $password)) {
        $errors[] = "Password must contain at least one uppercase letter";
    }
    
    if (!preg_match("/[a-z]/", $password)) {
        $errors[] = "Password must contain at least one lowercase letter";
    }
    
    if (!preg_match("/[0-9]/", $password)) {
        $errors[] = "Password must contain at least one number";
    }
    
    // Check if passwords match
    if ($password !== $confirmPassword) {
        $errors[] = "Passwords do not match";
    }
    
    // Check if terms are accepted
    if (!$terms) {
        $errors[] = "You must agree to the Terms of Service and Privacy Policy";
    }
    
    // Check if email already exists
    if (empty($errors)) {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
            $stmt->execute([':email' => $email]);
            if ($stmt->fetch()) {
                $errors[] = "An account with this email already exists";
            }
        } catch (PDOException $e) {
            error_log('Signup email check failed: ' . $e->getMessage());
            $errors[] = "Unexpected error. Please try again.";
        }
    }
    
    // If there are errors, redirect back to signup page
    if (!empty($errors)) {
        $_SESSION['error'] = implode("<br>", $errors);
        header("Location: signUp.php");
        exit();
    }
    
    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Combine first and last name
    $full_name = $firstname . ' ' . $lastname;
    
    try {
        $pdo = getDBConnection();
        // Insert user into database with separate first/last names to match dashboard usage
        $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, password, created_at) VALUES (:first_name, :last_name, :email, :password, NOW())");
        $stmt->execute([
            ':first_name' => $firstname,
            ':last_name' => $lastname,
            ':email' => $email,
            ':password' => $hashed_password
        ]);

        // Get the newly created user ID
        $user_id = (int)$pdo->lastInsertId();

        // Clear form data from session
        unset($_SESSION['form_data']);

        // Option: Do NOT auto-login; require explicit login for clarity
        $_SESSION['success'] = "Account created successfully! Please sign in.";
        header("Location: login.php");
        exit();
    } catch (PDOException $e) {
        error_log('Signup insert failed: ' . $e->getMessage());
        $_SESSION['error'] = "Something went wrong. Please try again.";
        header("Location: signUp.php");
        exit();
    }
    
} else {
    // If accessed directly without POST
    header("Location: signUp.php");
    exit();
}

// Optional: Function to send welcome email
function sendWelcomeEmail($email, $name) {
    $subject = "Welcome to mutSkills!";
    $message = "
    <html>
    <head>
        <title>Welcome to mutSkills</title>
    </head>
    <body>
        <h2>Welcome, $name!</h2>
        <p>Thank you for joining mutSkills. We're excited to have you on board!</p>
        <p>Start exploring our platform and unlock unlimited learning opportunities.</p>
        <p>Best regards,<br>The mutSkills Team</p>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: noreply@mutskills.com" . "\r\n";
    
    mail($email, $subject, $message, $headers);
}
?>
<?php
session_start();
session_regenerate_id(true); // Regenerate session ID for security

include '../database/connectdatabase.php';
$dbname = "project";
mysqli_select_db($conn, $dbname);

$errorMessage = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    
    // Check login credentials with all necessary fields
    $sql = "SELECT login_id, email, password, role, user_id, employer_id FROM tbl_login WHERE email = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        if (password_verify($password, $row['password'])) {
            // Clear any existing session data
            session_unset();
            
            // Set common session variables
            $_SESSION['logged_in'] = true;
            $_SESSION['email'] = $row['email'];
            $_SESSION['login_id'] = $row['login_id'];
            $_SESSION['role'] = $row['role'];
            
            switch ($row['role']) {
                case 1:  // User
                    $_SESSION['user_type'] = 'user';
                    $_SESSION['user_id'] = $row['user_id'];
                    header("Location: ../userdashboard/userdashboard.php");
                    break;
                    
                case 2:  // Employer
                    $_SESSION['user_type'] = 'employer';
                    $_SESSION['employer_id'] = $row['employer_id'];
                    header("Location: ../userdashboard/employerdashboard.php");
                    break;
                    
                case 3:  // Admin
                    $_SESSION['user_type'] = 'admin';
                    $_SESSION['admin_id'] = $row['login_id'];
                    header("Location: ../userdashboard/admindashboard.html");
                    break;
                    
                default:
                    $errorMessage = "Invalid user role";
                    session_destroy();
                    break;
            }
            
            if (empty($errorMessage)) {
                // Set session cookie parameters for better security
                $params = session_get_cookie_params();
                setcookie(session_name(), session_id(), [
                    'expires' => time() + 3600, // 1 hour
                    'path' => '/',
                    'domain' => '',
                    'secure' => true,
                    'httponly' => true,
                    'samesite' => 'Lax'
                ]);
                
                exit();
            }
        } else {
            $errorMessage = "Invalid password";
        }
    } else {
        $errorMessage = "Email not found";
    }
}

// If not POST request or login failed, show the login form
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="loginvalidation.css">
    <script>
        function validateEmail() {
            const email = document.getElementById("email").value;
            const emailError = document.getElementById("email-error");

            if (!email) {
                emailError.textContent = "E-mail is required.";
                return false;
            }

            if (email[0] === " ") {
                emailError.textContent = "E-mail must not start with a space.";
                return false;
            }

            const emailRegex = /^[a-zA-Z0-9][^\s@]*@(gmail\.com|yahoo\.com|hotmail\.com|amaljyothi\.ac\.in|mca\.ajce\.in)$/;

            if (!emailRegex.test(email)) {
                emailError.textContent = "Invalid email format.";
                return false;
            }

            emailError.textContent = "";
            return true;
        }

        function validateForm() {
            return validateEmail();
        }
    </script>
</head>
<body>
    <div class="container">
        <div class="image-container">
            <a href="../homepage/homepage.html" class="back-to-website">
                <span class="arrow-circle">←</span> Return to Homepage
            </a>
        </div>

        <div class="form-container">
            <h1><span id="wel">Welcome Back<br></span><span id="log">Sign In</span></h1>

            <?php if(!empty($errorMessage)): ?>
                <div class="error-message"><?php echo $errorMessage; ?></div>
            <?php endif; ?>

            <form method="POST" id="form" onsubmit="return validateForm()">
                <input type="email" id="email" name="email" placeholder="Enter your email address" onkeyup="validateEmail()" required>
                <p class="error" id="email-error"></p>

                <input type="password" name="password" placeholder="Enter your password" required>
                
                <input type="submit" value="Sign In">
            </form>

            <p id="register">New to our platform? <a href="../Signup/signup.php">Create an Account</a></p>
            <p class="forgot-password">Forgot Password? <a href="forgotpassword.php">Reset Password</a></p>
        </div>
    </div>
</body>
</html>

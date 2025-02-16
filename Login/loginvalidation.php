<?php
ob_start();
session_start();
session_regenerate_id(true); // Ensures a fresh session

include '../database/connectdatabase.php';
$dbname = "project";
mysqli_select_db($conn, $dbname);

$errorUserNotFound = "";
$errorIncorrectPassword = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') 
{
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    $checkemail = "SELECT * FROM tbl_login WHERE email='$email'";
    $checkemailresult = mysqli_query($conn, $checkemail);

    if ($checkemailresult && mysqli_num_rows($checkemailresult) > 0) 
    {
        $userdata = mysqli_fetch_assoc($checkemailresult);
        
        if (password_verify($password, $userdata['password'])) 
        {
            $_SESSION['email'] = $email;
            $_SESSION['user_id'] = $userdata['login_id']; // Ensure 'login_id' exists in DB
            $_SESSION['role'] = $userdata['role'];

            session_write_close(); // Ensure session is saved before redirecting

            // Redirect based on role
            switch ($userdata['role']) 
            {
                case 1:
                    header("Location: ../userdashboard/userdashboard.php");
                    exit();
                case 2:
                    header("Location: ../userdashboard/employerdashboard.php");
                    exit();
                case 3:
                    header("Location: ../userdashboard/admindashboard.html");
                    exit();
                default:
                    die("Invalid role: " . htmlspecialchars($userdata['role']));
            }
        } 
        else 
        {
            $errorIncorrectPassword = "Incorrect password";
        }
    } 
    else 
    {
        $errorUserNotFound = "User not found";
    }
}

ob_end_flush();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="loginvalidation.css">
    <script>
        function validateEmail() 
        {
            const email = document.getElementById("email").value;
            const emailError = document.getElementById("email-error");

            // Check if email is empty
            if (!email) 
            {
                emailError.textContent = "E-mail is required.";
                return false;
            }

            // Check if email starts with a space
            if (email[0] === " ") 
            {
                emailError.textContent = "E-mail must not start with a space.";
                return false;
            }

            // Email validation regex
            const emailRegex = /^[a-zA-Z0-9][^\s@]*@(gmail\.com|yahoo\.com|hotmail\.com|amaljyothi\.ac\.in|mca\.ajce\.in)$/;

            // Check email format
            if (!emailRegex.test(email)) 
            {
                emailError.textContent = "Invalid email format.";
                return false;
            }

            // Clear error message if email is valid
            emailError.textContent = "";
            return true;
        }

        function validateForm() 
        {
            return validateEmail();
        }
    </script>
</head>
<body>
    <div class="container">
        <div class="image-container">
            <a href="../homepage/homepage.html" class="back-to-website">
                <span class="arrow-circle">←</span> Back to website
            </a>
        </div>

        <div class="form-container">
            <h1><span id="wel">Welcome back<br></span><span id="log">Log in</span></h1>

            <!-- Display PHP Error Messages -->
            <?php if(!empty($errorUserNotFound)): ?>
                <div class="error-message"><?php echo $errorUserNotFound; ?></div>
            <?php endif; ?>

            <?php if(!empty($errorIncorrectPassword)): ?>
                <div class="error-message"><?php echo $errorIncorrectPassword; ?></div>
                <div class="forgot-text">
                    <p id="forgot">Forgot password? <a href="forgotpassword.php">Reset</a></p>
                </div>
            <?php endif; ?>

            <form method="POST" id="form" onsubmit="return validateForm()">
                <input type="email" id="email" name="email" placeholder="Email" onkeyup="validateEmail()" required>
                <p class="error" id="email-error"></p>

                <input type="password" name="password" placeholder="Password" required>

                <input type="submit" value="Log in">
            </form>

            <p id="register">Don't have an account? <a href="../Signup/signup.php">Register</a></p>
        </div>
    </div>
</body>
</html>

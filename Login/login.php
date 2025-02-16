<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include '../database/connectdatabase.php';
$dbname = "project";
mysqli_select_db($conn, $dbname);

$error = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') 
{
    $email =trim($_POST['email']);
    $password = trim($_POST['password']);
    
    if (empty($email) || empty($password)) 
    {
        $error = "Please enter both email and password";
    } 
    else 
    {
        $query = "SELECT * FROM tbl_login WHERE email = '$email'";
        $result = mysqli_query($conn, $query);
        
        if (!$result) 
        {
            die("Database error: " . mysqli_error($conn));
        } 
        
        if (mysqli_num_rows($result) > 0) 
        {
            $user = mysqli_fetch_assoc($result);
            
            if (password_verify($password, $user['password'])) 
            {
                $_SESSION['email'] = $email;
                $_SESSION['login_id'] = $user['login_id'];
                $_SESSION['role'] = $user['role'];

                

                switch ($user['role']) 
                {
                    case 1:
                        header("Location: ../userdashboard/userdashboard.php");
                        exit();
                    case 2:
                        header("Location: ../userdashboard/employerdashboard.html");
                        exit();
                    case 3:
                        header("Location: ../userdashboard/admindashboard.html");
                        exit();
                    default:
                        $error = "Invalid user role.";
                }

                // JavaScript fallback for redirection
                echo "<script>window.location.href = '$redirect_url';</script>";
                exit();
            } 
            else 
            {
                $error = "Incorrect password";
            }
        } 
        else 
        {
            $error = "User not found";
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="login.css">
</head>
<body>
    <div class="container">
        <div class="image-container">
            <a href="../homepage/homepage.html" class="back-to-website"><span class="arrow-circle">←</span> Back to website</a>
        </div>
        <div class="form-container">
            <?php if(!empty($error)): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <h1><span id="wel">Welcome back<br></span><span id="log">Log in</span></h1>
            <form method="POST" id="form">
                <input type="email" name="email" placeholder="Email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                <input type="password" name="password" placeholder="Password" required>
                <?php if(!empty($error) && $error === "Incorrect password"): ?>
                    <div class="forgot-text">
                        <p id="forgot">Forgot password?<a href="forgotpassword.php">Reset</a></p>
                    </div>
                <?php endif; ?>
                <input type="submit" value="Log in">
            </form>
            <p id="register">Don't have an account? <a href="../Signup/signup.php">Register</a></p>
        </div>
    </div>
</body>
</html>
<?php
    session_start();
    include '../database/connectdatabase.php';
    $dbname="project";
    mysqli_select_db($conn,$dbname);
    if($_SERVER['REQUEST_METHOD']=='POST')
    {
        $email=$_POST['email'];
        $password=$_POST['password'];
        $error="";
        
        // checking whether the used had an account
        $checkemail="SELECT * FROM tbl_login WHERE email='$email' ";
        $checkemailresult=mysqli_query($conn,$checkemail);
        if(mysqli_num_rows($checkemailresult)>0)
        {
            $userdata=mysqli_fetch_assoc($checkemailresult);
            if(password_verify($password,$userdata['password']))//password_verify() is a built-in PHP function used to verify a hashed password.
            {
                $_SESSION['email']=$email;
                $_SESSION['user_id']=$userdata['id'];
                $_SESSION['role']=$userdata['role'];
                $_SESSION['username']=$userdata['username'];

                // redirecting to the user dashboard according to there role
                if($userdata['role']==1)
                {
                    header("Location:../userdashboard/userdashboard.php");
                exit();
                }
                else if($userdata['role']==2)
                {
                    header("Location:../userdashboard/employerdashboard.html");
                    exit();
                }
                else if($userdata['role']==3)
                {
                    header("Location:../userdashboard/admindashboard.html");
                    exit();
                }
            }
            else
            {
                $error="Incorrect password";
            }
        }
        else
        {
            $error="User not found";
        }

        // checking in tbl_user
        // $checkemail_tbl_user="SELECT * FROM tbl_login WHERE email='$email' ";//selecting the emaiL from the database according to the input by the user
        // $checkemailresult_tbl_user=mysqli_query($conn,$checkemail_tbl_user);//converts the database result into php associative array.so that we can easily access the column values using column name
        

        // checking in tbl_employer
        // $checkemail_tbl_employer="SELECT * FROM tbl_employer WHERE email='$email' ";//selecting the emaiL from the database according to the input by the user
        // $checkemailresult_tbl_employer=mysqli_query($conn,$checkemail_tbl_employer);//converts the database result into php associative array.so that we can easily access the column values using column name


        // chcek whether the user exist or not in tbl_user
        
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
            <div class="error-message"><?php echo $error; ?></div><?php endif; ?>
            <h1><span id="wel">Welcome back<br></span><span id="log">Log in</span></h1>
            <form method="POST" id="form">
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Password" required>
                <?php if(!empty($error) && $error === "Incorrect password"): ?>
                    <div class="forgot-text">
                        <p id="forgot">Forgot password?<a href="../forgotpassword/forgotpassword.php">Reset</a></p>
                    </div>
                <?php endif; ?>
                <input type="submit" value="Log in">
            </form>
            <p id="register">Don't have an account? <a href="../Signup/signup.php">Register</a></p>
        </div>
    </div>
</body>
</html>
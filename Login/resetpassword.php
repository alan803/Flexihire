<?php
    session_start();
    include "../database/connectdatabase.php";
    $dbname="project";
    mysqli_select_db($conn,$dbname);
    $email = urldecode($_GET['email']);
    if($_SERVER['REQUEST_METHOD']=='POST')
    {
        $password=$_POST['password'];
        $hashed_password=password_hash($password,PASSWORD_DEFAULT);
        //collecting email from session
        if(isset($_GET['email']) && filter_var($_GET['email'], FILTER_VALIDATE_EMAIL))
        {
            echo $email;
            $sql = "UPDATE tbl_login SET password='$hashed_password' WHERE email='$email'";
            if (mysqli_query($conn, $sql)) 
            {
                header("Location: loginvalidation.php");
            } else {
                echo "Error updating password: " . mysqli_error($conn);
            }
        }
    }
    mysqli_close( $conn );
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="resetpassword.css">
    <script>
        function validatePassword() 
        {
            const password = document.getElementById("password").value;
            const error = document.getElementById("passwordError");
            if (!password) 
            {
                error.textContent = "Password is required.";
                return false;
            } 
            else if (password.length < 6) 
            {
                error.textContent = "Password must be at least 6 characters.";
                return false;
            } 
            else if (!/[^a-zA-Z0-9]/.test(password)) 
            {
                error.textContent = "Password should  contain atleast one special character.";
                return false;
            }
            error.textContent = "";
            return true;
        }

        function validateConfirmPassword() 
        {
            const password = document.getElementById("password").value;
            const confirmpassword = document.getElementById("confirmpassword").value;
            const error = document.getElementById("confirmpasswordError");
            if (!confirmpassword) 
            {
                error.textContent = "Confirm password is required.";
                return false;
            } 
            else if (confirmpassword !== password) 
            {
                error.textContent = "Passwords do not match.";
                return false;
            }
            error.textContent = "";
            return true;
        }
        function validateForm() // It ensures that all fields in the form meet their respective validation criteria before allowing the form to be submitted.
        {
            const validPassword = validatePassword();
            const validConfirmPassword = validateConfirmPassword();
            return validPassword && validConfirmPassword;//The && operator combines the results of all the validations. If any of the individual validations return false, the overall result will also be false.
        }
    </script>
</head>
<body>
    <div class="container">
        <div class="form-box">
            <span><img src="update.png"></span>
            <h2>Update password</h2>
            <p>Please set a new password</p>
            <form method="post" onsubmit="return validateForm()">
                <input type="password" name="password" id="password" placeholder="Enter your password" onkeyup="validatePassword()">
                <p class="error" id="passwordError"></p>
                <input type="password" name="confirmpassword" id="confirmpassword" placeholder="Enter your password" onkeyup="validateConfirmPassword()">
                <p class="error" id="confirmpasswordError"></p>
                <!-- <p class="error" id="emailError" ></p> -->
                <button type="submit">Change password</button>
            </form>
            <a href="loginvalidation.php" class="back-link"><span class="arrow-circle">←</span> Back to log in</a>
        </div>
    </div>
</body>
</html>
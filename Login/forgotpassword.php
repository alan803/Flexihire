<?php
    session_start();
    include "../database/connectdatabase.php";
    $error="";
    $dbname="project";
    mysqli_select_db($conn,$dbname);
    if($_SERVER['REQUEST_METHOD']=='POST')
    {
        $email=filter_var(trim($_POST['email']),FILTER_SANITIZE_EMAIL);
        $sql=mysqli_prepare($conn,"SELECT * FROM tbl_login WHERE email=?");//mysqli_preapre is used to prevent sql injection(means injecting malicious sql code into an application)
        mysqli_stmt_bind_param($sql,"s",$email);//used for binding parameter,'s' means string is passing
        mysqli_stmt_execute($sql);//executes the statement
        $result=mysqli_stmt_get_result($sql);//fetches the result

        //check if email is linked with an account or not
        if(mysqli_num_rows($result)===1)
        {
            header("Location: send_otp.php?email=" . urlencode($email));//safely redirects to send_otp page.urlencode is used for safely tarnsfer the data to the url
            exit();
        }
        else
        {
            $error="Email is not linked with any account";
            mysqli_stmt_close($sql);
        }
    }
    mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="forgotpassword.css">
</head>
<script>
    function validateEmail() 
        {
            const email = document.getElementById("email").value;
            const error = document.getElementById("emailError");

            // Check if email is empty
            if (!email) 
            {
                error.textContent = "E-mail is required.";
                return false;
            }

            // Check if email starts with a space or invalid character
            if (email[0] === " ") 
            {
                 error.textContent = "E-mail must start with a letter.";
                return false;
            }

            // Check if email has valid format and domain
            // const emailRegex = ;
        if (!/^[a-zA-Z0-9][^\s@]*@(gmail\.com|yahoo\.com|hotmail\.com|amaljyothi\.ac\.in|mca\.ajce\.in)$/.test(email)) 
        {
            // Check if it's the domain that's invalid
            if (email.includes('@')) 
            {
                const domain = email.split('@')[1];
                if (domain !== 'gmail.com' && domain !== 'yahoo.com') 
                {
                    error.textContent = "Invalid domain";
                    return false;
                }
            }
            error.textContent = "Invalid email address.";
            return false;
        }

    // Clear error message if all validations pass
        error.textContent = "";
        return true;
    }
    function validateForm() // It ensures that all fields in the form meet their respective validation criteria before allowing the form to be submitted.
        {
            const validEmail = validateEmail();
            return validEmail;//The && operator combines the results of all the validations. If any of the individual validations return false, the overall result will also be false.
        }
</script>
<body>
    <div class="container">
        <div class="form-box">
            <span><img src="fingerprint.png"></span>
            <h2>Forgot password?</h2>
            <p>No worries, we’ll send you reset <Span id="sep">instructions.</Span></p>
            <form method="post" onsubmit="return validateForm()">
                <input type="email" name="email" id="email" placeholder="Enter your email" onkeyup="validateEmail()">
                <p class="error" id="emailError"><?php echo htmlspecialchars($error); ?></p>
                <button type="submit">Reset password</button>
            </form>
            <a href="loginvalidation.php" class="back-link"><span class="arrow-circle">←</span> Back to log in</a>
        </div>
    </div>
</body>
</html>
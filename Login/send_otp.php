<?php
    session_start();
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    $error="";
    //Import PHPMailer classes into the global namespace
    //These must be at the top of your script, not inside a function
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\SMTP;
    use PHPMailer\PHPMailer\Exception;
    include "../database/connectdatabase.php";
    $dbname="project";
    mysqli_select_db($conn,$dbname);
    //Load Composer's autoloader
    require '../vendor/autoload.php';
    function generateRandomOTP($length = 4) 
    {
        //rand(0, pow(10, $length) - 1) ensures that the OTP respects the length parameter.
        //str_pad(..., '0', STR_PAD_LEFT) makes sure OTPs like 0073 are not converted to 73.
        $otp = str_pad(rand(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);

         // Store the OTP and its expiration time in the session
        $_SESSION['verification_code']=$otp;
        $_SESSION['otp_expiry']=time()+600;//otp expires after 10 minutes
        return $otp;
    }

    //generate otp once a time don't create every time when the page reloads
    // Check if OTP exists or needs to be generated
    if (!isset($_SESSION['verification_code']) || !isset($_SESSION['otp_expiry']) || time() > $_SESSION['otp_expiry']) 
    {
        // Generate the OTP only if it hasn't been generated or has expired
        $otp = generateRandomOTP();
    } 
    else 
    {
        // Use the existing OTP if it is still valid
        $otp = $_SESSION['verification_code'];
    }
    


    
    //Create an instance; passing `true` enables exceptions
    
    if (isset($_GET['email']) && filter_var($_GET['email'], FILTER_VALIDATE_EMAIL)) 
    {
        $email = urldecode($_GET['email']); // Decode the email from the URL
        // $sql="SELECT username FROM tbl_login where email='$email'";
        // $result=mysqli_query($conn,$sql);
        // $row=mysqli_fetch_assoc($result);
        // $username=$row['username'];
        $mail = new PHPMailer(true);
        try 
        {
            //Server settings
            $mail->isSMTP();                                            //Send using SMTP
            $mail->Host       = 'smtp.gmail.com';                     //Set the SMTP server to send through
            $mail->SMTPAuth   = true;                                   //Enable SMTP authentication
            $mail->Username   = 'flexihire369@gmail.com';                     //SMTP username
            $mail->Password   = 'wxnj ujrn pxlc fykl';                               //SMTP password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;            //Enable implicit TLS encryption
            $mail->Port       = 587;                                    //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`

            //Recipients
            $mail->setFrom('flexihire369@gmail.com', 'Flexihire');
            $mail->addAddress($email);     //Add a recipient
            $mail->addReplyTo('flexihire369@gmail.com', 'Information');

            //Attachments
            // $mail->addAttachment('/var/tmp/file.tar.gz');         //Add attachments
            // $mail->addAttachment('/tmp/image.jpg', 'new.jpg');    //Optional name

            //Content
            $mail->isHTML(true);                                  //Set email format to HTML
            $mail->Subject = 'Password reset for your account ';//.$username;
            $mail->Body    = 'Verification code for password reset  '.$otp;
            $mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

            $mail->send();
        }
        catch (Exception $e) 
        {
            echo "Email could not be sent. Error: " . $mail->ErrorInfo; // ✅ Show the error instead
        }
    } 
    else 
    {
        echo 'Invalid or missing email address.';
    }
     
    
    if($_SERVER['REQUEST_METHOD']=='POST')
    {
        $entered_otp=implode("",$_POST['otp']);
        if (!isset($_POST['otp']) || in_array("", $_POST['otp'])) 
        {
            $error = "Please enter the OTP.";
        } 
        else if ($entered_otp === $_SESSION['verification_code']) 
        {
            unset($_SESSION['verification_code']);
            unset($_SESSION['otp_expiry']);
            header("Location: resetpassword.php?email=" . urlencode($email));
            exit();
        } 
        else 
        {
            $error = "Incorrect OTP.";
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
    <link rel="stylesheet" href="send_otp.css">
</head>
<script defer>
        document.addEventListener("DOMContentLoaded", function () 
        {
            const inputs = document.querySelectorAll(".otp-inputs input");
            inputs.forEach((input, index) => 
            {
                input.addEventListener("input", (e) => {
                    if (e.target.value.length === 1 && index < inputs.length - 1) 
                    {
                        inputs[index + 1].focus();
                    }
                });
                input.addEventListener("keydown", (e) => {
                    if (e.key === "Backspace" && index > 0 && !e.target.value) 
                    {
                        inputs[index - 1].focus();
                    }
                });
            });
        });
    </script>
<body>
<div class="form-container">
        <form  method="POST" class="password-reset-form">
            <span id="emaillogo"><img src="emailicon.png"></span>
            <h2 id="para1">OTP Verification</h2>
            <p id="para2">Enter the 4 digit code sent to your <br><span id="para-1">email</span></p>
            <div class="otp-inputs">
                <input type="text" name="otp[]" maxlength="1" required>
                <input type="text" name="otp[]" maxlength="1" required>
                <input type="text" name="otp[]" maxlength="1" required>
                <input type="text" name="otp[]" maxlength="1" required>
            </div>
            <button type="submit">Verify</button>
            <?php if (!empty($error)) { ?>
                <p class="error-message"><?php echo $error; ?></p>
            <?php } ?>
            <p class="resend">Didn't receive the code?<a href="#"> Resend OTP</a></p>
            <a href="loginvalidation.php" class="back-link"><span class="arrow-circle">←</span> Back to log in</a>
        </form>
    </div>
</body>
</html>
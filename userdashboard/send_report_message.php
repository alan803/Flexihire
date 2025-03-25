<?php
session_start();
require_once '../database/connectdatabase.php';
require '../vendor/autoload.php';  // Using Composer's autoloader

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login/loginvalidation.php');
    exit();
}

// Get reporter_id from URL
$reporter_id = filter_input(INPUT_GET, 'reporter_id', FILTER_VALIDATE_INT);

if (!$reporter_id) {
    header('Location: reports.php?error=invalid_id');
    exit();
}

// Get employer details (removed username from query)
$sql = "SELECT l.email, e.company_name, e.profile_image 
        FROM tbl_employer e 
        JOIN tbl_login l ON e.employer_id = l.employer_id 
        WHERE e.employer_id = ?";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $reporter_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$employer = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if ($employer) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'flexihire369@gmail.com';
        $mail->Password = 'xtik ztxp zaji bszk';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;
        
        // Additional SMTP settings
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        // Recipients
        $mail->setFrom('flexihire369@gmail.com', 'FlexiHire');
        $mail->addAddress($employer['email']);

        // Content
        $mail->isHTML(true);
        $mail->Subject = "Report Notice from FlexiHire";
        
        // HTML Email Body (removed username)
        $emailBody = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <div style='background-color: #f8f9fa; padding: 20px; text-align: center; border-radius: 10px 10px 0 0;'>
                <h2 style='color: #1a56db; margin: 0;'>Report Notice from FlexiHire</h2>
            </div>
            
            <div style='background-color: white; padding: 20px; border-radius: 0 0 10px 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);'>
                <p>Dear <strong>{$employer['company_name']}</strong>,</p>
                
                <p>Your job posting has been reported for violation of our terms. Please review and update your posting accordingly.</p>
                
                <div style='margin: 20px 0; padding: 15px; background-color: #f8f9fa; border-radius: 5px;'>
                    <p style='margin: 0; color: #666;'>
                        <strong>Account Details:</strong><br>
                        Company Name: {$employer['company_name']}<br>
                        Email: {$employer['email']}
                    </p>
                </div>
                
                <p>Please take immediate action to address this issue.</p>
                
                <p style='margin-top: 20px;'>Best regards,<br>FlexiHire Admin Team</p>
            </div>
            
            <div style='text-align: center; margin-top: 20px; color: #666; font-size: 12px;'>
                <p>This is an automated message from FlexiHire. Please do not reply to this email.</p>
            </div>
        </div>";
        
        $mail->Body = $emailBody;
        $mail->AltBody = strip_tags(str_replace(['<br>', '</p>'], ["\n", "\n\n"], $emailBody));

        $mail->send();
        header('Location: reports.php?success=email_sent');
        exit();
    } catch (Exception $e) {
        header('Location: reports.php?error=email_failed&msg=' . urlencode($mail->ErrorInfo));
        exit();
    }
} else {
    header('Location: reports.php?error=employer_not_found');
    exit();
}
?>

<?php
session_start();
require_once '../database/connectdatabase.php';
require '../vendor/autoload.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login/loginvalidation.php');
    exit();
}

// Get user_id from URL
$user_id = filter_input(INPUT_GET, 'user_id', FILTER_VALIDATE_INT);

try {
    // Update login status to inactive
    $login_sql = "UPDATE tbl_login SET status = 'inactive' WHERE user_id = ?";
    $login_stmt = mysqli_prepare($conn, $login_sql);
    mysqli_stmt_bind_param($login_stmt, "i", $user_id);
    mysqli_stmt_execute($login_stmt);

    // Get user email for notification
    $email_sql = "SELECT l.email, u.first_name, u.last_name 
                  FROM tbl_user u 
                  JOIN tbl_login l ON u.user_id = l.user_id 
                  WHERE u.user_id = ?";
    $email_stmt = mysqli_prepare($conn, $email_sql);
    mysqli_stmt_bind_param($email_stmt, "i", $user_id);
    mysqli_stmt_execute($email_stmt);
    $result = mysqli_stmt_get_result($email_stmt);
    $user = mysqli_fetch_assoc($result);

    if ($user) {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);

        // Email server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'flexihire369@gmail.com';
        $mail->Password = 'xtik ztxp zaji bszk';
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Recipients
        $mail->setFrom('flexihire369@gmail.com', 'FlexiHire');
        $mail->addAddress($user['email']);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Account Deactivation Notice - FlexiHire';
        $mail->Body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <div style='background-color: #f8f9fa; padding: 20px; text-align: center; border-radius: 10px 10px 0 0;'>
                    <h2 style='color: #dc3545; margin: 0;'>Account Deactivation Notice</h2>
                </div>
                
                <div style='background-color: white; padding: 20px; border-radius: 0 0 10px 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);'>
                    <p>Dear <strong>{$user['first_name']} {$user['last_name']}</strong>,</p>
                    
                    <p>We regret to inform you that your FlexiHire account has been deactivated due to violation of our terms of service.</p>
                    
                    <p>If you believe this was done in error or would like to appeal this decision, please contact our support team at flexihire369@gmail.com.</p>
                    
                    <p style='margin-top: 20px;'>Best regards,<br>FlexiHire Admin Team</p>
                </div>
                
                <div style='text-align: center; margin-top: 20px; color: #666; font-size: 12px;'>
                    <p>This is an automated message from FlexiHire. Please do not reply to this email.</p>
                </div>
            </div>";

        $mail->send();
    }

    $_SESSION['success'] = "User account has been deactivated successfully";
} catch (Exception $e) {
    $_SESSION['error'] = "Error deactivating user account";
    error_log($e->getMessage());
}

header('Location: manage_users.php');
exit();
?>
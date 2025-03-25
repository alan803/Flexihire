<?php
session_start();
require_once '../database/connectdatabase.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login/loginvalidation.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: reports.php');
    exit();
}

// Get employer_id and reason from POST
$employer_id = filter_input(INPUT_POST, 'employer_id', FILTER_VALIDATE_INT);
$reason = filter_input(INPUT_POST, 'reason', FILTER_SANITIZE_STRING);

if (!$employer_id || empty($reason)) {
    $_SESSION['error'] = "Invalid input data";
    header('Location: reports.php');
    exit();
}

// Verify employer exists
$check_sql = "SELECT employer_id FROM tbl_employer WHERE employer_id = ?";
$check_stmt = mysqli_prepare($conn, $check_sql);
mysqli_stmt_bind_param($check_stmt, "i", $employer_id);
mysqli_stmt_execute($check_stmt);
$check_result = mysqli_stmt_get_result($check_stmt);

if (mysqli_num_rows($check_result) === 0) {
    $_SESSION['error'] = "Employer not found";
    header('Location: reports.php');
    exit();
}

try {
    // Update login status to inactive
    $login_sql = "UPDATE tbl_login SET status = 'inactive' WHERE employer_id = ?";
    $login_stmt = mysqli_prepare($conn, $login_sql);
    mysqli_stmt_bind_param($login_stmt, "i", $employer_id);
    
    if (!mysqli_stmt_execute($login_stmt)) {
        throw new Exception("Failed to update login status");
    }

    // Get employer email for notification
    $email_sql = "SELECT l.email, e.company_name 
                  FROM tbl_employer e 
                  JOIN tbl_login l ON e.employer_id = l.employer_id 
                  WHERE e.employer_id = ?";
    $email_stmt = mysqli_prepare($conn, $email_sql);
    mysqli_stmt_bind_param($email_stmt, "i", $employer_id);
    mysqli_stmt_execute($email_stmt);
    $result = mysqli_stmt_get_result($email_stmt);
    $employer = mysqli_fetch_assoc($result);

    if ($employer) {
        // Send email notification
        require '../vendor/autoload.php';
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);

        try {
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
            $mail->addAddress($employer['email']);

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Account Deactivation Notice - FlexiHire';
            $mail->Body = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                    <div style='background-color: #f8f9fa; padding: 20px; text-align: center; border-radius: 10px 10px 0 0;'>
                        <h2 style='color: #dc3545; margin: 0;'>Account Deactivation Notice</h2>
                    </div>
                    
                    <div style='background-color: white; padding: 20px; border-radius: 0 0 10px 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);'>
                        <p>Dear <strong>{$employer['company_name']}</strong>,</p>
                        
                        <p>We regret to inform you that your FlexiHire employer account has been deactivated.</p>
                        
                        <div style='margin: 20px 0; padding: 15px; background-color: #f8f9fa; border-radius: 5px;'>
                            <p style='margin: 0; color: #666;'>
                                <strong>Reason for Deactivation:</strong><br>
                                {$reason}
                            </p>
                        </div>
                        
                        <p>If you believe this was done in error or would like to appeal this decision, please contact our support team at flexihire369@gmail.com.</p>
                        
                        <p style='margin-top: 20px;'>Best regards,<br>FlexiHire Admin Team</p>
                    </div>
                    
                    <div style='text-align: center; margin-top: 20px; color: #666; font-size: 12px;'>
                        <p>This is an automated message from FlexiHire. Please do not reply to this email.</p>
                    </div>
                </div>";

            $mail->AltBody = "Account Deactivation Notice\n\n" .
                            "Dear {$employer['company_name']},\n\n" .
                            "We regret to inform you that your FlexiHire employer account has been deactivated.\n\n" .
                            "Reason for Deactivation:\n" .
                            "{$reason}\n\n" .
                            "If you believe this was done in error or would like to appeal this decision, please contact our support team at flexihire369@gmail.com.\n\n" .
                            "Best regards,\nFlexiHire Admin Team";

            $mail->send();
        } catch (Exception $e) {
            // Log email error but don't stop the deactivation process
            error_log("Email sending failed: " . $mail->ErrorInfo);
        }
    }

    $_SESSION['success'] = "Employer account has been deactivated successfully";
} catch (Exception $e) {
    error_log("Deactivation error: " . $e->getMessage());
    $_SESSION['error'] = "Error deactivating employer account: " . $e->getMessage();
}

header('Location: reports.php');
exit();
?>
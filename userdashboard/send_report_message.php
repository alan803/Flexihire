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

// Get IDs from URL
$report_id = filter_input(INPUT_GET, 'report_id', FILTER_VALIDATE_INT);
$job_id = filter_input(INPUT_GET, 'job_id', FILTER_VALIDATE_INT);
$employer_id = filter_input(INPUT_GET, 'employer_id', FILTER_VALIDATE_INT);

if (!$report_id || !$job_id || !$employer_id) {
    header('Location: reports.php?error=invalid_parameters');
    exit();
}

// Get job, employer and report details
$sql = "SELECT j.job_title, j.employer_id, 
               e.company_name, 
               l.email,
               r.reason, r.created_at
        FROM tbl_jobs j
        JOIN tbl_employer e ON j.employer_id = e.employer_id
        JOIN tbl_login l ON e.employer_id = l.employer_id
        JOIN tbl_reports r ON r.reported_job_id = j.job_id
        WHERE r.report_id = ? AND j.job_id = ? AND e.employer_id = ?";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "iii", $report_id, $job_id, $employer_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$details = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if ($details) {
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
        $mail->setFrom('flexihire369@gmail.com', 'FlexiHire Admin');
        $mail->addAddress($details['email']);

        // Content
        $mail->isHTML(true);
        $mail->Subject = "Job Posting Report Notice - FlexiHire";
        
        // HTML Email Body
        $emailBody = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <div style='background-color: #f8f9fa; padding: 20px; text-align: center; border-radius: 10px 10px 0 0;'>
                <h2 style='color: #dc3545; margin: 0;'>Job Posting Report Notice</h2>
            </div>
            
            <div style='background-color: white; padding: 20px; border-radius: 0 0 10px 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);'>
                <p>Dear <strong>{$details['company_name']}</strong>,</p>
                
                <p>We are writing to inform you that your job posting has been reported for potential violation of our terms and conditions.</p>
                
                <div style='margin: 20px 0; padding: 15px; background-color: #f8f9fa; border-radius: 5px;'>
                    <p style='margin: 0; color: #666;'>
                        <strong>Job Details:</strong><br>
                        Job Title: {$details['job_title']}<br>
                        Report Reason: {$details['reason']}<br>
                        Report Date: " . date('F j, Y', strtotime($details['created_at'])) . "
                    </p>
                </div>
                
                <p>Please review your job posting and make any necessary adjustments to ensure compliance with our platform's guidelines. If you believe this report was made in error, please contact our support team.</p>
                
                <p>Failure to address these concerns may result in the removal of your job posting or other account restrictions.</p>
                
                <p style='margin-top: 20px;'>Best regards,<br>FlexiHire Admin Team</p>
            </div>
            
            <div style='text-align: center; margin-top: 20px; color: #666; font-size: 12px;'>
                <p>This is an automated message from FlexiHire. Please do not reply to this email.</p>
            </div>
        </div>";
        
        $mail->Body = $emailBody;
        $mail->AltBody = strip_tags(str_replace(['<br>', '</p>'], ["\n", "\n\n"], $emailBody));

        if ($mail->send()) {
            $_SESSION['success'] = "Email has been sent successfully";
        } else {
            $_SESSION['error'] = "Failed to send email";
        }
        header('Location: reports.php');
        exit();
    } catch (Exception $e) {
        header('Location: reports.php?error=email_failed&msg=' . urlencode($mail->ErrorInfo));
        exit();
    }
} else {
    header('Location: reports.php?error=details_not_found');
    exit();
}
?>

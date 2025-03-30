<?php
    session_start();
    include '../database/connectdatabase.php';
    require '../vendor/autoload.php'; // For PHPMailer

    // Check if admin is logged in
    if (!isset($_SESSION['admin_id'])) 
    {
        header("Location: ../login/loginvalidation.php");
        exit();
    }

    // Check if job_id and reason are provided
    if (!isset($_POST['job_id']) || !isset($_POST['deactivate_reason'])) 
    {
        $_SESSION['message'] = "Job ID or reason not provided";
        $_SESSION['message_type'] = "error";
        header("Location: manage_jobs.php");
        exit();
    }

    $job_id = filter_input(INPUT_POST, 'job_id', FILTER_VALIDATE_INT);
    $reason = filter_input(INPUT_POST, 'deactivate_reason', FILTER_SANITIZE_STRING);

    try {
        // First, get the job and employer details
        $query = "SELECT j.*, e.company_name, l.email 
                  FROM tbl_jobs j
                  JOIN tbl_employer e ON j.employer_id = e.employer_id
                  JOIN tbl_login l ON e.employer_id = l.employer_id
                  WHERE j.job_id = ?";
        
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $job_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $job_details = mysqli_fetch_assoc($result);

        if (!$job_details) {
            throw new Exception("Job not found");
        }

        // Update job status to 'deleted'
        $update_query = "UPDATE tbl_jobs SET is_deleted = 1 WHERE job_id = ?";
        $update_stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($update_stmt, "i", $job_id);

        if (!mysqli_stmt_execute($update_stmt)) {
            throw new Exception("Error deactivating job: " . mysqli_error($conn));
        }

        // Send email to employer
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
            $mail->setFrom('flexihire369@gmail.com', 'FlexiHire Admin');
            $mail->addAddress($job_details['email']);

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Job Listing Deactivated - FlexiHire';
            $mail->Body = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                    <div style='background-color: #f8f9fa; padding: 20px; text-align: center; border-radius: 10px 10px 0 0;'>
                        <h2 style='color: #dc3545; margin: 0;'>Job Listing Deactivated</h2>
                    </div>
                    
                    <div style='background-color: white; padding: 20px; border-radius: 0 0 10px 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);'>
                        <p>Dear <strong>{$job_details['company_name']}</strong>,</p>
                        
                        <p>Your job listing for the position of <strong>{$job_details['job_title']}</strong> has been deactivated by the FlexiHire admin team.</p>
                        
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

            $mail->AltBody = "Job Listing Deactivated\n\n" .
                            "Dear {$job_details['company_name']},\n\n" .
                            "Your job listing for the position of {$job_details['job_title']} has been deactivated by the FlexiHire admin team.\n\n" .
                            "Reason for Deactivation:\n" .
                            "{$reason}\n\n" .
                            "If you believe this was done in error or would like to appeal this decision, please contact our support team at flexihire369@gmail.com.\n\n" .
                            "Best regards,\nFlexiHire Admin Team";

            $mail->send();
            $_SESSION['message'] = "Job has been deactivated and employer has been notified";
            $_SESSION['message_type'] = "success";
        } catch (Exception $e) {
            // Log email error but don't stop the deactivation process
            error_log("Email sending failed: " . $mail->ErrorInfo);
            $_SESSION['message'] = "Job deactivated but failed to send notification email";
            $_SESSION['message_type'] = "warning";
        }

    } catch (Exception $e) {
        $_SESSION['message'] = "Error: " . $e->getMessage();
        $_SESSION['message_type'] = "error";
    }

    mysqli_close($conn);
    header("Location: manage_jobs.php");
    exit();
?>

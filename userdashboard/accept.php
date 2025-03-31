<?php
    session_start();
    include '../database/connectdatabase.php';
    require '../vendor/autoload.php'; // For PHPMailer
    $dbname = "project";
    mysqli_select_db($conn, $dbname);

    if (!isset($_SESSION['employer_id'])) {
        header("Location: ../login/loginvalidation.php");
        exit();
    }

    if (isset($_GET['application_id']) && isset($_GET['status'])) {
        $application_id = $_GET['application_id'];
        $status = $_GET['status'];

        // Validate status
        if (!in_array($status, ['accepted', 'rejected'])) {
            header("Location: myjoblist.php");
            exit();
        }

        // Get job_id and check vacancy status before updating
        $sql = "SELECT a.job_id, a.user_id, j.job_title, e.company_name, e.phone_number, u.first_name, u.last_name, l.email, l2.email as employer_email 
                FROM tbl_applications a
                JOIN tbl_jobs j ON a.job_id = j.job_id
                JOIN tbl_employer e ON j.employer_id = e.employer_id
                JOIN tbl_user u ON a.user_id = u.user_id
                JOIN tbl_login l ON u.user_id = l.user_id
                JOIN tbl_login l2 ON e.employer_id = l2.employer_id
                WHERE a.id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $application_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($row = mysqli_fetch_assoc($result)) {
            $job_id = $row['job_id'];
            $user_email = $row['email'];
            $user_name = $row['first_name'] . ' ' . $row['last_name'];
            $job_title = $row['job_title'];
            $company_name = $row['company_name'];
            $employer_phone = $row['phone_number'];
            $employer_email = $row['employer_email'];

            // Check vacancy limit
            $vacancy_sql = "SELECT 
                (SELECT vacancy FROM tbl_jobs WHERE job_id = ?) as total_vacancy,
                (SELECT COUNT(*) FROM tbl_applications WHERE job_id = ? AND status = 'accepted') as filled_vacancy";
            $stmt = mysqli_prepare($conn, $vacancy_sql);
            mysqli_stmt_bind_param($stmt, "ii", $job_id, $job_id);
            mysqli_stmt_execute($stmt);
            $vacancy_result = mysqli_stmt_get_result($stmt);
            $vacancy_data = mysqli_fetch_assoc($vacancy_result);

            if ($status === 'accepted' && 
                $vacancy_data['filled_vacancy'] >= $vacancy_data['total_vacancy']) {
                header("Location: applicants.php?job_id=" . $job_id . "&error=vacancy_full");
                exit();
            }

            // Update the application status
            $update_sql = "UPDATE tbl_applications SET status = ? WHERE id = ?";
            $stmt = mysqli_prepare($conn, $update_sql);
            mysqli_stmt_bind_param($stmt, "si", $status, $application_id);
            
            if (mysqli_stmt_execute($stmt)) {
                // Send email for both accepted and rejected status
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
                    $mail->setFrom($employer_email, $company_name);
                    $mail->addAddress($user_email);

                    // Content
                    $mail->isHTML(true);
                    
                    if ($status === 'accepted') {
                        // Insert into tbl_selected
                        $insert_sql = "INSERT INTO tbl_selected (application_id, job_id, user_id) 
                                    VALUES (?, ?, ?)";
                        $stmt = mysqli_prepare($conn, $insert_sql);
                        mysqli_stmt_bind_param($stmt, "iii", $application_id, $job_id, $row['user_id']);
                        mysqli_stmt_execute($stmt);

                        $mail->Subject = "Congratulations! You've Been Selected for $job_title";
                        $mail->Body = "
                            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                                <div style='background-color: #f8f9fa; padding: 20px; text-align: center; border-radius: 10px 10px 0 0;'>
                                    <h2 style='color: #4a90e2; margin: 0;'>Congratulations!</h2>
                                </div>
                                
                                <div style='background-color: white; padding: 20px; border-radius: 0 0 10px 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);'>
                                    <p>Dear <strong>$user_name</strong>,</p>
                                    
                                    <p>We are pleased to inform you that you have been selected for the position of <strong>$job_title</strong> at <strong>$company_name</strong>.</p>
                                    
                                    <div style='margin: 20px 0; padding: 15px; background-color: #f8f9fa; border-radius: 5px;'>
                                        <p style='margin: 0; color: #666;'>
                                            <strong>Next Steps:</strong><br>
                                            Please contact us at: <strong>$employer_phone</strong> for further details.
                                        </p>
                                    </div>
                                    
                                    <p style='margin-top: 20px;'>Best regards,<br>$company_name</p>
                                </div>
                            </div>";
                    } else {
                        // Rejection email
                        $mail->Subject = "Update Regarding Your Application for $job_title";
                        $mail->Body = "
                            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                                <div style='background-color: #f8f9fa; padding: 20px; text-align: center; border-radius: 10px 10px 0 0;'>
                                    <h2 style='color: #4a90e2; margin: 0;'>Application Update</h2>
                                </div>
                                
                                <div style='background-color: white; padding: 20px; border-radius: 0 0 10px 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);'>
                                    <p>Dear <strong>$user_name</strong>,</p>
                                    
                                    <p>Thank you for your interest in the <strong>$job_title</strong> position at <strong>$company_name</strong>.</p>
                                    
                                    <p>After careful consideration of your application, we regret to inform you that we have decided to move forward with other candidates whose qualifications more closely match our current needs.</p>
                                    
                                    <p>We encourage you to apply for future positions that match your skills and experience.</p>
                                    
                                    <div style='margin: 20px 0; padding: 15px; background-color: #f8f9fa; border-radius: 5px;'>
                                        <p style='margin: 0; color: #666;'>
                                            We wish you the best in your job search and future professional endeavors.
                                        </p>
                                    </div>
                                    
                                    <p style='margin-top: 20px;'>Best regards,<br>$company_name</p>
                                </div>
                            </div>";
                    }

                    $mail->send();
                } catch (Exception $e) {
                    error_log("Email sending failed: " . $mail->ErrorInfo);
                }
            }

            header("Location: applicants.php?job_id=" . $job_id . "&success=status_updated");
        } else {
            header("Location: myjoblist.php");
        }
        exit();
    } else {
        header("Location: myjoblist.php");
        exit();
    }
?>
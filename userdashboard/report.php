<?php
session_start();
include '../database/connectdatabase.php';

if (isset($_GET['job_id'], $_GET['user_id'], $_GET['reason'])) {
    $job_id = (int)$_GET['job_id'];
    $reporter_id = (int)$_GET['user_id'];  // This is the user reporting
    $reason = trim($_GET['reason']);

    if (empty($reason)) 
    {
        $_SESSION['message'] = 'Report reason cannot be empty';
        $_SESSION['message_type'] = 'error';
    } 
    else 
    {
        // Check if user has already reported this job
        $check_sql = "SELECT report_id FROM tbl_reports 
                     WHERE reporter_id = ? AND reported_job_id = ?";
        $check_stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($check_stmt, "ii", $reporter_id, $job_id);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);

        if (mysqli_num_rows($check_result) > 0) {
            $_SESSION['message'] = 'You have already reported this job';
            $_SESSION['message_type'] = 'error';
        } else {
            // First get the employer_id from the job
            $job_sql = "SELECT employer_id FROM tbl_jobs WHERE job_id = ?";
            $job_stmt = mysqli_prepare($conn, $job_sql);
            mysqli_stmt_bind_param($job_stmt, "i", $job_id);
            mysqli_stmt_execute($job_stmt);
            $result = mysqli_stmt_get_result($job_stmt);
            $job_data = mysqli_fetch_assoc($result);
            $reported_employer_id = $job_data['employer_id'];

            // Now insert into tbl_reports with the correct columns
            $sql = "INSERT INTO tbl_reports (reporter_id, reported_job_id, reported_employer_id, reported_user_id, status, reason, created_at) 
                    VALUES (?, ?, ?, NULL, 'pending', ?, NOW())";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "iiis", $reporter_id, $job_id, $reported_employer_id, $reason);

            if (mysqli_stmt_execute($stmt)) 
            {
                $_SESSION['message'] = 'Report submitted successfully';
                $_SESSION['message_type'] = 'success';
            } 
            else 
            {
                $_SESSION['message'] = 'Failed to submit report';
                $_SESSION['message_type'] = 'error';
            }
        }
    }
} 
else 
{
    $_SESSION['message'] = 'Missing required parameters';
    $_SESSION['message_type'] = 'error';
}

mysqli_close($conn);
header("Location: jobdetails.php?job_id=" . $job_id);
exit();

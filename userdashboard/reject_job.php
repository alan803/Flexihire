<?php
    session_start();
    $dbname="project";
    include '../database/connectdatabase.php';
    mysqli_select_db($conn, $dbname);

    // Check if job_id is provided
    if (!isset($_GET['job_id'])) 
    {
        $_SESSION['message'] = "No job ID provided.";
        $_SESSION['message_type'] = "error";
        header("Location: manage_jobs.php");
        exit();
    }

    $job_id = $_GET['job_id'];

    // First check if the job exists and is pending
    $check_sql = "SELECT status FROM tbl_jobs WHERE job_id = ?";
    $check_stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($check_stmt, "i", $job_id);
    mysqli_stmt_execute($check_stmt);
    $result = mysqli_stmt_get_result($check_stmt);
    
    if (mysqli_num_rows($result) == 0) 
    {
        $_SESSION['message'] = "Job not found.";
        $_SESSION['message_type'] = "error";
        header("Location: manage_jobs.php");
        exit();
    }

    $job = mysqli_fetch_assoc($result);
    if ($job['status'] !== 'pending') 
    {
        $_SESSION['message'] = "This job is not pending approval.";
        $_SESSION['message_type'] = "error";
        header("Location: manage_jobs.php");
        exit();
    }

    // Update the job status to rejected
    $sql = "UPDATE tbl_jobs SET status = 'rejected' WHERE job_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $job_id);
    
    if(mysqli_stmt_execute($stmt)) 
    {
        $_SESSION['message'] = "Job has been rejected.";
        $_SESSION['message_type'] = "success";
    } 
    else 
    {
        $_SESSION['message'] = "Error rejecting the job. Please try again.";
        $_SESSION['message_type'] = "error";
    }
    
    mysqli_stmt_close($stmt);
    mysqli_stmt_close($check_stmt);
    
    // Redirect back to manage_jobs.php
    header("Location: manage_jobs.php");
    exit();
?>
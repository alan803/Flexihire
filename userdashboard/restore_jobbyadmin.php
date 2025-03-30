<?php
    session_start();
    include '../database/connectdatabase.php';
    $dbname = "project";
    mysqli_select_db($conn, $dbname);

    // Debug logging
    error_log("Restore job process started");
    error_log("Session data: " . print_r($_SESSION, true));
    error_log("GET data: " . print_r($_GET, true));

    // Check if admin is logged in
    if (!isset($_SESSION['admin_id'])) 
    {
        error_log("Admin not logged in - redirecting to login");
        header("Location: ../login/loginvalidation.php");
        exit();
    }

    // Check if job_id is provided
    if (!isset($_GET['job_id'])) 
    {
        error_log("Job ID not provided");
        $_SESSION['error'] = "Job ID not provided";
        header("Location: manage_jobs.php");
        exit();
    }

    $job_id = $_GET['job_id'];
    error_log("Processing job ID: " . $job_id);

    // First check if the job exists and is deactivated
    $check_sql = "SELECT job_id, is_deleted, status FROM tbl_jobs WHERE job_id = ?";
    $check_stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($check_stmt, "i", $job_id);
    mysqli_stmt_execute($check_stmt);
    $result = mysqli_stmt_get_result($check_stmt);
    
    if (mysqli_num_rows($result) == 0) 
    {
        error_log("Job not found with ID: " . $job_id);
        $_SESSION['error'] = "Job not found.";
        header("Location: manage_jobs.php");
        exit();
    }

    $job = mysqli_fetch_assoc($result);
    error_log("Current job status - is_deleted: " . $job['is_deleted'] . ", status: " . $job['status']);
    
    // Check if job is actually deactivated
    if ($job['is_deleted'] != 1) 
    {
        error_log("Job is not deactivated - current is_deleted value: " . $job['is_deleted']);
        $_SESSION['error'] = "This job is already activated.";
        header("Location: manage_jobs.php");
        exit();
    }

    // Update job status to activate it
    $query = "UPDATE tbl_jobs SET is_deleted = 0 WHERE job_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $job_id);

    if (mysqli_stmt_execute($stmt)) {
        error_log("Job successfully activated - Job ID: " . $job_id);
        $_SESSION['message'] = "Job has been activated successfully";
        $_SESSION['message_type'] = "success";
    } else {
        error_log("Error activating job - Job ID: " . $job_id . " - Error: " . mysqli_error($conn));
        $_SESSION['message'] = "Error activating job: " . mysqli_error($conn);
        $_SESSION['message_type'] = "error";
    }

    mysqli_stmt_close($stmt);
    mysqli_stmt_close($check_stmt);
    mysqli_close($conn);

    header("Location: manage_jobs.php");
    exit();
?>

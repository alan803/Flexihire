<?php
    session_start();
    include '../database/connectdatabase.php';

    // Check if admin is logged in
    if (!isset($_SESSION['admin_id'])) 
    {
        header("Location: ../login/loginvalidation.php");
        exit();
    }

    // Check if job_id is provided
    if (!isset($_GET['job_id'])) 
    {
        $_SESSION['error'] = "Job ID not provided";
        header("Location: manage_jobs.php");
        exit();
    }

    $job_id = mysqli_real_escape_string($conn, $_GET['job_id']);

    // Update job status to 'deleted' instead of actually deleting
    $query = "UPDATE tbl_jobs SET is_deleted = 1 WHERE job_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $job_id);

    if (mysqli_stmt_execute($stmt)) 
    {
        $_SESSION['success'] = "Job has been deleted successfully";
    } 
    else 
    {
        $_SESSION['error'] = "Error deleting job: " . mysqli_error($conn);
    }

    mysqli_stmt_close($stmt);
    mysqli_close($conn);

    header("Location: manage_jobs.php");
    exit();
?>

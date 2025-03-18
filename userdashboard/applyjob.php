<?php
    session_start();
    include '../database/connectdatabase.php';
    $dbname="project";
    mysqli_select_db($conn,$dbname);

    if (!isset($_SESSION['user_id']) || !isset($_GET['job_id'])) 
    {
        $_SESSION['message'] = "Please login to apply for jobs.";
        $_SESSION['message_type'] = "error";
        header('location: ../login/loginvalidation.php');
        exit();
    }

    $user_id = $_SESSION['user_id'];
    $job_id = $_GET['job_id'];
    
    // Checking if the user has already applied for the job
    $check = "SELECT * FROM tbl_applications WHERE user_id='$user_id' AND job_id='$job_id'";
    $run = mysqli_query($conn, $check);
    $num = mysqli_num_rows($run);
    if ($num > 0) {
        mysqli_close($conn);
        $_SESSION['message'] = "You have already applied for this job.";
        $_SESSION['message_type'] = "error";
        header('Location: userdashboard.php?error=already_applied');
        exit();
    } else {
        $sql = "INSERT INTO tbl_applications (user_id, job_id) VALUES ('$user_id', '$job_id')";
        $run = mysqli_query($conn, $sql);
        mysqli_close($conn);
        
        if ($run) {
            $_SESSION['message'] = "You have successfully applied for this job!";
            $_SESSION['message_type'] = "success";
            header('Location: userdashboard.php');
            exit();
        } else {
            $_SESSION['message'] = "Failed to apply for the job. Please try again.";
            $_SESSION['message_type'] = "error";
            header('Location: userdashboard.php');
            exit();
        }
    }
?>
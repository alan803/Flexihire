<?php
    session_start();
    include '../database/connectdatabase.php';
    $dbname="project";
    mysqli_select_db($conn,$dbname);

    if (!isset($_SESSION['user_id']) || !isset($_GET['job_id'])) 
    {
        mysqli_close($conn);
        $_SESSION['message'] = "Please login to apply for jobs.";
        $_SESSION['message_type'] = "error";
        header('location: ../login/loginvalidation.php');
        exit();
    }

    $user_id = $_SESSION['user_id'];
    $job_id = $_GET['job_id'];
    
    // Checking if the user has already applied for the job
    $check = "SELECT * FROM tbl_applications WHERE user_id = ? AND job_id = ?";
    $check_stmt = mysqli_prepare($conn, $check);
    mysqli_stmt_bind_param($check_stmt, "ii", $user_id, $job_id);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    
    if (mysqli_num_rows($check_result) > 0) 
    {
        mysqli_close($conn);
        $_SESSION['message'] = "You have already applied for this job.";
        $_SESSION['message_type'] = "error";
        header('Location: userdashboard.php');
        exit();
    } 
    else 
    {
        // Check if any certificates or documents are required
        $certificate_check = "SELECT license_required, badge_required FROM tbl_jobs WHERE job_id = ?";
        $cert_stmt = mysqli_prepare($conn, $certificate_check);
        mysqli_stmt_bind_param($cert_stmt, "i", $job_id);
        mysqli_stmt_execute($cert_stmt);
        $cert_result = mysqli_stmt_get_result($cert_stmt);
        $job = mysqli_fetch_assoc($cert_result);

        if ($job) 
        {
            $license_required = $job['license_required'];
            $badge_required = $job['badge_required'];

            // Check if license or badge is required
            if ($license_required !== NULL && $license_required !== 'not_required' && $license_required !== '') 
            {
                $_SESSION['message'] = "This job requires a " . $license_required . " license. Please upload it in your profile.";
                $_SESSION['message_type'] = "error";
                header('Location: userdashboard.php');
                exit();
            }
            
            if ($badge_required !== NULL && $badge_required === 'yes') 
            {
                $_SESSION['message'] = "This job requires a badge. Please upload it in your profile.";
                $_SESSION['message_type'] = "error";
                header('Location: userdashboard.php');
                exit();
            }
        }

        // If no certificates required or all certificates are valid, proceed with application
        $sql = "INSERT INTO tbl_applications (user_id, job_id, status) VALUES (?, ?, 'Pending')";
        $apply_stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($apply_stmt, "ii", $user_id, $job_id);
        
        if (mysqli_stmt_execute($apply_stmt)) 
        {
            $_SESSION['message'] = "You have successfully applied for this job!";
            $_SESSION['message_type'] = "success";
            header('Location: userdashboard.php');
            exit();
        } 
        else 
        {
            $_SESSION['message'] = "Failed to apply for the job. Please try again.";
            $_SESSION['message_type'] = "error";
            mysqli_close($conn);
            header('Location: userdashboard.php');
            exit();
        }
    }
?>
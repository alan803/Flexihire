<?php
    session_start();
    include '../database/connectdatabase.php';
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

        // First, update the application status
        $update_sql = "UPDATE tbl_applications SET status = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($stmt, "si", $status, $application_id);
        
        if (mysqli_stmt_execute($stmt)) {
            // If status is accepted, add to tbl_selected
            if ($status === 'accepted') {
                // Get the application details
                $sql = "SELECT user_id, job_id FROM tbl_applications WHERE id = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "i", $application_id);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                
                if ($row = mysqli_fetch_assoc($result)) {
                    $user_id = $row['user_id'];
                    $job_id = $row['job_id'];

                    // Insert into tbl_selected
                    $insert_sql = "INSERT INTO tbl_selected (application_id, job_id, user_id) 
                                 VALUES (?, ?, ?)";
                    $stmt = mysqli_prepare($conn, $insert_sql);
                    mysqli_stmt_bind_param($stmt, "iii", $application_id, $job_id, $user_id);
                    mysqli_stmt_execute($stmt);
                }
            }
        }

        // Redirect back to applicants page with the job_id
        $get_job_sql = "SELECT job_id FROM tbl_applications WHERE id = ?";
        $stmt = mysqli_prepare($conn, $get_job_sql);
        mysqli_stmt_bind_param($stmt, "i", $application_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        
        if ($row) {
            header("Location: applicants.php?job_id=" . $row['job_id']);
        } else {
            header("Location: myjoblist.php");
        }
        exit();
    } else {
        header("Location: myjoblist.php");
        exit();
    }
?>
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

        // Get job_id and check vacancy status before updating
        $sql = "SELECT job_id FROM tbl_applications WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $application_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($row = mysqli_fetch_assoc($result)) {
            $job_id = $row['job_id'];

            // Check current number of accepted applications and vacancy limit
            $vacancy_sql = "SELECT 
                (SELECT vacancy FROM tbl_jobs WHERE job_id = ?) as total_vacancy,
                (SELECT COUNT(*) FROM tbl_applications WHERE job_id = ? AND status = 'accepted') as filled_vacancy";
            $stmt = mysqli_prepare($conn, $vacancy_sql);
            mysqli_stmt_bind_param($stmt, "ii", $job_id, $job_id);
            mysqli_stmt_execute($stmt);
            $vacancy_result = mysqli_stmt_get_result($stmt);
            $vacancy_data = mysqli_fetch_assoc($vacancy_result);

            // If status is 'accepted', check if vacancy limit would be exceeded
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
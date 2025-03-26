<?php
    session_start();

    // Check if employer is logged in
    if (!isset($_SESSION['employer_id'])) {
        $_SESSION['message'] = "Please login to continue.";
        $_SESSION['message_type'] = "error";
        header("Location: interviews.php");
        exit();
    }

    $dbname="project";
    include '../database/connectdatabase.php';
    mysqli_select_db($conn, $dbname);

    if (isset($_GET['appointment_id'])) {
        $appointment_id = $_GET['appointment_id'];
        
        // Get job_id and user_id from appointments table
        $get_ids = "SELECT job_id, user_id FROM tbl_appointments WHERE appointment_id = ?";
        $stmt = mysqli_prepare($conn, $get_ids);
        mysqli_stmt_bind_param($stmt, "i", $appointment_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        
        if ($row) {
            $job_id = $row['job_id'];
            $user_id = $row['user_id'];
            
            // Update tbl_appointments
            $update_appointment = "UPDATE tbl_appointments SET status = 'accepted' WHERE appointment_id = ?";
            $stmt1 = mysqli_prepare($conn, $update_appointment);
            mysqli_stmt_bind_param($stmt1, "i", $appointment_id);
            
            // Update tbl_applications
            $update_application = "UPDATE tbl_applications SET status = 'accepted' WHERE job_id = ? AND user_id = ?";
            $stmt2 = mysqli_prepare($conn, $update_application);
            mysqli_stmt_bind_param($stmt2, "ii", $job_id, $user_id);
            
            // Execute both updates
            if (mysqli_stmt_execute($stmt1) && mysqli_stmt_execute($stmt2)) {
                $_SESSION['message'] = "Interview completed and application accepted successfully!";
                $_SESSION['message_type'] = "success";
            } else {
                $_SESSION['message'] = "Error updating status. Please try again.";
                $_SESSION['message_type'] = "error";
            }
            
            mysqli_stmt_close($stmt1);
            mysqli_stmt_close($stmt2);
        } else {
            $_SESSION['message'] = "Appointment not found.";
            $_SESSION['message_type'] = "error";
        }
        
        mysqli_stmt_close($stmt);
    } else {
        $_SESSION['message'] = "No appointment ID provided.";
        $_SESSION['message_type'] = "error";
    }

    // Redirect back to interviews.php
    header("Location: interviews.php");
    exit();
?>
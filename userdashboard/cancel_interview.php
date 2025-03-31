<?php
session_start();
include '../database/connectdatabase.php';
$dbname = "project";
mysqli_select_db($conn, $dbname);

if (!isset($_SESSION['employer_id'])) {
    header("Location: ../login/loginvalidation.php");
    exit();
}

$employer_id = $_SESSION['employer_id'];
$appointment_id = $_GET['appointment_id'] ?? null;

if (!$appointment_id) {
    header("Location: interviews.php");
    exit();
}

// First, get the appointment details to ensure it exists and belongs to this employer
$sql = "SELECT a.*, j.job_title, u.first_name, u.last_name, j.job_id, u.user_id 
        FROM tbl_appointments a
        JOIN tbl_jobs j ON a.job_id = j.job_id
        JOIN tbl_user u ON a.user_id = u.user_id
        WHERE a.appointment_id = ? AND a.employer_id = ? AND a.status = 'Pending'";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $appointment_id, $employer_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    header("Location: interviews.php?error=invalid_appointment");
    exit();
}

$appointment = mysqli_fetch_assoc($result);

// Start transaction
mysqli_begin_transaction($conn);

try {
    // 1. Update appointment status to rejected
    $update_sql = "UPDATE tbl_appointments SET status = 'Rejected' WHERE appointment_id = ?";
    $update_stmt = mysqli_prepare($conn, $update_sql);
    
    if (!$update_stmt) {
        throw new Exception("Prepare failed: " . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($update_stmt, "i", $appointment_id);
    
    if (!mysqli_stmt_execute($update_stmt)) {
        throw new Exception("Execute failed: " . mysqli_stmt_error($update_stmt));
    }

    // 2. Update application status back to pending
    $update_application_sql = "UPDATE tbl_applications 
                             SET status = 'Pending' 
                             WHERE user_id = ? AND job_id = ?";
    $update_app_stmt = mysqli_prepare($conn, $update_application_sql);
    
    if (!$update_app_stmt) {
        throw new Exception("Prepare failed: " . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($update_app_stmt, "ii", $appointment['user_id'], $appointment['job_id']);
    
    if (!mysqli_stmt_execute($update_app_stmt)) {
        throw new Exception("Execute failed: " . mysqli_stmt_error($update_app_stmt));
    }

    // If everything is successful, commit the transaction
    mysqli_commit($conn);
    
    header("Location: interviews.php?success=interview_rejected");
    exit();

} catch (Exception $e) {
    // If there's an error, rollback the transaction
    mysqli_rollback($conn);
    error_log("Reject Interview Error: " . $e->getMessage());
    header("Location: interviews.php?error=reject_failed");
    exit();
}
?>

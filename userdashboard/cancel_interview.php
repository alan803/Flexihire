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

// Verify the appointment belongs to this employer and is pending
$sql = "SELECT a.*, j.job_title, u.first_name, u.last_name 
        FROM tbl_appointments a
        JOIN tbl_jobs j ON a.job_id = j.job_id
        JOIN tbl_user u ON a.user_id = u.user_id
        WHERE a.appointment_id = ? AND a.employer_id = ? AND a.status = 'pending'";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $appointment_id, $employer_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    header("Location: interviews.php");
    exit();
}

$appointment = mysqli_fetch_assoc($result);

// Update appointment status to cancelled
$update_sql = "UPDATE tbl_appointments SET status = 'cancelled' WHERE appointment_id = ?";
$update_stmt = mysqli_prepare($conn, $update_sql);
mysqli_stmt_bind_param($update_stmt, "i", $appointment_id);

if (mysqli_stmt_execute($update_stmt)) {
    // Update application status back to pending
    $update_application_sql = "UPDATE tbl_applications 
                             SET status = 'pending' 
                             WHERE user_id = ? AND job_id = ?";
    $update_app_stmt = mysqli_prepare($conn, $update_application_sql);
    mysqli_stmt_bind_param($update_app_stmt, "ii", $appointment['user_id'], $appointment['job_id']);
    mysqli_stmt_execute($update_app_stmt);

    // Send email notification (optional)
    $to = $appointment['email'];
    $subject = "Interview Cancelled - " . $appointment['job_title'];
    $message = "Dear " . $appointment['first_name'] . " " . $appointment['last_name'] . ",\n\n";
    $message .= "Your interview for the position of " . $appointment['job_title'] . " has been cancelled.\n";
    $message .= "Date: " . date('F j, Y', strtotime($appointment['appointment_date'])) . "\n";
    $message .= "Time: " . date('g:i A', strtotime($appointment['appointment_time'])) . "\n\n";
    $message .= "Please check your dashboard for more information.\n\n";
    $message .= "Best regards,\n";
    $message .= $row['company_name'];

    mail($to, $subject, $message);

    // Redirect with success message
    echo "<script>
            alert('Interview cancelled successfully!');
            window.location.href='interviews.php';
          </script>";
    exit();
} else {
    // Redirect with error message
    echo "<script>
            alert('Error cancelling interview. Please try again.');
            window.location.href='interviews.php';
          </script>";
    exit();
}
?>

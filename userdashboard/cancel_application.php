<?php
session_start();
include '../database/connectdatabase.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['message'] = "Please login to continue";
    $_SESSION['message_type'] = "error";
    header('Location: ../login/loginvalidation.php');
    exit();
}

$user_id = $_SESSION['user_id'];

if (!isset($_GET['application_id']) || empty($_GET['application_id'])) {
    $_SESSION['message'] = "Unable to process request. Please try again.";
    $_SESSION['message_type'] = "error";
    header('Location: applied.php');
    exit();
}

$application_id = (int)$_GET['application_id'];

// Start a transaction
mysqli_begin_transaction($conn);

try {
    // First, check if there are certificates to delete
    $cert_check_sql = "SELECT id, file_path FROM tbl_certificates WHERE application_id = ? AND user_id = ?";
    $cert_check_stmt = mysqli_prepare($conn, $cert_check_sql);
    mysqli_stmt_bind_param($cert_check_stmt, "ii", $application_id, $user_id);
    mysqli_stmt_execute($cert_check_stmt);
    $cert_result = mysqli_stmt_get_result($cert_check_stmt);
    
    // Delete physical files if they exist
    while ($cert = mysqli_fetch_assoc($cert_result)) {
        if (file_exists($cert['file_path'])) {
            unlink($cert['file_path']);
        }
    }
    
    // Delete certificates from database
    $delete_certs_sql = "DELETE FROM tbl_certificates WHERE application_id = ? AND user_id = ?";
    $delete_certs_stmt = mysqli_prepare($conn, $delete_certs_sql);
    mysqli_stmt_bind_param($delete_certs_stmt, "ii", $application_id, $user_id);
    mysqli_stmt_execute($delete_certs_stmt);
    
    // Now delete the application
    $delete_app_sql = "DELETE FROM tbl_applications WHERE id = ? AND user_id = ?";
    $delete_app_stmt = mysqli_prepare($conn, $delete_app_sql);
    mysqli_stmt_bind_param($delete_app_stmt, "ii", $application_id, $user_id);
    mysqli_stmt_execute($delete_app_stmt);
    $affected_rows = mysqli_stmt_affected_rows($delete_app_stmt);
    
    // Commit the transaction
    mysqli_commit($conn);
    
    if ($affected_rows > 0) {
        $_SESSION['message'] = "Your job application has been successfully cancelled.";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Application not found or already cancelled.";
        $_SESSION['message_type'] = "error";
    }
    
} catch (Exception $e) {
    // Rollback the transaction in case of error
    mysqli_rollback($conn);
    $_SESSION['message'] = "Failed to cancel application. Please try again later.";
    $_SESSION['message_type'] = "error";
}

mysqli_close($conn);
header('Location: applied.php');
exit();
?>

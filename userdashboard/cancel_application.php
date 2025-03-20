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

// Start transaction
mysqli_begin_transaction($conn);

try {
    // First delete certificates
    $delete_certs_sql = "DELETE FROM tbl_certificates WHERE application_id = ? AND user_id = ?";
    $delete_certs_stmt = mysqli_prepare($conn, $delete_certs_sql);
    mysqli_stmt_bind_param($delete_certs_stmt, "ii", $application_id, $user_id);
    mysqli_stmt_execute($delete_certs_stmt);
    mysqli_stmt_close($delete_certs_stmt);

    // Then delete application
    $delete_app_sql = "DELETE FROM tbl_applications WHERE id = ? AND user_id = ?";
    $delete_app_stmt = mysqli_prepare($conn, $delete_app_sql);
    mysqli_stmt_bind_param($delete_app_stmt, "ii", $application_id, $user_id);
    mysqli_stmt_execute($delete_app_stmt);
    
    $affected_rows = mysqli_stmt_affected_rows($delete_app_stmt);
    mysqli_stmt_close($delete_app_stmt);

    if ($affected_rows > 0) {
        mysqli_commit($conn);
        $_SESSION['message'] = "Your job application has been successfully cancelled.";
        $_SESSION['message_type'] = "success";
    } else {
        throw new Exception("Application not found or already cancelled.");
    }
} catch (Exception $e) {
    mysqli_rollback($conn);
    $_SESSION['message'] = $e->getMessage();
    $_SESSION['message_type'] = "error";
}

mysqli_close($conn);
header('Location: applied.php');
exit();
?>

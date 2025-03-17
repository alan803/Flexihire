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

$delete_sql = "DELETE FROM tbl_applications WHERE id = ? AND user_id = ?";
$delete_stmt = mysqli_prepare($conn, $delete_sql);

if ($delete_stmt) {
    mysqli_stmt_bind_param($delete_stmt, "ii", $application_id, $user_id);
    
    if (mysqli_stmt_execute($delete_stmt)) {
        $affected_rows = mysqli_stmt_affected_rows($delete_stmt);
        mysqli_stmt_close($delete_stmt);
        
        if ($affected_rows > 0) {
            $_SESSION['message'] = "Your job application has been successfully cancelled.";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Application not found or already cancelled.";
            $_SESSION['message_type'] = "error";
        }
    } else {
        mysqli_stmt_close($delete_stmt);
        $_SESSION['message'] = "Failed to cancel application. Please try again later.";
        $_SESSION['message_type'] = "error";
    }
} else {
    $_SESSION['message'] = "System error. Please contact support if this persists.";
    $_SESSION['message_type'] = "error";
}

mysqli_close($conn);
header('Location: applied.php');
exit();
?>

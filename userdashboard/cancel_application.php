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
    // First get certificate file paths
    $get_certs_sql = "SELECT id, file_path FROM tbl_certificates WHERE application_id = ? AND user_id = ?";
    $get_certs_stmt = mysqli_prepare($conn, $get_certs_sql);
    mysqli_stmt_bind_param($get_certs_stmt, "ii", $application_id, $user_id);
    mysqli_stmt_execute($get_certs_stmt);
    $cert_result = mysqli_stmt_get_result($get_certs_stmt);
    
    $file_paths = [];
    $cert_ids = [];
    
    while ($cert = mysqli_fetch_assoc($cert_result)) {
        if (!empty($cert['file_path']) && file_exists($cert['file_path'])) {
            $file_paths[] = $cert['file_path'];
        }
        $cert_ids[] = $cert['id'];
    }
    
    mysqli_stmt_close($get_certs_stmt);
    
    // Delete certificates from database
    if (!empty($cert_ids)) {
        $placeholders = implode(',', array_fill(0, count($cert_ids), '?'));
        $delete_certs_sql = "DELETE FROM tbl_certificates WHERE id IN ($placeholders) AND user_id = ?";
        $delete_certs_stmt = mysqli_prepare($conn, $delete_certs_sql);
        
        // Create parameter types string (i for each cert_id plus one i for user_id)
        $types = str_repeat('i', count($cert_ids)) . 'i';
        
        // Create parameters array
        $params = $cert_ids;
        $params[] = $user_id;
        
        // Bind parameters dynamically
        $bind_params = array();
        $bind_params[] = &$types;
        foreach ($params as $key => $value) {
            $bind_params[] = &$params[$key];
        }
        
        call_user_func_array(array($delete_certs_stmt, 'bind_param'), $bind_params);
        mysqli_stmt_execute($delete_certs_stmt);
        mysqli_stmt_close($delete_certs_stmt);
        
        error_log("Deleted " . count($cert_ids) . " certificates from database");
    } else {
        // If no certificates found, still try to delete any that might exist
        $delete_certs_sql = "DELETE FROM tbl_certificates WHERE application_id = ? AND user_id = ?";
        $delete_certs_stmt = mysqli_prepare($conn, $delete_certs_sql);
        mysqli_stmt_bind_param($delete_certs_stmt, "ii", $application_id, $user_id);
        mysqli_stmt_execute($delete_certs_stmt);
        mysqli_stmt_close($delete_certs_stmt);
        
        error_log("No certificates found, but attempted deletion anyway");
    }

    // Then delete application
    $delete_app_sql = "DELETE FROM tbl_applications WHERE id = ? AND user_id = ?";
    $delete_app_stmt = mysqli_prepare($conn, $delete_app_sql);
    mysqli_stmt_bind_param($delete_app_stmt, "ii", $application_id, $user_id);
    mysqli_stmt_execute($delete_app_stmt);
    
    $affected_rows = mysqli_stmt_affected_rows($delete_app_stmt);
    mysqli_stmt_close($delete_app_stmt);

    if ($affected_rows > 0) {
        // Commit database changes
        mysqli_commit($conn);
        
        // Now delete the physical files
        foreach ($file_paths as $file_path) {
            if (file_exists($file_path)) {
                if (unlink($file_path)) {
                    error_log("Deleted file: $file_path");
                } else {
                    error_log("Failed to delete file: $file_path");
                }
            }
        }
        
        $_SESSION['message'] = "Your job application has been successfully cancelled.";
        $_SESSION['message_type'] = "success";
    } else {
        throw new Exception("Application not found or already cancelled.");
    }
} catch (Exception $e) {
    mysqli_rollback($conn);
    $_SESSION['message'] = $e->getMessage();
    $_SESSION['message_type'] = "error";
    error_log("Error cancelling application: " . $e->getMessage());
}

mysqli_close($conn);
header('Location: applied.php');
exit();
?>

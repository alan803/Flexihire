<?php
session_start();
include '../database/connectdatabase.php';

// Debug information
error_log("POST data: " . print_r($_POST, true));
error_log("FILES data: " . print_r($_FILES, true));
error_log("SESSION data: " . print_r($_SESSION, true));
error_log("GET data: " . print_r($_GET, true));

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['message'] = "Please login to apply for jobs.";
    $_SESSION['message_type'] = "error";
    header("Location: ../login/loginvalidation.php");
    exit();
}

// Get user_id from session
$user_id = $_SESSION['user_id'];

// Check if job_id is passed via POST or GET
if (isset($_POST['job_id']) && !empty($_POST['job_id'])) {
    $job_id = intval($_POST['job_id']);
} elseif (isset($_GET['job_id']) && !empty($_GET['job_id'])) {
    $job_id = intval($_GET['job_id']);
} else {
    error_log("No job_id found in POST or GET");
    $_SESSION['message'] = "Missing job ID. Please try again.";
    $_SESSION['message_type'] = "error";
    header("Location: userdashboard.php");
    exit();
}

error_log("Processing application for user_id: $user_id, job_id: $job_id");

// Check if user has already applied
$check_sql = "SELECT * FROM tbl_applications WHERE user_id = ? AND job_id = ?";
$check_stmt = mysqli_prepare($conn, $check_sql);
mysqli_stmt_bind_param($check_stmt, "ii", $user_id, $job_id);
mysqli_stmt_execute($check_stmt);
$result = mysqli_stmt_get_result($check_stmt);

if (mysqli_num_rows($result) > 0) {
    $_SESSION['message'] = "You have already applied for this job.";
    $_SESSION['message_type'] = "error";
    header("Location: userdashboard.php");
    exit();
}

// Handle file uploads
$upload_dir = "../uploads/";

// Create upload directory if it doesn't exist
if (!file_exists($upload_dir)) {
    if (!mkdir($upload_dir, 0777, true)) {
        error_log("Failed to create upload directory: $upload_dir");
        $_SESSION['message'] = "Failed to create upload directory.";
        $_SESSION['message_type'] = "error";
        header("Location: userdashboard.php");
        exit();
    }
}

// Start transaction
mysqli_begin_transaction($conn);

try {
    $application_id = null;
    
    // 1. Insert into tbl_applications first to get application_id
    $app_sql = "INSERT INTO tbl_applications (user_id, job_id, status) 
                VALUES (?, ?, 'Pending')";
    $app_stmt = mysqli_prepare($conn, $app_sql);
    
    if (!$app_stmt) {
        throw new Exception("Application prepare failed: " . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($app_stmt, "ii", $user_id, $job_id);
    
    if (!mysqli_stmt_execute($app_stmt)) {
        throw new Exception("Application execute failed: " . mysqli_stmt_error($app_stmt));
    }
    
    $application_id = mysqli_insert_id($conn);
    error_log("Application inserted with ID: $application_id");
    
    // 2. Process license upload and insert into tbl_certificates
    if (isset($_FILES['license']) && $_FILES['license']['error'] === UPLOAD_ERR_OK) {
        $temp_name = $_FILES['license']['tmp_name'];
        $name = basename($_FILES['license']['name']);
        $extension = pathinfo($name, PATHINFO_EXTENSION);
        $new_name = uniqid("license_" . $user_id . "_") . "." . $extension;
        $license_path = $upload_dir . $new_name;
        
        error_log("Attempting to upload license to: $license_path");
        
        if (!move_uploaded_file($temp_name, $license_path)) {
            throw new Exception("Failed to move uploaded license file");
        }
        
        error_log("License uploaded successfully to: $license_path");
        
        // Insert license into tbl_certificates
        $cert_sql = "INSERT INTO tbl_certificates (application_id, job_id, user_id, certificate_type, file_path, status) 
                    VALUES (?, ?, ?, 'license', ?, 'pending')";
        $cert_stmt = mysqli_prepare($conn, $cert_sql);
        
        if (!$cert_stmt) {
            throw new Exception("License certificate prepare failed: " . mysqli_error($conn));
        }
        
        mysqli_stmt_bind_param($cert_stmt, "iiis", $application_id, $job_id, $user_id, $license_path);
        
        if (!mysqli_stmt_execute($cert_stmt)) {
            throw new Exception("License certificate execute failed: " . mysqli_stmt_error($cert_stmt));
        }
        
        error_log("License certificate inserted successfully");
    }
    
    // 3. Process badge upload and insert into tbl_certificates
    if (isset($_FILES['badge']) && $_FILES['badge']['error'] === UPLOAD_ERR_OK) {
        $temp_name = $_FILES['badge']['tmp_name'];
        $name = basename($_FILES['badge']['name']);
        $extension = pathinfo($name, PATHINFO_EXTENSION);
        $new_name = uniqid("badge_" . $user_id . "_") . "." . $extension;
        $badge_path = $upload_dir . $new_name;
        
        error_log("Attempting to upload badge to: $badge_path");
        
        if (!move_uploaded_file($temp_name, $badge_path)) {
            throw new Exception("Failed to move uploaded badge file");
        }
        
        error_log("Badge uploaded successfully to: $badge_path");
        
        // Insert badge into tbl_certificates
        $cert_sql = "INSERT INTO tbl_certificates (application_id, job_id, user_id, certificate_type, file_path, status) 
                    VALUES (?, ?, ?, 'badge', ?, 'pending')";
        $cert_stmt = mysqli_prepare($conn, $cert_sql);
        
        if (!$cert_stmt) {
            throw new Exception("Badge certificate prepare failed: " . mysqli_error($conn));
        }
        
        mysqli_stmt_bind_param($cert_stmt, "iiis", $application_id, $job_id, $user_id, $badge_path);
        
        if (!mysqli_stmt_execute($cert_stmt)) {
            throw new Exception("Badge certificate execute failed: " . mysqli_stmt_error($cert_stmt));
        }
        
        error_log("Badge certificate inserted successfully");
    }
    
    // Commit transaction
    mysqli_commit($conn);
    
    $_SESSION['message'] = "Your application has been submitted successfully!";
    $_SESSION['message_type'] = "success";
    
} catch (Exception $e) {
    // Rollback transaction on error
    mysqli_rollback($conn);
    
    error_log("Transaction failed: " . $e->getMessage());
    $_SESSION['message'] = "Failed to submit application: " . $e->getMessage();
    $_SESSION['message_type'] = "error";
}

mysqli_close($conn);
header("Location: userdashboard.php");
exit();
?>

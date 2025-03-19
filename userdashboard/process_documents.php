<?php
session_start();
include '../database/connectdatabase.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_POST['user_id'];
    $job_id = $_POST['job_id'];
    $upload_success = true;
    $upload_dir = "../database/user_documents/";
    
    // Create directory if it doesn't exist
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // First insert the application to get application_id
    $sql = "INSERT INTO tbl_applications (user_id, job_id, status) VALUES (?, ?, 'Pending')";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $job_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $application_id = mysqli_insert_id($conn);
        
        // Process license file
        if (isset($_FILES['license']) && $_FILES['license']['error'] == 0) {
            $license_file = $_FILES['license'];
            $license_name = "license_" . $user_id . "_" . time() . "_" . basename($license_file['name']);
            $license_path = $upload_dir . $license_name;
            
            if (move_uploaded_file($license_file['tmp_name'], $license_path)) {
                // Insert into tbl_certificates
                $sql = "INSERT INTO tbl_certificates (application_id, job_id, user_id, certificate_type, file_path) 
                       VALUES (?, ?, ?, 'license', ?)";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "iiis", $application_id, $job_id, $user_id, $license_path);
                if (!mysqli_stmt_execute($stmt)) {
                    $upload_success = false;
                }
            } else {
                $upload_success = false;
            }
        }

        // Process badge file
        if (isset($_FILES['badge']) && $_FILES['badge']['error'] == 0) {
            $badge_file = $_FILES['badge'];
            $badge_name = "badge_" . $user_id . "_" . time() . "_" . basename($badge_file['name']);
            $badge_path = $upload_dir . $badge_name;
            
            if (move_uploaded_file($badge_file['tmp_name'], $badge_path)) {
                // Insert into tbl_certificates
                $sql = "INSERT INTO tbl_certificates (application_id, job_id, user_id, certificate_type, file_path) 
                       VALUES (?, ?, ?, 'badge', ?)";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "iiis", $application_id, $job_id, $user_id, $badge_path);
                if (!mysqli_stmt_execute($stmt)) {
                    $upload_success = false;
                }
            } else {
                $upload_success = false;
            }
        }

        if ($upload_success) {
            $_SESSION['message'] = "Documents uploaded and application submitted successfully!";
            $_SESSION['message_type'] = "success";
        } else {
            // If upload failed, delete the application
            $delete_sql = "DELETE FROM tbl_applications WHERE id = ?";
            $delete_stmt = mysqli_prepare($conn, $delete_sql);
            mysqli_stmt_bind_param($delete_stmt, "i", $application_id);
            mysqli_stmt_execute($delete_stmt);
            
            $_SESSION['message'] = "Error uploading documents.";
            $_SESSION['message_type'] = "error";
        }
    } else {
        $_SESSION['message'] = "Error submitting application.";
        $_SESSION['message_type'] = "error";
    }

    mysqli_close($conn);
    header("Location: userdashboard.php");
    exit();
}
?> 
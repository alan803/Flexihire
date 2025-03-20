<?php
session_start();
include '../database/connectdatabase.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['message'] = "User not logged in";
        $_SESSION['message_type'] = "error";
        header('Location: userdashboard.php');
        exit;
    }

    $user_id = $_SESSION['user_id'];
    $job_id = $_POST['job_id'];
    $upload_dir = "../database/user_documents/";

    // Create directory if it doesn't exist
    if (!file_exists($upload_dir) && !mkdir($upload_dir, 0777, true) && !is_dir($upload_dir)) {
        $_SESSION['message'] = "Failed to create upload directory.";
        $_SESSION['message_type'] = "error";
        header('Location: userdashboard.php');
        exit;
    }

    // Insert application record
    $sql = "INSERT INTO tbl_applications (user_id, job_id, status) VALUES (?, ?, 'Pending')";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $job_id);

    if (mysqli_stmt_execute($stmt)) {
        $application_id = mysqli_insert_id($conn);

        // Function to handle file uploads
        function uploadCertificate($file, $type, $application_id, $job_id, $user_id, $upload_dir, $conn) {
            if ($file['error'] === 0) {
                $file_name = $type . "_" . $user_id . "_" . time() . "_" . basename($file['name']);
                $file_path = $upload_dir . $file_name;

                if (move_uploaded_file($file['tmp_name'], $file_path)) {
                    $sql = "INSERT INTO tbl_certificates (application_id, job_id, user_id, certificate_type, file_path) 
                            VALUES (?, ?, ?, ?, ?)";
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, "iiiss", $application_id, $job_id, $user_id, $type, $file_path);
                    if (!mysqli_stmt_execute($stmt)) {
                        return false;
                    }
                    return true;
                }
            }
            return false;
        }

        // Upload license
        $upload_success = true;
        if (isset($_FILES['license'])) {
            $upload_success = uploadCertificate($_FILES['license'], 'license', $application_id, $job_id, $user_id, $upload_dir, $conn);
        }

        // Upload badge
        if ($upload_success && isset($_FILES['badge'])) {
            $upload_success = uploadCertificate($_FILES['badge'], 'badge', $application_id, $job_id, $user_id, $upload_dir, $conn);
        }

        if ($upload_success) {
            $_SESSION['message'] = "Application submitted successfully with required documents!";
            $_SESSION['message_type'] = "success";
            mysqli_close($conn);
            header('Location: userdashboard.php');
            exit();
        } else {
            // Cleanup if upload fails
            $delete_sql = "DELETE FROM tbl_applications WHERE id = ?";
            $delete_stmt = mysqli_prepare($conn, $delete_sql);
            mysqli_stmt_bind_param($delete_stmt, "i", $application_id);
            mysqli_stmt_execute($delete_stmt);

            $_SESSION['message'] = "Error uploading documents. Please try again.";
            $_SESSION['message_type'] = "error";
            mysqli_close($conn);
            header('Location: userdashboard.php');
            exit();
        }
    } else {
        $_SESSION['message'] = "Error submitting application: " . mysqli_error($conn);
        $_SESSION['message_type'] = "error";
        header('Location: userdashboard.php');
        exit;
    }
} else {
    $_SESSION['message'] = "Invalid request method.";
    $_SESSION['message_type'] = "error";
    header('Location: userdashboard.php');
    exit;
}
?>

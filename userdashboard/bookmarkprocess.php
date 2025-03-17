<?php
session_start();
include '../database/connectdatabase.php';

// Debug logs
error_log("POST data: " . print_r($_POST, true));
error_log("Session data: " . print_r($_SESSION, true));

// Set header to return JSON response
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['id']) && !isset($_SESSION['user_id'])) {
    error_log("User not logged in");
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit();
}

// Use the correct session key
$user_id = isset($_SESSION['id']) ? $_SESSION['id'] : $_SESSION['user_id'];
$job_id = isset($_POST['job_id']) ? (int)$_POST['job_id'] : 0;

// Get job title from tbl_jobs
$job_title_sql = "SELECT job_title FROM tbl_jobs WHERE job_id = ?";
$job_title_stmt = mysqli_prepare($conn, $job_title_sql);
mysqli_stmt_bind_param($job_title_stmt, "i", $job_id);
mysqli_stmt_execute($job_title_stmt);
$job_title_result = mysqli_stmt_get_result($job_title_stmt);
$job_title_row = mysqli_fetch_assoc($job_title_result);
$job_title = $job_title_row['job_title'];

// Debug logs
error_log("User ID: " . $user_id);
error_log("Job ID: " . $job_id);

if ($job_id === 0) {
    error_log("Invalid job ID");
    echo json_encode(['success' => false, 'message' => 'Invalid job ID']);
    exit();
}

try {
    // Check if already bookmarked
    $check_sql = "SELECT * FROM tbl_bookmarks WHERE user_id = ? AND job_id = ?";
    $check_stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($check_stmt, "ii", $user_id, $job_id);
    mysqli_stmt_execute($check_stmt);
    $result = mysqli_stmt_get_result($check_stmt);

    if (mysqli_num_rows($result) > 0) {
        // Remove bookmark
        $delete_sql = "DELETE FROM tbl_bookmarks WHERE user_id = ? AND job_id = ?";
        $delete_stmt = mysqli_prepare($conn, $delete_sql);
        mysqli_stmt_bind_param($delete_stmt, "ii", $user_id, $job_id);
        
        if (mysqli_stmt_execute($delete_stmt)) {
            error_log("Bookmark removed successfully");
            echo json_encode([
                'success' => true, 
                'action' => 'unbookmarked',
                'message' => 'Bookmark removed successfully'
            ]);
        } else {
            error_log("Error removing bookmark: " . mysqli_error($conn));
            echo json_encode([
                'success' => false, 
                'message' => 'Error removing bookmark: ' . mysqli_error($conn)
            ]);
        }
    } else {
        // Add bookmark with job title
        $insert_sql = "INSERT INTO tbl_bookmarks (user_id, job_id, job_title) VALUES (?, ?, ?)";
        $insert_stmt = mysqli_prepare($conn, $insert_sql);
        mysqli_stmt_bind_param($insert_stmt, "iis", $user_id, $job_id, $job_title);
        
        if (mysqli_stmt_execute($insert_stmt)) {
            error_log("Bookmark added successfully");
            echo json_encode([
                'success' => true, 
                'action' => 'bookmarked',
                'message' => 'Job bookmarked successfully'
            ]);
        } else {
            error_log("Error adding bookmark: " . mysqli_error($conn));
            echo json_encode([
                'success' => false, 
                'message' => 'Error adding bookmark: ' . mysqli_error($conn)
            ]);
        }
    }
} catch (Exception $e) {
    error_log("Exception: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}

mysqli_close($conn);
?>
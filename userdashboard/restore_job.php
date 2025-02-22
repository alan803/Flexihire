<?php
session_start();
include '../database/connectdatabase.php';

if (!isset($_SESSION['employer_id'])) {
    header("Location: ../login/loginvalidation.php");
    exit();
}

if (isset($_GET['id'])) {
    $job_id = $_GET['id'];
    $employer_id = $_SESSION['employer_id'];

    // Restore the job (set is_deleted = 0)
    $sql = "UPDATE tbl_jobs SET is_deleted = 0 WHERE job_id = ? AND employer_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $job_id, $employer_id);

    if ($stmt->execute()) {
        $message = "Job restored successfully.";
    } else {
        $message = "Error restoring job.";
    }

    $stmt->close();
    header("Location: myjoblist.php?message=" . urlencode($message));
    exit();
} else {
    header("Location: myjoblist.php?message=Invalid job selection.");
    exit();
}

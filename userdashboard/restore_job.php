<?php
session_start();
include '../database/connectdatabase.php';

if (!isset($_SESSION['employer_id'])) 
{
    header("Location: ../login/loginvalidation.php");
    exit();
}

if (isset($_GET['job_id'])) 
{
    $job_id = $_GET['job_id'];
    $employer_id = $_SESSION['employer_id'];

    // Restore the job by setting is_deleted to 0
    $sql = "UPDATE tbl_jobs SET is_deleted = 0 WHERE job_id = ? AND employer_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $job_id, $employer_id);

    if ($stmt->execute()) {
        $_SESSION['message'] = "Job has been restored successfully.";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error restoring job: " . $conn->error;
        $_SESSION['message_type'] = "error";
    }

    $stmt->close();
    header("Location: myjoblist.php");
    exit();
} 
else 
{
    $_SESSION['message'] = "Invalid job selection.";
    $_SESSION['message_type'] = "error";
    header("Location: myjoblist.php");
    exit();
}
?>

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
        header("Location: myjoblist.php?restored=true");
    } else {
        $_SESSION['error'] = "Error restoring job: " . $conn->error;
        header("Location: myjoblist.php");
    }

    $stmt->close();
    exit();
} 
else 
{
    $_SESSION['error'] = "Invalid job selection.";
    header("Location: myjoblist.php");
    exit();
}
?>

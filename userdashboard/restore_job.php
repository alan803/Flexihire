<?php
session_start();
include '../database/connectdatabase.php';
$dbname = "project";
mysqli_select_db($conn, $dbname);

if (!isset($_SESSION['employer_id']) || !isset($_GET['id'])) {
    header("location: myjoblist.php");
    exit();
}

$job_id = $_GET['id'];
$employer_id = $_SESSION['employer_id'];

// Verify job ownership and current status
$check_query = "SELECT is_deleted FROM tbl_jobs WHERE job_id = ? AND employer_id = ?";
$stmt = mysqli_prepare($conn, $check_query);
mysqli_stmt_bind_param($stmt, "ii", $job_id, $employer_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    header("location: myjoblist.php?message=Job not found");
    exit();
}

// Update job status to active
$update_query = "UPDATE tbl_jobs SET is_deleted = 0 WHERE job_id = ? AND employer_id = ?";
$stmt = mysqli_prepare($conn, $update_query);
mysqli_stmt_bind_param($stmt, "ii", $job_id, $employer_id);

if (mysqli_stmt_execute($stmt)) {
    header("location: myjoblist.php?message=Job activated successfully");
} else {
    header("location: myjoblist.php?message=Failed to activate job");
}
exit();
?>

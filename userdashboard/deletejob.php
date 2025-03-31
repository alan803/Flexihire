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

    // Soft delete: Update the is_deleted column instead of deleting
    $sql = "UPDATE tbl_jobs SET is_deleted = 1 WHERE job_id = ? AND employer_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $job_id, $employer_id);

    if ($stmt->execute()) {
        // Change this line to include the deactivated parameter
        header("Location: myjoblist.php?deactivated=true");
    } else {
        header("Location: myjoblist.php?error=true");
    }

    $stmt->close();
    exit();
} else {
    header("Location: myjoblist.php?error=true");
    exit();
}
?>

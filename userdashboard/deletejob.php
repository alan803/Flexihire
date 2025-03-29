<?php
    session_start();
    include '../database/connectdatabase.php';

    if (!isset($_SESSION['employer_id'])) 
    {
        header("Location: ../login/loginvalidation.php");
        exit();
    }

    if (isset($_GET['id'])) 
    {
        $job_id = $_GET['id'];
        $employer_id = $_SESSION['employer_id'];

        // Check if any user has applied for this job
        $sql = "SELECT COUNT(*) as application_count FROM tbl_applications WHERE job_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $job_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if ($row['application_count'] > 0) {
            // Job has applications, prevent deletion
            $_SESSION['message'] = "Cannot delete job as it has active applications.";
            $_SESSION['message_type'] = "error";
            header("Location: myjoblist.php");
            exit();
        }

        // If no applications, proceed with soft delete
        $sql = "UPDATE tbl_jobs SET is_deleted = 1 WHERE job_id = ? AND employer_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $job_id, $employer_id);

        if ($stmt->execute()) {
            $_SESSION['message'] = "Job successfully deactivated.";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Error deactivating job.";
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

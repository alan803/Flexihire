<?php
    session_start();
    include '../database/connectdatabase.php';
    $dbname="project";
    mysqli_select_db($conn,$dbname);

    // Check if admin is logged in
    if (!isset($_SESSION['admin_id'])) {
        header('Location: ../login/loginvalidation.php');
        exit();
    }

    $employer_id = filter_input(INPUT_GET, 'employer_id', FILTER_VALIDATE_INT);

    if ($employer_id) {
        $sql = "UPDATE tbl_login SET status = 'active' WHERE employer_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $employer_id);
        $result = mysqli_stmt_execute($stmt);

        if ($result) {
            $_SESSION['message'] = "Employer account has been activated successfully";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Error activating employer account";
            $_SESSION['message_type'] = "error";
        }
    }

    header('Location: manage_employers.php');
    exit();
?>
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

    $user_id = filter_input(INPUT_GET, 'user_id', FILTER_VALIDATE_INT);

    if ($user_id) {
        $sql = "UPDATE tbl_login SET status = 'active' WHERE user_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        $result = mysqli_stmt_execute($stmt);

        if ($result) {
            $_SESSION['message'] = "User account has been activated successfully";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Error activating user account";
            $_SESSION['message_type'] = "error";
        }
    }

    header('Location: manage_users.php');
    exit();
?>

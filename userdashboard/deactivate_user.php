<?php
    session_start();
    include '../database/connectdatabase.php';
    $dbname="project";
    mysqli_select_db($conn,$dbname);

    $user_id = $_GET['user_id'];

    $sql = "UPDATE tbl_login SET status = 'inactive' WHERE user_id = $user_id";
    $result = mysqli_query($conn,$sql);

    if($result)
    {
        echo "<script>window.location.href = 'manage_users.php';</script>";
        echo "<script>alert('User deactivated successfully');</script>";
    }
    else
    {
        echo "<script>window.location.href = 'manage_users.php';</script>";
        echo "<script>alert('User deactivation failed');</script>";
    }
?>
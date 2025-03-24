<?php
    session_start();
    include '../database/connectdatabase.php';
    $dbname="project";
    mysqli_select_db($conn,$dbname);

    $employer_id = $_GET['employer_id'];

    $sql = "UPDATE tbl_login SET status = 'inactive' WHERE employer_id = $employer_id";
    $result = mysqli_query($conn,$sql);

    if($result)
    {
        echo "<script>window.location.href = 'manage_employers.php';</script>";
        echo "<script>alert('Employer deactivated successfully');</script>";
    }
    else
    {
        echo "<script>window.location.href = 'manage_employers.php';</script>";
        echo "<script>alert('Employer deactivation failed');</script>";
    }
?>
<?php
    include '../database/connectdatabase.php';
    $dbname="project";
    mysqli_select_db($conn,$dbname);

    $user_id = $_GET['user_id'];
    $job_id = $_GET['job_id'];


?>
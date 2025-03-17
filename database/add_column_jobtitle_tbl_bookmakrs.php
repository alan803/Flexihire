<?php
    session_start();
    include 'connectdatabase.php';
    $dbname="project";
    mysqli_select_db($conn,$dbname);
    
    $sql="ALTER TABLE tbl_bookmarks ADD COLUMN job_title VARCHAR(255)";
    $result=mysqli_query($conn,$sql);
?>
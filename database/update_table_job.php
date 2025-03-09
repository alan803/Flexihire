<?php
    include 'connectdatabase.php';
    $dbname="project";
    mysqli_select_db($conn,$dbname);
    $sql="ALTER table tbl_jobs ADD COLUMN category VARCHAR(50) NOT NULL,
        ADD COLUMN town VARCHAR(255) NOT NULL,
        ADD COLUMN start_time TIME NOT NULL,
        ADD COLUMN end_time TIME NOT NULL,
        ADD COLUMN working_days VARCHAR(50) NOT NULL,
        ADD COLUMN contact_no VARCHAR(255) NOT NULL";
    if(mysqli_query($conn,$sql))
    {
        echo "updated";
    }
    else
    {
        echo "error";
    }
    mysqli_close($conn);
?>
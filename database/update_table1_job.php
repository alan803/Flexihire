<?php
    include 'connectdatabase.php';
    $dbname="project";
    mysqli_select_db($conn,$dbname);
    $sql="ALTER table tbl_jobs ADD COLUMN license_required VARCHAR(255) NULL DEFAULT NULL,
        ADD COLUMN badge_required VARCHAR(255) DEFAULT NULL";
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
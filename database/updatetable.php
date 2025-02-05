<?php
    include 'connectdatabase.php';
    $dbname="project";
    mysqli_select_db($conn,$dbname);
    $sql="ALTER TABLE tbl_employer DROP COLUMN name";
    if(mysqli_query($conn,$sql))
    {
        echo "column dropped";
    }
    else
    {
        echo "error".mysqli_error($conn);
    }
?>
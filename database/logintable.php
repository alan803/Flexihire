<?php
    include 'connectdatabase.php';
    $dbname="project";

    mysqli_select_db($conn,$dbname);

    $sql="INSERT INTO tbl_login (username, email, password, role) 
    VALUES ('flexihire', 'adminflexihire2025@gmail.com', 'adminflexhire2025@', 3);
    ";

    if(mysqli_query($conn,$sql))
    {
        // echo "table created";
    }
    else
    {
        // die("error".mysqli_error($conn));
    }
    mysqli_close($conn);
?>
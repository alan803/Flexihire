<?php
    include 'connect.php';
    $databasename="taste";
    mysqli_select_db($conn,$databasename);

    $sql="CREATE TABLE employe(
        id int(6) unsigned auto_increment primary key,
        name varchar(20) not null,
        age int(3) not null,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";

    if(mysqli_query($conn,$sql))
    {
        echo "table created";
    }
    else
    {
        die("error ".mysqli_error($conn));
    }
    mysqli_close($conn);
?>
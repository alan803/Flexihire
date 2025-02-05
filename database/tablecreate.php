<?php
    include 'connectdatabase.php';
    $dbname="project";

    mysqli_select_db($conn,$dbname);

    $sql="CREATE TABLE tbl_user(
        id int unsigned auto_increment primary key,
        name varchar(255) not null,
        email varchar(255) not null,
        password varchar(255) not null,
        created_at datetime default current_timestamp,
        updated_at datetime default current_timestamp on update current_timestamp
    )";

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
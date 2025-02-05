<?php
    include 'connectdatabase.php';
    $dbname="project";

    mysqli_select_db($conn,$dbname);

    $sql="CREATE TABLE tbl_employer(
        id int unsigned auto_increment primary key,
        user_id int unsigned,
        name varchar(255) not null,
        email varchar(255) not null,
        company_name varchar(255) not null,
        district varchar(255) not null,
        password varchar(255) not null,
        created_at datetime default current_timestamp,
        updated_at datetime default current_timestamp on update current_timestamp,
        foreign key(user_id) references tbl_user(id)
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
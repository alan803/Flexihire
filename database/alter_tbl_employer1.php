<?php
    include 'connectdatabase.php';
    $dbname="project";
    mysqli_select_db($conn, $dbname);

    $sql = "ALTER TABLE tbl_employer 
            ADD COLUMN shop_description VARCHAR(255) NULL,
            ADD COLUMN contact_person VARCHAR(255) NULL,
            ADD COLUMN details TEXT NULL";
            
    if(mysqli_query($conn, $sql)) {
        echo "Table updated successfully";
    } else {
        echo "Error updating table: " . mysqli_error($conn);
    }
?>
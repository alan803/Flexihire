<?php
include 'connectdatabase.php';

$dbname = "project";
mysqli_select_db($conn, $dbname);

// SQL query to alter tbl_user
$alterQuery = "ALTER TABLE tbl_user 
    ADD COLUMN username VARCHAR(50) UNIQUE AFTER last_name,
    ADD COLUMN profile_image VARCHAR(255) DEFAULT 'default.jpg' AFTER username,
    ADD COLUMN phone_number VARCHAR(15) AFTER profile_image,
    ADD COLUMN address VARCHAR(255) AFTER phone_number;";

if (mysqli_query($conn, $alterQuery)) {
    echo "Table 'tbl_user' updated successfully.";
} else {
    echo "Error updating table: " . mysqli_error($conn);
}

// Close connection
mysqli_close($conn);
?>

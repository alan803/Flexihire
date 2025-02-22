<?php
// Include database connection
include 'connectdatabase.php';

// Select the database
$dbname = "project";
mysqli_select_db($conn, $dbname);

// SQL query to add new columns to tbl_employer
$sql = "ALTER TABLE tbl_employer 
        ADD COLUMN username VARCHAR(50),
        ADD COLUMN profile_image VARCHAR(255) DEFAULT 'default.jpg',
        ADD COLUMN phone_number VARCHAR(15),
        ADD COLUMN address VARCHAR(255)";

// Execute the query
if (mysqli_query($conn, $sql)) {
    echo "Table 'tbl_employer' updated successfully.";
} else {
    echo "Error updating table: " . mysqli_error($conn);
}

// Close the connection
mysqli_close($conn);
?>

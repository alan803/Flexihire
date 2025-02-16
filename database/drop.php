<?php
/*include 'connectdatabase.php';
$dbname = "project";

mysqli_select_db($conn, $dbname);

// Disable foreign key checks to avoid constraint issues
mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 0");

// Drop tables if they exist
$dropTables = [
    "DROP TABLE IF EXISTS tbl_employer",
    "DROP TABLE IF EXISTS tbl_login",
    "DROP TABLE IF EXISTS tbl_user"
];

foreach ($dropTables as $query) {
    if (!mysqli_query($conn, $query)) {
        die("Error dropping table: " . mysqli_error($conn));
    }
}

// Re-enable foreign key checks
mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 1");

mysqli_close($conn);*/
?>

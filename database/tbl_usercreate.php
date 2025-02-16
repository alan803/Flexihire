<?php
include 'connectdatabase.php';
$dbname = "project";

mysqli_select_db($conn, $dbname);

$sql = "CREATE TABLE IF NOT EXISTS tbl_user (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    dob DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (mysqli_query($conn, $sql)) {
    echo "Table 'tbl_user' created successfully.";
} else {
    die("Error creating table: " . mysqli_error($conn));
}

mysqli_close($conn);
?>

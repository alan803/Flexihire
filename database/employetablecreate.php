<?php
include 'connectdatabase.php';
$dbname = "project";

mysqli_select_db($conn, $dbname);

$sql = "CREATE TABLE IF NOT EXISTS tbl_employer (
    employer_id INT AUTO_INCREMENT PRIMARY KEY,
    company_name VARCHAR(255) NOT NULL,
    district VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (mysqli_query($conn, $sql)) {
    echo "Table 'tbl_employer' created successfully.";
} else {
    die("Error creating table: " . mysqli_error($conn));
}

mysqli_close($conn);
?>

<?php
include 'connectdatabase.php';
$dbname = "project";

mysqli_select_db($conn, $dbname);

$sql = "CREATE TABLE IF NOT EXISTS tbl_login (
    login_id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('student', 'employer') NOT NULL,
    user_id INT DEFAULT NULL,
    employer_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES tbl_user(user_id) ON DELETE CASCADE,
    FOREIGN KEY (employer_id) REFERENCES tbl_employer(employer_id) ON DELETE CASCADE
)";

if (mysqli_query($conn, $sql)) {
    echo "Table 'tbl_login' created successfully.";
} else {
    die("Error creating table: " . mysqli_error($conn));
}

mysqli_close($conn);
?>

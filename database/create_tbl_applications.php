<?php
include '../database/connectdatabase.php';

$sql = "CREATE TABLE IF NOT EXISTS tbl_applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    job_id INT NOT NULL,
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('Pending', 'Accepted', 'Rejected') DEFAULT 'Pending',
    FOREIGN KEY (user_id) REFERENCES tbl_user(user_id) ON DELETE CASCADE,
    FOREIGN KEY (job_id) REFERENCES tbl_jobs(job_id) ON DELETE CASCADE
)";

if (mysqli_query($conn, $sql)) {
    echo "Table 'tbl_applications' created successfully";
} else {
    echo "Error creating table: " . mysqli_error($conn);
}

mysqli_close($conn);
?>
<?php
// Include database connection
include 'connectdatabase.php';

$sql = "CREATE TABLE IF NOT EXISTS tbl_selected (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL UNIQUE,
    job_id INT NOT NULL,
    user_id INT NOT NULL,
    selected_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    remarks VARCHAR(255) DEFAULT NULL,
    CONSTRAINT fk_application FOREIGN KEY (application_id) REFERENCES tbl_applications(id) ON DELETE CASCADE,
    CONSTRAINT fk_job FOREIGN KEY (job_id) REFERENCES tbl_jobs(job_id) ON DELETE CASCADE,
    CONSTRAINT fk_user FOREIGN KEY (user_id) REFERENCES tbl_user(user_id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "Table tbl_selected created successfully.";
} else {
    echo "Error creating table: " . $conn->error;
}

$conn->close();

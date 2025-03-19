<?php
    session_start();
    include 'connectdatabase.php';
    $dbname = "project";
    mysqli_select_db($conn, $dbname);

    // First, check the structure of the referenced tables
    $check_applications = "SHOW COLUMNS FROM tbl_applications";
    $check_jobs = "SHOW COLUMNS FROM tbl_jobs";
    $check_users = "SHOW COLUMNS FROM tbl_user";
    
    $app_result = mysqli_query($conn, $check_applications);
    $jobs_result = mysqli_query($conn, $check_jobs);
    $users_result = mysqli_query($conn, $check_users);
    
    if (!$app_result || !$jobs_result || !$users_result) {
        echo "Error checking table structures: " . mysqli_error($conn);
        exit;
    }
    
    // Create the certificates table without UNSIGNED and with matching column types
    $sql = "CREATE TABLE tbl_certificates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        application_id INT NOT NULL,
        job_id INT NOT NULL,
        user_id INT NOT NULL,
        certificate_type VARCHAR(50) NOT NULL,
        file_path VARCHAR(255) NOT NULL,
        status VARCHAR(20) DEFAULT 'pending',
        uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (application_id) REFERENCES tbl_applications(id) ON DELETE CASCADE,
        FOREIGN KEY (job_id) REFERENCES tbl_jobs(job_id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES tbl_user(user_id) ON DELETE CASCADE
    )";

    $run = mysqli_query($conn, $sql);

    if ($run) {
        echo "Table created successfully";
    } else {
        echo "Table creation failed: " . mysqli_error($conn);
    }

    mysqli_close($conn);
?>

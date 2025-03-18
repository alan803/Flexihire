<?php
    session_start();
    include '../database/connectdatabase.php';
    $dbname="project";
    mysqli_select_db($conn,$dbname);

    //creating table reports
    $sql="CREATE TABLE IF NOT EXISTS tbl_reports (
    report_id INT AUTO_INCREMENT PRIMARY KEY,
    reporter_id INT NOT NULL,
    reported_job_id INT DEFAULT NULL,
    reported_employer_id INT DEFAULT NULL,
    reported_user_id INT DEFAULT NULL,
    reason TEXT NOT NULL,
    status VARCHAR(20) DEFAULT 'Pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    resolution_notes TEXT DEFAULT NULL,

    -- Foreign Key Constraints
    CONSTRAINT fk_reporter FOREIGN KEY (reporter_id) REFERENCES tbl_login(login_id) ON DELETE CASCADE,
    CONSTRAINT fk_reported_job FOREIGN KEY (reported_job_id) REFERENCES tbl_jobs(job_id) ON DELETE CASCADE,
    CONSTRAINT fk_reported_employer FOREIGN KEY (reported_employer_id) REFERENCES tbl_employer(employer_id) ON DELETE CASCADE,
    CONSTRAINT fk_reported_user FOREIGN KEY (reported_user_id) REFERENCES tbl_login(user_id) ON DELETE CASCADE
) ENGINE=INNODB;";

    if(mysqli_query($conn,$sql))
    {
        echo "Table reports created successfully";
    }
    else
    {
        echo "Error creating table reports: ".mysqli_error($conn);
    }
?>
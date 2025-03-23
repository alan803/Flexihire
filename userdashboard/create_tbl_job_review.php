<?php
    include '../database/connectdatabase.php';
    $dbname = "project";
    mysqli_select_db($conn, $dbname);

    // Create job reviews table
    $sql = "CREATE TABLE IF NOT EXISTS tbl_job_reviews (
        review_id INT PRIMARY KEY AUTO_INCREMENT,
        job_id INT NOT NULL,
        user_id INT NOT NULL,
        employer_id INT NOT NULL,
        rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
        review_text TEXT NOT NULL,
        review_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (job_id) REFERENCES tbl_jobs(job_id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES tbl_user(user_id) ON DELETE CASCADE,
        FOREIGN KEY (employer_id) REFERENCES tbl_employer(employer_id) ON DELETE CASCADE,
        UNIQUE KEY unique_job_review (job_id, user_id)
    )";

    if (mysqli_query($conn, $sql)) {
        echo "Job reviews table created successfully";
    } else {
        echo "Error creating table: " . mysqli_error($conn);
    }
?> 
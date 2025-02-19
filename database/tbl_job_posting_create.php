<?php
    include 'connectdatabase.php';
    $dbname="project";
    mysqli_select_db($conn,$dbname);
    $sql = "CREATE TABLE tbl_jobs (
        job_id INT AUTO_INCREMENT PRIMARY KEY,
        job_title VARCHAR(255) NOT NULL,
        location VARCHAR(255) NOT NULL,
        job_description TEXT NOT NULL,
        working_hour VARCHAR(50) NOT NULL,
        vacancy_date DATE NOT NULL,
        vacancy INT NOT NULL,
        salary DECIMAL(10,2) NOT NULL,
        application_deadline DATE NOT NULL,
        interview ENUM('yes', 'no') NOT NULL,
        employer_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, 
        FOREIGN KEY (employer_id) REFERENCES tbl_employer(employer_id) ON DELETE CASCADE
    )";

    if(mysqli_query($conn,$sql))
    {
        echo "Table 'tbl_jobs' created successfully.";
    }
    else
    {
        echo "Error creating table: " . mysqli_error($conn);
    }
?>
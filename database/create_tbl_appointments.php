<?php
    session_start();
    include 'connectdatabase.php';
    $dbname="project";
    mysqli_select_db($conn,$dbname);

    $sql="CREATE TABLE tbl_appointments (
    appointment_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    job_id INT NOT NULL,
    employer_id INT NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    status ENUM('pending', 'confirmed', 'cancelled') DEFAULT 'pending',
    location VARCHAR(255),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES tbl_user(user_id) ON DELETE CASCADE,
    FOREIGN KEY (job_id) REFERENCES tbl_jobs(job_id) ON DELETE CASCADE,
    FOREIGN KEY (employer_id) REFERENCES tbl_employer(employer_id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_job (job_id),
    INDEX idx_employer (employer_id)
    )";
    if(mysqli_query($conn,$sql))
    {
        echo "Table tbl_appointments created successfully";
    }
    else
    {
        echo "Error creating table: " . mysqli_error($conn);
    }
    mysqli_close($conn);
?>
<?php
    include 'connectdatabase.php';
    $dbname="project";
    mysqli_select_db($conn,$dbname);

    $sql="CREATE TABLE tbl_job_ratings (
    rating_id INT(11) PRIMARY KEY AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    job_id INT(11) NOT NULL,
    rating TINYINT(1) NOT NULL CHECK (rating BETWEEN 1 AND 5),
    review TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES tbl_user(user_id) ON DELETE CASCADE,
    FOREIGN KEY (job_id) REFERENCES tbl_jobs(job_id) ON DELETE CASCADE
    )";
    if(mysqli_query($conn,$sql))
    {
        echo "table created";
    }
    else
    {
        echo "Error ocured";
    }
    mysqli_close($conn);
?>
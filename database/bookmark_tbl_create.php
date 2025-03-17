<?php
    include 'connectdatabase.php';
    $dbname="project";
    mysqli_select_db($conn,$dbname);

    $sql = "CREATE TABLE IF NOT EXISTS tbl_bookmarks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        job_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES tbl_user(user_id) ON DELETE CASCADE,
        FOREIGN KEY (job_id) REFERENCES tbl_jobs(job_id) ON DELETE CASCADE
    )";
    
    if(mysqli_query($conn,$sql))
    {
        echo "Table created successfully";
    }
    else
    {
        echo "Error creating table: " . mysqli_error($conn);
    }

    mysqli_close($conn);
?>
<?php
    session_start();
    include '../database/connectdatabase.php';
    $dbname = "project";
    mysqli_select_db($conn, $dbname);

    if (!isset($_SESSION['user_id'])) {
        header("Location: ../login/loginvalidation.php");
        exit();
    }

    $user_id = $_SESSION['user_id'];

    if ($_SERVER['REQUEST_METHOD'] == 'POST') 
    {
        $job_id = $_POST['job_id'];
        $employer_id = $_POST['employer_id'];
        $rating = $_POST['rating'];
        $review_text = $_POST['review'];

        // Validate rating
        if (!is_numeric($rating) || $rating < 1 || $rating > 5) 
        {
            $_SESSION['error'] = "Invalid rating value";
            header("Location: reviews.php");
            exit();
        }

        // Check if user has already reviewed this job
        $check_sql = "SELECT rating_id FROM tbl_job_ratings WHERE job_id = ? AND user_id = ?";
        $check_stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($check_stmt, "ii", $job_id, $user_id);
        mysqli_stmt_execute($check_stmt);
        $result = mysqli_stmt_get_result($check_stmt);

        if (mysqli_num_rows($result) > 0) 
        {
            $_SESSION['error'] = "You have already reviewed this job";
            header("Location: reviews.php");
            exit();
        }

        // Insert the review
        $sql = "INSERT INTO tbl_job_ratings (job_id, user_id, rating, review) 
                VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "iiss", $job_id, $user_id, $rating, $review_text);

        if (mysqli_stmt_execute($stmt)) 
        {
            // Update employer's average rating and review count
            $updateEmployerSQL = "UPDATE tbl_user 
                                SET avg_rating = (
                                    SELECT AVG(r.rating) 
                                    FROM tbl_job_ratings r 
                                    JOIN tbl_jobs j ON r.job_id = j.job_id 
                                    WHERE j.employer_id = ?
                                ),
                                total_reviews = (
                                    SELECT COUNT(r.rating_id) 
                                    FROM tbl_job_ratings r 
                                    JOIN tbl_jobs j ON r.job_id = j.job_id 
                                    WHERE j.employer_id = ?
                                )
                                WHERE user_id = ?";

            $update_stmt = mysqli_prepare($conn, $updateEmployerSQL);
            mysqli_stmt_bind_param($update_stmt, "iii", $employer_id, $employer_id, $employer_id);
            
            if (mysqli_stmt_execute($update_stmt)) {
                $_SESSION['success'] = "Review submitted successfully";
            } else {
                $_SESSION['error'] = "Review submitted but failed to update employer rating";
            }
            
            mysqli_stmt_close($update_stmt);
        } 
        else 
        {
            $_SESSION['error'] = "Error submitting review: " . mysqli_error($conn);
        }

        mysqli_stmt_close($stmt);
        header("Location: reviews.php");
        exit();
    }
?>
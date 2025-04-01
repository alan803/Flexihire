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
            $_SESSION['message'] = "Invalid rating value";
            $_SESSION['message_type'] = 'error';
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
            $_SESSION['message'] = "You have already reviewed this job";
            $_SESSION['message_type'] = 'error';
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
            // First, let's verify the data we're trying to update
            $verify_sql = "SELECT AVG(r.rating) as avg_rating, COUNT(r.rating_id) as total_reviews 
                          FROM tbl_job_ratings r 
                          JOIN tbl_jobs j ON r.job_id = j.job_id 
                          WHERE j.employer_id = ?";
            $verify_stmt = mysqli_prepare($conn, $verify_sql);
            mysqli_stmt_bind_param($verify_stmt, "i", $employer_id);
            mysqli_stmt_execute($verify_stmt);
            $verify_result = mysqli_stmt_get_result($verify_stmt);
            $verify_data = mysqli_fetch_assoc($verify_result);
            
            // Update employer's average rating and review count in tbl_login
            $updateEmployerSQL = "UPDATE tbl_login 
                                SET avg_rating = ?,
                                    total_reviews = ?
                                WHERE user_id = ?";

            $update_stmt = mysqli_prepare($conn, $updateEmployerSQL);
            mysqli_stmt_bind_param($update_stmt, "dii", $verify_data['avg_rating'], $verify_data['total_reviews'], $employer_id);
            
            if (mysqli_stmt_execute($update_stmt)) {
                $_SESSION['message'] = "Review submitted successfully";
                $_SESSION['message_type'] = 'success';
            } else {
                $_SESSION['message'] = "Review submitted but failed to update employer rating";
                $_SESSION['message_type'] = 'error';
            }
            
            mysqli_stmt_close($verify_stmt);
            mysqli_stmt_close($update_stmt);
        } 
        else 
        {
            $_SESSION['message'] = "Error submitting review";
            $_SESSION['message_type'] = 'error';
        }

        mysqli_stmt_close($stmt);
        header("Location: reviews.php");
        exit();
    }
?>
<?php
function getEmployerRating($conn, $employer_id) 
{
    $sql = "SELECT AVG(r.rating) AS avg_rating, COUNT(r.rating_id) AS total_reviews
            FROM tbl_job_ratings r
            JOIN tbl_jobs j ON r.job_id = j.job_id
            WHERE j.employer_id = ?
            GROUP BY j.employer_id";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $employer_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);

    // Initialize default values
    $avg_rating = 0;
    $total_reviews = 0;

    // Only update values if we got results
    if ($row) {
        $avg_rating = $row['avg_rating'] ? round($row['avg_rating'], 1) : 0;
        $total_reviews = $row['total_reviews'] ? $row['total_reviews'] : 0;
    }

    return array(
        'rating' => $avg_rating,
        'total_reviews' => $total_reviews
    );
}

function displayEmployerRating($rating, $total_reviews) 
{
    echo "<div class='employer-rating'>";
    echo "<div class='rating-stars'>";
    
    // Display filled stars
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= round($rating)) {
            echo "<i class='fas fa-star'></i>";
        } else {
            echo "<i class='far fa-star'></i>";
        }
    }
    
    echo "</div>";
    echo "<div class='rating-info'>";
    echo "<span class='rating-value'>" . number_format($rating, 1) . "</span>";
    echo "<span class='rating-separator'>/</span>";
    echo "<span class='rating-max'>5</span>";
    echo "<span class='rating-count'>($total_reviews reviews)</span>";
    echo "</div>";
    echo "</div>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employer Rating</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #333;
            margin-bottom: 10px;
        }
        .employer-rating {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-top: 20px;
        }
        .rating-stars {
            display: flex;
            gap: 4px;
        }
        .rating-stars i {
            color: #FFD700;
            font-size: 24px;
        }
        .rating-info {
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .rating-value {
            font-size: 24px;
            font-weight: 600;
            color: #333;
        }
        .rating-separator {
            color: #666;
            font-size: 24px;
        }
        .rating-max {
            color: #666;
            font-size: 24px;
        }
        .rating-count {
            color: #666;
            font-size: 16px;
            margin-left: 8px;
        }
        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            margin-top: 20px;
            transition: background-color 0.3s;
        }
        .back-button:hover {
            background: #45a049;
        }
        .no-ratings {
            text-align: center;
            color: #666;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Employer Rating</h1>
        </div>
        <?php
        include '../database/connectdatabase.php';
        $dbname = "project";
        mysqli_select_db($conn, $dbname);

        if (isset($_GET['employer_id'])) {
            $employer_id = $_GET['employer_id'];
            $rating_data = getEmployerRating($conn, $employer_id);
            
            if ($rating_data['total_reviews'] > 0) {
                displayEmployerRating($rating_data['rating'], $rating_data['total_reviews']);
            } else {
                echo "<div class='no-ratings'>";
                echo "<i class='far fa-star' style='font-size: 48px; color: #ccc;'></i>";
                echo "<p>No ratings available for this employer yet.</p>";
                echo "</div>";
            }
        } else {
            echo "<div class='no-ratings'>";
            echo "<p>No employer ID provided.</p>";
            echo "</div>";
        }
        ?>
        <a href="jobdetails.php?job_id=<?php echo isset($_GET['job_id']) ? $_GET['job_id'] : ''; ?>" class="back-button">
            <i class="fas fa-arrow-left"></i>
            Back to Job Details
        </a>
    </div>
</body>
</html>
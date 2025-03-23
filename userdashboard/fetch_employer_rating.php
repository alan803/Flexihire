<?php
function getEmployerDetails($conn, $employer_id) 
{
    $sql = "SELECT e.*, l.email,
            (SELECT AVG(r.rating) FROM tbl_job_ratings r 
             JOIN tbl_jobs j ON r.job_id = j.job_id 
             WHERE j.employer_id = e.employer_id) as avg_rating,
            (SELECT COUNT(r.rating_id) FROM tbl_job_ratings r 
             JOIN tbl_jobs j ON r.job_id = j.job_id 
             WHERE j.employer_id = e.employer_id) as total_reviews
            FROM tbl_employer e 
            JOIN tbl_login l ON e.employer_id = l.employer_id
            WHERE e.employer_id = ?";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $employer_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_assoc($result);
}

function displayEmployerProfile($employer) 
{
    // Debug information
    error_log("Employer data: " . print_r($employer, true));
    
    echo "<div class='employer-profile'>";
    echo "<div class='profile-header'>";
    if (!empty($employer['profile_image'])) {
        echo "<img src='" . htmlspecialchars($employer['profile_image']) . "' class='profile-image' alt='Profile'>";
    } else {
        echo "<div class='profile-image-placeholder'><i class='fas fa-building'></i></div>";
    }
    echo "<h2>" . htmlspecialchars($employer['company_name']) . "</h2>";
    echo "</div>";
    
    echo "<div class='profile-details'>";
    echo "<div class='detail-item'>";
    echo "<i class='fas fa-envelope'></i>";
    echo "<span>" . htmlspecialchars($employer['email']) . "</span>";
    echo "</div>";
    
    echo "<div class='detail-item'>";
    echo "<i class='fas fa-phone'></i>";
    echo "<span>" . htmlspecialchars($employer['phone_number']) . "</span>";
    echo "</div>";
    
    echo "<div class='detail-item'>";
    echo "<i class='fas fa-map-marker-alt'></i>";
    echo "<span>" . htmlspecialchars($employer['location']) . "</span>";
    echo "</div>";
    echo "</div>";
    
    echo "<div class='employer-rating'>";
    echo "<div class='rating-stars'>";
    
    $rating = round($employer['avg_rating']);
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $rating) {
            echo "<i class='fas fa-star'></i>";
        } else {
            echo "<i class='far fa-star'></i>";
        }
    }
    
    echo "</div>";
    echo "<div class='rating-info'>";
    echo "<span class='rating-value'>" . number_format($employer['avg_rating'], 1) . "</span>";
    echo "<span class='rating-separator'>/</span>";
    echo "<span class='rating-max'>5</span>";
    echo "<span class='rating-count'>(" . $employer['total_reviews'] . " reviews)</span>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employer Profile - FlexiHire</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="fetch_employer_rating.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Employer Profile</h1>
        </div>
        <?php
        include '../database/connectdatabase.php';
        $dbname = "project";
        mysqli_select_db($conn, $dbname);

        if (isset($_GET['employer_id'])) {
            $employer_id = $_GET['employer_id'];
            $employer = getEmployerDetails($conn, $employer_id);
            
            if ($employer) {
                displayEmployerProfile($employer);
            } else {
                echo "<div class='no-ratings'>";
                echo "<i class='far fa-building'></i>";
                echo "<p>Employer profile not found.</p>";
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
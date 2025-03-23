<?php
    session_start();
    include '../database/connectdatabase.php';
    $dbname = "project";
    mysqli_select_db($conn, $dbname);

    // Debug: Print session information
    echo "<!-- Debug: Session user_id: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'Not set') . " -->";

    // Checking if user is logged in
    if (!isset($_SESSION['user_id'])) 
    {
        header("Location: ../login/loginvalidation.php");
        exit();
    }

    $user_id = $_SESSION['user_id'];

    // Fetch accepted jobs with their details
    $sql = "SELECT j.job_id, j.job_title, j.employer_id, e.company_name, e.profile_image 
            FROM tbl_applications a 
            JOIN tbl_jobs j ON a.job_id = j.job_id 
            JOIN tbl_employer e ON j.employer_id = e.employer_id 
            WHERE a.user_id = '$user_id' AND a.status = 'accepted'";
    
    echo "<!-- Debug: SQL Query: " . $sql . " -->";

    $result = mysqli_query($conn, $sql);
    $accepted_jobs = [];

    if (!$result) {
        echo "<!-- Debug: MySQL Error: " . mysqli_error($conn) . " -->";
    }

    while ($row = mysqli_fetch_assoc($result)) 
    {
        $accepted_jobs[] = $row;
        echo "<!-- Debug: Processing job: " . print_r($row, true) . " -->";
    }

    echo "<!-- Debug: Accepted Jobs Count: " . count($accepted_jobs) . " -->";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employer Reviews</title>
    <link rel="stylesheet" href="reviews.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="reviews.js"></script>
</head>
<body>
    <div class="container">
        <h1>Review Your Accepted Jobs</h1>
        
        <?php if (empty($accepted_jobs)): ?>
            <div class="no-employers">
                <i class="fas fa-briefcase"></i>
                <p>No accepted jobs found to review.</p>
            </div>
        <?php else: ?>
            <div class="employers-list">
                <?php foreach ($accepted_jobs as $job): ?>
                    <div class="employer-card">
                        <div class="employer-info">
                            <h3><?php echo htmlspecialchars($job['job_title']); ?></h3>
                            <p class="employer-id">Company: <?php echo htmlspecialchars($job['company_name']); ?></p>
                        </div>
                        <div class="employer-actions">
                            <a href="jobdetails.php?job_id=<?php echo $job['job_id']; ?>" class="view-details-btn">
                                <i class="fas fa-info-circle"></i> View Job Details
                            </a>
                            <a href="employer_profile.php?employer_id=<?php echo $job['employer_id']; ?>" class="view-details-btn">
                                <i class="fas fa-user-tie"></i> Employer Details
                            </a>
                            <button class="add-review-btn" data-job-id="<?php echo $job['job_id']; ?>">
                                <i class="fas fa-star"></i> Rate Job
                            </button>
                        </div>
                    </div>

                    <!-- Hidden Review Form -->
                    <div class="review-form-container" id="review-form-<?php echo $job['job_id']; ?>" style="display: none;">
                        <form action="submit_review.php" method="POST" class="review-form">
                            <input type="hidden" name="job_id" value="<?php echo $job['job_id']; ?>">
                            <input type="hidden" name="employer_id" value="<?php echo $job['employer_id']; ?>">
                            
                            <div class="form-group">
                                <label>Rating</label>
                                <div class="rating-input">
                                    <label for="star5-<?php echo $job['job_id']; ?>">★</label>
                                    <input type="radio" name="rating" id="star5-<?php echo $job['job_id']; ?>" value="5">

                                    <label for="star4-<?php echo $job['job_id']; ?>">★</label>
                                    <input type="radio" name="rating" id="star4-<?php echo $job['job_id']; ?>" value="4">

                                    <label for="star3-<?php echo $job['job_id']; ?>">★</label>
                                    <input type="radio" name="rating" id="star3-<?php echo $job['job_id']; ?>" value="3">

                                    <label for="star2-<?php echo $job['job_id']; ?>">★</label>
                                    <input type="radio" name="rating" id="star2-<?php echo $job['job_id']; ?>" value="2">

                                    <label for="star1-<?php echo $job['job_id']; ?>">★</label>
                                    <input type="radio" name="rating" id="star1-<?php echo $job['job_id']; ?>" value="1">
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="review">Review:</label>
                                <textarea name="review" id="review-<?php echo $job['job_id']; ?>" required></textarea>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="submit-btn">Submit Review</button>
                                <button type="button" class="cancel-btn" data-job-id="<?php echo $job['job_id']; ?>">Cancel</button>
                            </div>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        $(document).ready(function() {
            // Show review form when Add Review button is clicked
            $('.add-review-btn').click(function() {
                const jobId = $(this).data('job-id');
                $(`#review-form-${jobId}`).slideDown();
            });

            // Hide review form when Cancel button is clicked
            $('.cancel-btn').click(function() {
                const jobId = $(this).data('job-id');
                $(`#review-form-${jobId}`).slideUp();
            });

            // Handle star rating selection
            $(".rating-input input").click(function () {
                var jobId = $(this).attr("id").split("-")[1]; // Get job ID
                var rating = $(this).val(); // Get selected rating
                
                // Remove 'selected' class from all labels for this job
                $(`#review-form-${jobId} .rating-input label`).removeClass("selected");

                // Add 'selected' class to the clicked star and all previous ones
                $(this).nextAll("label").addClass("selected");
                $(this).prev("label").addClass("selected");
                $(this).addClass("selected");
            });
        });
    </script>
</body>
</html>
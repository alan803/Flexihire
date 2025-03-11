<?php
    session_start();
    
    // Check if user is logged in
    if (!isset($_SESSION['employer_id'])) {
        // Redirect to login page if not logged in
        header("Location: ../login/loginvalidation.php");
        exit();
    }

    include '../database/connectdatabase.php';
    $dbname = "project";
    mysqli_select_db($conn, $dbname);
    
    $employer_id = $_SESSION['employer_id'];

    // Use prepared statement to prevent SQL injection
    $sql = "SELECT * FROM tbl_employer WHERE employer_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $employer_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (!$result) {
        die("Query failed: " . mysqli_error($conn));
    }
    
    $row = mysqli_fetch_assoc($result);
    
    if (!$row) {
        die("No employer found with ID: " . $employer_id);
    }
    
    // Fetch email from tbl_login
    $sql_email = "SELECT email FROM tbl_login WHERE user_id = ?";
    $stmt_email = mysqli_prepare($conn, $sql_email);
    mysqli_stmt_bind_param($stmt_email, "i", $employer_id);
    mysqli_stmt_execute($stmt_email);
    $result_email = mysqli_stmt_get_result($stmt_email);
    $row_email = mysqli_fetch_assoc($result_email);
    $email = $row_email['email'];
    
    $company_name = $row['company_name'];

    // Check if row data exists
    if ($row) {
        echo "<!-- Debug: Location value = " . htmlspecialchars($row['location']) . " -->";
    } else {
        echo "<!-- Debug: No data found for employer_id = $employer_id -->";
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employer Profile</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="employer_profile.css">
</head>
<body>
    <div id="notification" class="notification <?php echo isset($_SESSION['update_success']) ? 'show' : ''; ?>">
        <div class="notification-content">
            <i class="fas fa-check-circle"></i>
            <span>Profile Updated Successfully!</span>
        </div>
    </div>

    <div class="sidebar">
        <div class="logo-container">
            <?php if(!empty($row['profile_image'])): ?>
                <img src="<?php echo htmlspecialchars($row['profile_image']); ?>" 
                     alt="<?php echo htmlspecialchars($company_name); ?>"
                     onerror="this.src='company-logo.png';">
            <?php else: ?>
                <img src="company-logo.png" alt="AutoRecruits.in">
            <?php endif; ?>
        </div>
        <div class="company-info">
            <span style="position:relative; left:10px;"><?php echo htmlspecialchars($company_name); ?></span>
        </div>
        <nav class="nav-menu">
            <div class="nav-item active">
                <i class="fas fa-th-large"></i>
                <a href="employerdashboard.php">Home</a>
            </div>
            <div class="nav-item">
                <i class="fas fa-plus-circle"></i>
                <a href="postjob.php">Post a Job</a>
            </div>
            <div class="nav-item">
                <i class="fas fa-briefcase"></i>
                <a href="myjoblist.php">My Jobs</a>
            </div>
            <div class="nav-item">
                <i class="fas fa-calendar-check"></i>
                <a>Interviews</a>
            </div>
        </nav>
        <div class="settings-section">
            <div class="nav-item">
                <i class="fas fa-user"></i>
                <a href="employer_profile.php">My Profile</a>
            </div>
            <div class="nav-item">
                <i class="fas fa-sign-out-alt"></i>
                <a href="../login/logout.php">Logout</a>
            </div>
        </div>
    </div>
    
    <div class="main-content">
        <!-- Header Section -->
        <div class="profile-header">
            <div class="header-banner"></div>
            <div class="profile-info-container">
                <div class="profile-photo">
                    <img src="<?php echo !empty($row['profile_image']) ? htmlspecialchars($row['profile_image']) : 'company-logo.png'; ?>" 
                         alt="Company Logo"
                         onerror="this.src='company-logo.png';">
                </div>
                <div class="profile-details">
                    <h1 class="company-name"><?php echo htmlspecialchars($row['company_name']); ?></h1>
                    <!-- <p class="company-type"><?php echo htmlspecialchars($row['company_type']); ?></p> -->
                    <p class="company-type">Company Type</p>
                    <div class="company-meta">
                        <span><i class="fas fa-building"></i> Reg: <?php echo htmlspecialchars($row['registration_number']); ?></span>
                        <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($row['location']); ?></span>
                        <span><i class="fas fa-calendar"></i> Est. <?php echo htmlspecialchars($row['establishment_year']); ?></span>
                        <span><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($email); ?></span>
                        <span><i class="fas fa-map"></i> <?php echo htmlspecialchars($row['address']); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content Grid -->
        <div class="content-grid">
            <!-- Contact Information Card -->
            <div class="info-card">
                <h3><i class="fas fa-address-card"></i> Contact Information</h3>
                <div class="contact-info">
                    <div class="info-group">
                        <label>Contact Person</label>
                        <p><?php echo htmlspecialchars($row['contact_person']); ?></p>
                    </div>
                    <div class="info-group">
                        <label>Phone Number</label>
                        <p><?php echo htmlspecialchars($row['phone_number']); ?></p>
                    </div>
                    <div class="info-group">
                        <label>Email</label>
                        <p><?php echo htmlspecialchars($email); ?></p>
                    </div>
                    <div class="info-group">
                        <label>Address</label>
                        <p><?php echo htmlspecialchars($row['address']); ?></p>
                    </div>
                </div>
            </div>

            <!-- About Company Card -->
            <div class="info-card">
                <h3><i class="fas fa-building"></i> About Company</h3>
                <div class="description">
                    <?php echo htmlspecialchars($row['shop_description']); ?>
                </div>
            </div>

            <!-- Company Details Card -->
            <div class="info-card">
                <h3><i class="fas fa-info-circle"></i> Company Details</h3>
                <div class="details-grid">
                    <div class="info-group">
                        <label>Industry</label>
                        <p><?php echo htmlspecialchars($row['details']); ?></p>
                    </div>
                    <div class="info-group">
                        <label>Registration</label>
                        <p><?php echo htmlspecialchars($row['registration_number']); ?></p>
                    </div>
                    <div class="info-group">
                        <label>Established</label>
                        <p><?php echo htmlspecialchars($row['establishment_year']); ?></p>
                    </div>
                </div>
            </div>

            <!-- Edit Profile Button -->
            <a href="edit_employer_profile.php" class="edit-profile-btn">
                <i class="fas fa-edit"></i> Edit Profile
            </a>
        </div>
    </div>
    <script>
        // Animation for elements when they come into view
        document.addEventListener('DOMContentLoaded', function() {
            // Animate Play Button
            const playButton = document.querySelector('.play-button');
            setInterval(() => {
                playButton.style.transform = 'translate(-50%, -50%) scale(1.1)';
                setTimeout(() => {
                    playButton.style.transform = 'translate(-50%, -50%) scale(1)';
                }, 500);
            }, 2000);
            
            // Animate Calculator Dots
            const dots = document.querySelectorAll('.dot');
            let currentActive = 0;
            
            setInterval(() => {
                dots.forEach(dot => dot.classList.remove('active'));
                currentActive = (currentActive + 1) % dots.length;
                dots[currentActive].classList.add('active');
            }, 1500);

            const notification = document.getElementById('notification');
            
            if(notification && notification.classList.contains('show')) {
                // Show notification
                notification.style.display = 'block';
                
                // Remove notification after 3 seconds
                setTimeout(() => {
                    // First fade out
                    notification.style.animation = 'fadeOut 0.5s ease-out forwards';
                    
                    // Then remove from DOM after animation completes
                    setTimeout(() => {
                        notification.remove();
                    }, 500);
                }, 3000);
            }
        });
    </script>
</body>
</html>

<?php
// Clear the session variable after displaying
if(isset($_SESSION['update_success'])) {
    unset($_SESSION['update_success']);
}
?>
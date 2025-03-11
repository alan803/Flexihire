<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) 
{
    header("Location: ../login/loginvalidation.php");
    exit();
}

include '../database/connectdatabase.php';
$dbname = "project";
mysqli_select_db($conn, $dbname);

// Clear any stored username from session to ensure fresh data
unset($_SESSION['display_name']);

// Get user data with updated query
$user_id = $_SESSION['user_id'];
$sql = "SELECT l.email, u.first_name, u.last_name, u.username, u.profile_image, u.user_id
        FROM tbl_login l
        INNER JOIN tbl_user u ON l.user_id = u.user_id
        WHERE l.user_id = ?";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($result && mysqli_num_rows($result) > 0) {
    $user_data = mysqli_fetch_assoc($result);
    $email = $user_data['email'];
    
    // Force database refresh for display name
    $display_name = !empty($user_data['username']) ? $user_data['username'] : 
                   $user_data['first_name'] . " " . $user_data['last_name'];
    
    $profile_image = $user_data['profile_image'];
    $employer_id = $user_data['user_id'];
    
    // Update session with new display name
    $_SESSION['display_name'] = $display_name;
} else {
    error_log("Database error or user not found for ID: $user_id");
    session_destroy();
    header("Location: ../login/loginvalidation.php");
    exit();
}

// Debug output (remove in production)
// error_log("Display Name: " . $display_name);
// error_log("User ID: " . $user_id);

// Fetch active jobs
$sql_fetch = "SELECT job_id, job_title, job_description, location,town, salary, vacancy_date,created_at FROM tbl_jobs WHERE is_deleted = 0";
$result = mysqli_query($conn, $sql_fetch);  // Use direct query instead of prepare statement
// $row=mysqli_fetch_assoc($result);
// $job_id=$row['job_id'];
// $_SESSION['job_id']=$job_id;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
    <link rel="stylesheet" href="userdashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="nav-brand">
            <img src="logowithoutbcakground.png" alt="Logo" class="logo">
            <h1>FlexiHire</h1>
        </div>
        
        <div class="nav-right">
            <div class="profile-container">
                <?php if (!empty($profile_image)): ?>
                    <img src="/mini project/database/profile_picture/<?php echo htmlspecialchars($profile_image); ?>" class="profile-pic" alt="Profile">
                <?php else: ?>
                    <img src="profile.png" class="profile-pic" alt="Profile">
                <?php endif; ?>
                <div class="dropdown-menu">
                    <div class="user-info">
                        <span class="username"><?php echo htmlspecialchars($display_name); ?></span>
                        <!-- <span class="email"><?php echo $email; ?></span> -->
                    </div>
                    <div class="dropdown-divider"></div>
                    <a href="profiles/user/userprofile.php"><i class="fas fa-user"></i> Profile</a>
                    <a href="../login/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-menu">
                <a href="userdashboard.php" class="active"><i class="fas fa-list"></i> Job List</a>
                <a href="sidebar/jobgrid/jobgrid.html"><i class="fas fa-th"></i>Job Grid</a>
                <a href="sidebar/applyjob/applyjob.html"><i class="fas fa-paper-plane"></i> Apply Job</a>
                <!-- <a href="sidebar/jobdetails/jobdetails.html"><i class="fas fa-info-circle"></i> Job Details</a> -->
                <a href="sidebar/jobcategory/jobcategory.html"><i class="fas fa-tags"></i> Job Category</a>
                <a href="sidebar/appointment/appointment.html"><i class="fas fa-calendar"></i> Appointments</a>
                <a href="profiles/user/userprofile.php"><i class="fas fa-user"></i> Profile</a>
            </div>
            <div class="logout-container">
                <div class="sidebar-divider"></div>
                <a href="../login/logout.php" class="logout-link">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </aside>

        <!-- Main Content Area -->
        <main class="main-content">
            <!-- Search Bar -->
            <div class="search-container">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search jobs..." id="search">
                </div>
                <div class="filter-box">
                    <input type="text" placeholder="Location" id="location">
                    <div class="salary-range">
                        <input type="number" placeholder="Min Salary" id="minsalary">
                        <input type="number" placeholder="Max Salary" id="maxsalary">
                    </div>
                    <input type="date" id="date">
                </div>
            </div>

            <!-- Job Listings -->
            <div class="job-listings">
                <?php
                if ($result && mysqli_num_rows($result) > 0) 
                {
                    while ($row = mysqli_fetch_assoc($result)) 
                    {
                        $job_id=$row['job_id'];
                        $_SESSION['job_id']=$job_id;
                        echo '<div class="job-card">';
                        echo '<div class="job-header">';
                        echo '<h3>' . htmlspecialchars($row['job_title']) . '</h3>';
                        echo '<span class="salary">₹' . htmlspecialchars($row['salary']) . '</span>';
                        echo '</div>';
                        echo '<div class="job-body">';
                        echo '<p class="description">' . htmlspecialchars($row['job_description']) . '</p>';
                        echo '<p class="location"><i class="fas fa-map-marker-alt"></i> ' . htmlspecialchars($row['location']) . ',' . htmlspecialchars($row['town']) . '</p>';
                        echo '<p class="date"><i class="fas fa-calendar-plus"></i> Posted: ' . date('Y-m-d', strtotime($row['created_at'])) . '</p>';
                        echo '<p class="date"><i class="fas fa-calendar-alt"></i> Valid Until: ' . date('Y-m-d', strtotime($row['vacancy_date'])) . '</p>';
                        echo '</div>';
                        echo '<div class="job-footer">';
                        echo '<a href="jobdetails.php?job_id=' . $row['job_id'] . '" class="details-btn"><i class="fas fa-info-circle"></i> Details</a>';
                        echo '<button class="apply-btn"><i class="fas fa-paper-plane"></i> Apply Now</button>';
                        echo '</div>';
                        echo '</div>';
                    }
                } 
                else 
                {
                    echo '<div class="no-jobs">No active jobs available.</div>';
                }
                ?>
            </div>
        </main>
    </div>

    <script src="userdashboard.js"></script>
</body>
</html>

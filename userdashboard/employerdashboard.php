<?php
session_start();
if (!isset($_SESSION['employer_id'])) {
    header("Location: ../login/loginvalidation.php");
    exit();
}

include '../database/connectdatabase.php';
$dbname = "project";
mysqli_select_db($conn, $dbname);

// Your existing code to fetch employer data...
$employer_id = $_SESSION['employer_id'];
$sql = "SELECT u.company_name, l.email, u.profile_image 
        FROM tbl_login AS l
        JOIN tbl_employer AS u ON l.employer_id = u.employer_id
        WHERE u.employer_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $employer_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($result && mysqli_num_rows($result) > 0) {
    $employer_data = mysqli_fetch_array($result);
    $email = $employer_data['email'];
    $username = $employer_data['company_name'];
    $profile_image = $employer_data['profile_image'];
} else {
    error_log("Database error or user not found for ID: $employer_id");
    session_destroy();
    header("Location: ../login/logout.php");
    exit();
}
$sql_fetch = "SELECT j.*, 
              COUNT(CASE WHEN a.status = 'applied' THEN 1 END) AS applied_count,
              COUNT(CASE WHEN a.status = 'accepted' THEN 1 END) AS accepted_count,
              COUNT(CASE WHEN a.status = 'rejected' THEN 1 END) AS rejected_count,
              COUNT(a.id) AS total_applicants
              FROM tbl_jobs j
              LEFT JOIN tbl_applications a ON j.job_id = a.job_id
              WHERE j.employer_id = ? AND j.is_deleted = 0
              GROUP BY j.job_id";
$stmt_fetch = mysqli_prepare($conn, $sql_fetch);
mysqli_stmt_bind_param($stmt_fetch, "i", $employer_id);
mysqli_stmt_execute($stmt_fetch);
$result_fetch = mysqli_stmt_get_result($stmt_fetch);
mysqli_stmt_close($stmt_fetch);

$sql_count = "SELECT COUNT(*) AS active_jobs FROM tbl_jobs WHERE employer_id = ? AND is_deleted = 0";
$stmt_count = mysqli_prepare($conn, $sql_count);
mysqli_stmt_bind_param($stmt_count, "i", $employer_id);
mysqli_stmt_execute($stmt_count);
$result_count = mysqli_stmt_get_result($stmt_count);
$row = mysqli_fetch_assoc($result_count);
$active_jobs = $row ? $row['active_jobs'] : 0;
mysqli_stmt_close($stmt_count);

$sql_profile = "SELECT profile_image FROM tbl_employer WHERE employer_id = ?";
$stmt_profile = mysqli_prepare($conn, $sql_profile);
mysqli_stmt_bind_param($stmt_profile, "i", $employer_id);
mysqli_stmt_execute($stmt_profile);
$result_profile = mysqli_stmt_get_result($stmt_profile);
$profile_data = mysqli_fetch_assoc($result_profile);
$profile_image_path = !empty($profile_data['profile_image']) && file_exists("../database/profile_picture/" . $profile_data['profile_image'])
    ? "/mini project/database/profile_picture/" . htmlspecialchars($profile_data['profile_image'])
    : "../assets/images/company-logo.png";

$sql_applicants = "SELECT COUNT(DISTINCT a.id) as total_applicants 
                   FROM tbl_applications a 
                   JOIN tbl_jobs j ON a.job_id = j.job_id 
                   WHERE j.employer_id = ? AND j.is_deleted = 0";
$stmt_applicants = mysqli_prepare($conn, $sql_applicants);
mysqli_stmt_bind_param($stmt_applicants, "i", $employer_id);
mysqli_stmt_execute($stmt_applicants);
$result_applicants = mysqli_stmt_get_result($stmt_applicants);
$applicants_data = mysqli_fetch_assoc($result_applicants);
$total_applicants = $applicants_data ? $applicants_data['total_applicants'] : 0;
mysqli_stmt_close($stmt_applicants);

$sql_total_applications = "SELECT COUNT(*) as total_applications 
                          FROM tbl_applications a 
                          JOIN tbl_jobs j ON a.job_id = j.job_id 
                          WHERE j.employer_id = ? AND j.is_deleted = 0";
$stmt_total_applications = mysqli_prepare($conn, $sql_total_applications);
mysqli_stmt_bind_param($stmt_total_applications, "i", $employer_id);
mysqli_stmt_execute($stmt_total_applications);
$result_total_applications = mysqli_stmt_get_result($stmt_total_applications);
$total_applications_data = mysqli_fetch_assoc($result_total_applications);
$total_applications = $total_applications_data ? $total_applications_data['total_applications'] : 0;
mysqli_stmt_close($stmt_total_applications);

$sql_active_applications = "SELECT COUNT(*) as active_applications 
                           FROM tbl_applications a 
                           JOIN tbl_jobs j ON a.job_id = j.job_id 
                           WHERE j.employer_id = ? AND j.is_deleted = 0 
                           AND a.status = 'applied'";
$stmt_active_applications = mysqli_prepare($conn, $sql_active_applications);
mysqli_stmt_bind_param($stmt_active_applications, "i", $employer_id);
mysqli_stmt_execute($stmt_active_applications);
$result_active_applications = mysqli_stmt_get_result($stmt_active_applications);
$active_applications_data = mysqli_fetch_assoc($result_active_applications);
$active_applications = $active_applications_data ? $active_applications_data['active_applications'] : 0;
mysqli_stmt_close($stmt_active_applications);

$sql_hired_users = "SELECT COUNT(DISTINCT a.id) as hired_users 
                    FROM tbl_applications a 
                    JOIN tbl_jobs j ON a.job_id = j.job_id 
                    WHERE j.employer_id = ? AND j.is_deleted = 0 
                    AND a.status = 'accepted'";
$stmt_hired_users = mysqli_prepare($conn, $sql_hired_users);
mysqli_stmt_bind_param($stmt_hired_users, "i", $employer_id);
mysqli_stmt_execute($stmt_hired_users);
$result_hired_users = mysqli_stmt_get_result($stmt_hired_users);
$hired_users_data = mysqli_fetch_assoc($result_hired_users);
$hired_users = $hired_users_data ? $hired_users_data['hired_users'] : 0;
mysqli_stmt_close($stmt_hired_users);

$sql_pending_interviews = "SELECT COUNT(*) as pending_interviews 
                          FROM tbl_appointments 
                          WHERE employer_id = ? 
                          AND status = 'Pending'";
$stmt_pending_interviews = mysqli_prepare($conn, $sql_pending_interviews);
mysqli_stmt_bind_param($stmt_pending_interviews, "i", $employer_id);
mysqli_stmt_execute($stmt_pending_interviews);
$result_pending_interviews = mysqli_stmt_get_result($stmt_pending_interviews);
$pending_interviews_data = mysqli_fetch_assoc($result_pending_interviews);
$pending_interviews = $pending_interviews_data ? $pending_interviews_data['pending_interviews'] : 0;
mysqli_stmt_close($stmt_pending_interviews);

$sql_recent_applicants = "SELECT a.id, a.status, a.applied_at, 
                         u.first_name, u.last_name, u.profile_image,
                         j.job_title
                         FROM tbl_applications a 
                         JOIN tbl_user u ON a.user_id = u.user_id 
                         JOIN tbl_jobs j ON a.job_id = j.job_id 
                         WHERE j.employer_id = ? AND j.is_deleted = 0 
                         ORDER BY a.applied_at DESC 
                         LIMIT 5";
$stmt_recent_applicants = mysqli_prepare($conn, $sql_recent_applicants);
mysqli_stmt_bind_param($stmt_recent_applicants, "i", $employer_id);
mysqli_stmt_execute($stmt_recent_applicants);
$result_recent_applicants = mysqli_stmt_get_result($stmt_recent_applicants);
mysqli_stmt_close($stmt_recent_applicants);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employer Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="employerdashboard.css">
</head>
<body>
    <!-- <div class="dashboard-container">
        <div class="sidebar">
            <div class="logo-container">
                <?php if(!empty($profile_image)): ?>
                    <img src="<?php echo htmlspecialchars($profile_image); ?>" 
                         alt="<?php echo htmlspecialchars($username); ?>"
                         onerror="this.src='../assets/images/company-logo.png';">
                <?php else: ?>
                    <img src="../assets/images/company-logo.png" alt="AutoRecruits.in">
                <?php endif; ?>
            </div>
            <div class="company-info">
                <span><?php echo htmlspecialchars($username); ?></span>
                <span><?php echo htmlspecialchars($email); ?></span>
            </div>
            <nav class="nav-menu">
                <div class="nav-item active"><i class="fas fa-th-large"></i><a href="employerdashboard.php">Dashboard</a></div>
                <div class="nav-item"><i class="fas fa-plus-circle"></i><a href="postjob.php">Post a Job</a></div>
                <div class="nav-item"><i class="fas fa-briefcase"></i><a href="myjoblist.php">My Jobs</a></div>
                <div class="nav-item"><i class="fas fa-users"></i><a href="applicants.php">Applicants</a></div>
                <div class="nav-item"><i class="fas fa-calendar-check"></i><a href="interviews.php">Interviews</a></div>
            </nav>
            <div class="settings-section">
                <div class="nav-item"><i class="fas fa-user-cog"></i><a href="employer_profile.php">My Profile</a></div>
                <div class="nav-item"><i class="fas fa-sign-out-alt"></i><a href="../login/logout.php">Logout</a></div>
            </div>
        </div> -->
        <?php include 'sidebar.php'; ?>

        <div class="main-container">
            <div class="main-content">
                <div class="header">
                    <h1>Welcome, <?php echo htmlspecialchars($username); ?></h1>
                    <div class="header-actions">
                        <button id="applicantsToggle" class="toggle-sidebar-btn"><i class="fas fa-users"></i> Applicants</button>
                        <button class="post-job-btn"><i class="fas fa-plus-circle"></i><a href="postjob.php">Post a Job</a></button>
                    </div>
                </div>

                <div class="stats-grid">
                    <div class="stat-card"><i class="fas fa-briefcase"></i><div><span><?php echo $active_jobs; ?></span><p>Active Jobs</p></div></div>
                    <div class="stat-card"><i class="fas fa-file-alt"></i><div><span><?php echo $total_applications; ?></span><p>Total Applications</p></div></div>
                    <div class="stat-card"><i class="fas fa-user-check"></i><div><span><?php echo $hired_users; ?></span><p>Hired Users</p></div></div>
                    <div class="stat-card"><i class="fas fa-calendar-check"></i><div><span><?php echo $pending_interviews; ?></span><p>Pending Interviews</p></div></div>
                </div>

                <div class="jobs-section">
                    <div class="search-container">
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" placeholder="Search jobs..." id="search" name="search" oninput="filterjobs()">
                            <button class="reset-button" onclick="resetFilters()" title="Reset Search">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                    <h2><i class="fas fa-list"></i> Recent Job Postings</h2>
                    <div class="job-card-container">
                        <?php if (mysqli_num_rows($result_fetch) > 0): ?>
                            <?php while ($job_data = mysqli_fetch_array($result_fetch)): ?>
                                <div class="job-card">
                                    <div class="job-info">
                                        <div class="company-logo"><i class="fas fa-briefcase"></i></div>
                                        <div class="job-details">
                                            <h3><?php echo htmlspecialchars($job_data['job_title']); ?></h3>
                                            <div class="job-meta">
                                                <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($job_data['location']); ?></span>
                                                <span><i class="fas fa-calendar-alt"></i> <?php echo htmlspecialchars($job_data['vacancy_date']); ?></span>
                                                <span><i class="fas fa-rupee-sign"></i> <?php echo htmlspecialchars($job_data['salary']); ?></span>
                                            </div>
                                            <p class="job-description"><?php echo substr(htmlspecialchars($job_data['job_description']), 0, 150) . '...'; ?></p>
                                            <div class="job-stats">
                                                <span><i class="fas fa-users"></i> <?php echo $job_data['total_applicants']; ?> Applicants</span>
                                                <span><i class="fas fa-check-circle"></i> <?php echo $job_data['accepted_count']; ?> Accepted</span>
                                                <span><i class="fas fa-times-circle"></i> <?php echo $job_data['rejected_count']; ?> Rejected</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="job-actions">
                                        <a href="editjob.php?job_id=<?php echo $job_data['job_id']; ?>" class="action-btn edit-btn"><i class="fas fa-edit"></i> Edit</a>
                                        <!-- <a href="deletejob.php?id=<?php echo $job_data['job_id']; ?>&action=deactivate" class="action-btn delete-btn" onclick="return confirm('Are you sure you want to delete this job?')"><i class="fas fa-trash-alt"></i> Deactivate</a> -->
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="no-jobs">
                                <i class="fas fa-briefcase"></i>
                                <h3>No jobs posted yet</h3>
                                <p>Start by posting your first job opening</p>
                                <!-- <button class="post-job-btn"><i class="fas fa-plus-circle"></i><a href="postjob.php">Post a Job</a></button> -->
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="applicants-sidebar" id="applicantsSidebar">
            <button id="closeSidebar"><i class="fas fa-times"></i></button>
            <h2>Recent Applicants <span class="applicant-count"><?php echo $total_applications; ?></span></h2>
            <div class="applicant-list">
                <?php if (mysqli_num_rows($result_recent_applicants) > 0): ?>
                    <?php while ($applicant = mysqli_fetch_assoc($result_recent_applicants)): ?>
                        <div class="applicant-group">
                            <h3><?php echo htmlspecialchars($applicant['job_title']); ?></h3>
                            <div class="applicant-card">
                                <div class="applicant-avatar">
                                    <?php if (!empty($applicant['profile_image'])): ?>
                                        <img src="/mini project/database/profile_picture/<?php echo htmlspecialchars($applicant['profile_image']); ?>" 
                                             alt="<?php echo htmlspecialchars($applicant['first_name'] . ' ' . $applicant['last_name']); ?>"
                                             onerror="this.style.display='none'; this.parentElement.innerHTML='<?php echo substr($applicant['first_name'], 0, 1) . substr($applicant['last_name'], 0, 1); ?>';">
                                    <?php else: ?>
                                        <?php echo substr($applicant['first_name'], 0, 1) . substr($applicant['last_name'], 0, 1); ?>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <p class="applicant-name"><?php echo htmlspecialchars($applicant['first_name'] . ' ' . $applicant['last_name']); ?></p>
                                    <p class="applicant-position"><?php echo htmlspecialchars($applicant['job_title']); ?></p>
                                    <p class="applicant-status">Status: <?php echo htmlspecialchars($applicant['status']); ?></p>
                                    <p class="applicant-date">Applied: <?php echo date('M d, Y', strtotime($applicant['applied_at'])); ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-applicants">
                        <i class="fas fa-users"></i>
                        <p>No recent applicants</p>
                    </div>
                <?php endif; ?>
                <a href="applicants.php">View All Applicants <i class="fas fa-arrow-right"></i></a>
            </div>
        </div>
    </div>
    <script src="employerdashboard.js"></script>
</body>
</html>
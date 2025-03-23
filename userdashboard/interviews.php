<?php
session_start();
include '../database/connectdatabase.php';
$dbname = "project";
mysqli_select_db($conn, $dbname);

if (!isset($_SESSION['employer_id'])) {
    header("Location: ../login/loginvalidation.php");
    exit();
}

$employer_id = $_SESSION['employer_id'];

// Get employer details
$sql = "SELECT e.*, l.email 
        FROM tbl_employer e 
        JOIN tbl_login l ON e.employer_id = l.employer_id 
        WHERE e.employer_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $employer_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);

// Get all interviews for this employer
$sql = "SELECT 
            a.*, 
            j.job_title,
            u.first_name,
            u.last_name,
            u.profile_image,
            CONCAT('../database/profile_picture/', u.profile_image) as profile_image_path
        FROM tbl_appointments a
        JOIN tbl_jobs j ON a.job_id = j.job_id
        JOIN tbl_user u ON a.user_id = u.user_id
        WHERE a.employer_id = ?
        ORDER BY a.appointment_date ASC, a.appointment_time ASC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $employer_id);
mysqli_stmt_execute($stmt);
$interviews = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scheduled Interviews</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="employerdashboard.css">
    <link rel="stylesheet" href="interview.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar (Copied from employerdashboard.php) -->
        <div class="sidebar">
            <div class="logo-container">
                <?php if(!empty($row['profile_image'])): ?>
                    <img src="<?php echo htmlspecialchars($row['profile_image']); ?>" 
                         alt="<?php echo htmlspecialchars($row['company_name']); ?>"
                         onerror="this.src='../assets/images/company-logo.png';">
                <?php else: ?>
                    <img src="../assets/images/company-logo.png" alt="AutoRecruits.in">
                <?php endif; ?>
            </div>
            <div class="company-info">
                <span><?php echo htmlspecialchars($row['company_name'] ?? 'Company Name'); ?></span>
                <span style="font-size: 13px; color: var(--light-text);"><?php echo htmlspecialchars($row['email'] ?? 'Email'); ?></span>
            </div>
            <nav class="nav-menu">
                <div class="nav-item">
                    <i class="fas fa-th-large"></i>
                    <a href="employerdashboard.php">Dashboard</a>
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
                    <i class="fas fa-users"></i>
                    <a href="applicants.php">Applicants</a>
                </div>
                <div class="nav-item active">
                    <i class="fas fa-calendar-check"></i>
                    <a href="interviews.php">Interviews</a>
                </div>
            </nav>
            <div class="settings-section">
                <div class="nav-item">
                    <i class="fas fa-user-cog"></i>
                    <a href="employer_profile.php">My Profile</a>
                </div>
                <div class="nav-item">
                    <i class="fas fa-sign-out-alt"></i>
                    <a href="../login/logout.php">Logout</a>
                </div>
            </div>
        </div>

        <!-- Main Content (Unchanged) -->
        <div class="main-container">
            <div class="interview-container">
                <h2>Scheduled Interviews</h2>

                <?php if(mysqli_num_rows($interviews) > 0): ?>
                    <?php while($interview = mysqli_fetch_assoc($interviews)): ?>
                        <div class="interview-card">
                            <div class="interview-header">
                                <div class="interview-title">
                                    Interview with <?php echo htmlspecialchars($interview['first_name'] . ' ' . $interview['last_name']); ?>
                                </div>
                                <div class="interview-date">
                                    <?php 
                                        $date = new DateTime($interview['appointment_date']);
                                        $time = new DateTime($interview['appointment_time']);
                                        echo $date->format('F j, Y') . ' at ' . $time->format('g:i A');
                                    ?>
                                </div>
                            </div>

                            <div class="interview-details">
                                <div class="detail-group">
                                    <span class="detail-label">Position</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($interview['job_title']); ?></span>
                                </div>

                                <div class="detail-group">
                                    <span class="detail-label">Interview Type</span>
                                    <span class="detail-value interview-type type-<?php echo strtolower($interview['interview_type']); ?>">
                                        <i class="fas fa-<?php 
                                            echo $interview['interview_type'] === 'Physical' ? 'building' : 
                                                ($interview['interview_type'] === 'Online' ? 'video' : 'phone'); 
                                        ?>"></i>
                                        <?php echo htmlspecialchars($interview['interview_type']); ?> Interview
                                    </span>
                                </div>

                                <div class="detail-group">
                                    <span class="detail-label">
                                        <?php 
                                            echo $interview['interview_type'] === 'Physical' ? 'Location' : 
                                                ($interview['interview_type'] === 'Online' ? 'Meeting Link' : 'Phone Number'); 
                                        ?>
                                    </span>
                                    <span class="detail-value"><?php echo htmlspecialchars($interview['location']); ?></span>
                                </div>

                                <div class="detail-group">
                                    <span class="detail-label">Status</span>
                                    <span class="status-badge status-<?php echo strtolower($interview['status']); ?>">
                                        <?php echo ucfirst(htmlspecialchars($interview['status'])); ?>
                                    </span>
                                </div>
                            </div>

                            <?php if(!empty($interview['notes'])): ?>
                                <div class="interview-notes">
                                    <div class="notes-label">Notes</div>
                                    <div class="notes-content"><?php echo nl2br(htmlspecialchars($interview['notes'])); ?></div>
                                </div>
                            <?php endif; ?>

                            <div class="interview-actions">
                                <?php if($interview['status'] === 'pending'): ?>
                                    <a href="reschedule_interview.php?appointment_id=<?php echo $interview['appointment_id']; ?>" 
                                       class="action-btn btn-reschedule">
                                        <i class="fas fa-calendar-alt"></i> Reschedule
                                    </a>
                                    <a href="cancel_interview.php?appointment_id=<?php echo $interview['appointment_id']; ?>" 
                                       class="action-btn btn-cancel"
                                       onclick="return confirm('Are you sure you want to cancel this interview?')">
                                        <i class="fas fa-times"></i> Cancel
                                    </a>
                                <?php endif; ?>
                                <?php if($interview['interview_type'] === 'Online' && $interview['status'] === 'pending'): ?>
                                    <a href="<?php echo htmlspecialchars($interview['location']); ?>" 
                                       target="_blank" 
                                       class="action-btn btn-join">
                                        <i class="fas fa-video"></i> Join Meeting
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-interviews">
                        <i class="fas fa-calendar-times"></i>
                        <p>No interviews scheduled yet</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
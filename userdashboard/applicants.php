<?php
    session_start();
    if (!isset($_SESSION['employer_id'])) 
    {
        header("Location: ../login/loginvalidation.php");
        exit();
    }

    include '../database/connectdatabase.php';
    $dbname = "project";
    mysqli_select_db($conn, $dbname);

    // Debug: Check employer_id
    $employer_id = $_SESSION['employer_id'];
    echo "<!-- Debug: employer_id = " . $employer_id . " -->";

    // Fetch employer details with error checking
    $sql = "SELECT e.*, l.email 
            FROM tbl_employer e 
            JOIN tbl_login l ON e.employer_id = l.employer_id 
            WHERE e.employer_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        echo "<!-- Debug: Prepare failed: " . mysqli_error($conn) . " -->";
    }
    mysqli_stmt_bind_param($stmt, "i", $employer_id);
    if (!mysqli_stmt_execute($stmt)) {
        echo "<!-- Debug: Execute failed: " . mysqli_stmt_error($stmt) . " -->";
    }
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);

    // Debug: Print row data
    echo "<!-- Debug: Row data = " . print_r($row, true) . " -->";

    // Get company name and email with default values
    $company_name = $row['company_name'] ?? 'Company Name Not Set';
    $email = $row['email'] ?? '';

    // Display error or success messages from accept.php
    if (isset($_GET['error']) && $_GET['error'] === 'vacancy_full') {
        $error_message = "<div id='error-message' style='color: red; padding: 10px; border: 1px solid red; margin: 10px; background-color: #ffe6e6;'>
                            Error: Cannot accept more applicants - the vacancy limit for this job has been reached!
                          </div>";
    }
    if (isset($_GET['success']) && $_GET['success'] === 'status_updated') {
        $success_message = "<div id='success-message' style='color: green; padding: 10px; border: 1px solid green; margin: 10px; background-color: #e6ffe6;'>
                              Application status updated successfully!
                            </div>";
    }

    // Get job_id from URL
    $job_id = isset($_GET['job_id']) ? $_GET['job_id'] : null;
    
    if (!$job_id) 
    {
        header("Location: myjoblist.php");
        exit();
    }

    // Fetch job details including license requirement
    $job_sql = "SELECT job_title, license_required FROM tbl_jobs WHERE job_id = ?";
    $stmt = mysqli_prepare($conn, $job_sql);
    mysqli_stmt_bind_param($stmt, "i", $job_id);
    mysqli_stmt_execute($stmt);
    $job_result = mysqli_stmt_get_result($stmt);
    $job_details = mysqli_fetch_assoc($job_result);

    // Updated SQL query to include both license_required and badge_required
    $sql = "SELECT 
                a.id,
                a.job_id,
                a.user_id,
                a.status,
                a.applied_at,
                u.first_name, 
                u.last_name, 
                u.profile_image, 
                u.phone_number, 
                l.email,
                j.employer_id,
                j.license_required,
                j.badge_required,
                j.interview,
                CONCAT('../database/profile_picture/', u.profile_image) as profile_image_path
            FROM tbl_applications a 
            JOIN tbl_user u ON a.user_id = u.user_id 
            JOIN tbl_login l ON u.user_id = l.user_id
            JOIN tbl_jobs j ON a.job_id = j.job_id
            WHERE a.job_id = ?";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $job_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    // Debug output
    if (mysqli_num_rows($result) > 0) 
    {
        $debug_applicant = mysqli_fetch_assoc($result);
        mysqli_data_seek($result, 0); // Reset pointer
        error_log("Debug - Application ID: " . $debug_applicant['id'] . ", Status: " . $debug_applicant['status']);
    }

    // Fetch all jobs posted by this employer
    $jobs_query = "SELECT j.*, 
                          COUNT(a.id) as application_count
                   FROM tbl_jobs j
                   LEFT JOIN tbl_applications a ON j.job_id = a.job_id
                   WHERE j.employer_id = ? AND j.is_deleted = 0
                   GROUP BY j.job_id";
    $stmt = mysqli_prepare($conn, $jobs_query);
    mysqli_stmt_bind_param($stmt, "i", $employer_id);
    mysqli_stmt_execute($stmt);
    $jobs_result = mysqli_stmt_get_result($stmt);

    // Get selected job_id from URL or first job
    $selected_job_id = isset($_GET['job_id']) ? $_GET['job_id'] : null;
    if (!$selected_job_id && $job_row = mysqli_fetch_assoc($jobs_result)) {
        $selected_job_id = $job_row['job_id'];
        mysqli_data_seek($jobs_result, 0); // Reset pointer
    }

    // Fetch applicants for the selected job
    $applicants_query = "SELECT a.*, 
                               u.first_name, 
                               u.last_name, 
                               u.phone_number,
                               u.profile_image,
                               l.email,
                               j.license_required,
                               j.badge_required,
                               j.interview,
                               CONCAT('../database/profile_picture/', u.profile_image) as profile_image_path
                        FROM tbl_applications a
                        JOIN tbl_user u ON a.user_id = u.user_id
                        JOIN tbl_login l ON u.user_id = l.user_id
                        JOIN tbl_jobs j ON a.job_id = j.job_id
                        WHERE a.job_id = ?
                        ORDER BY a.applied_at DESC";

    $stmt = mysqli_prepare($conn, $applicants_query);
    mysqli_stmt_bind_param($stmt, "i", $selected_job_id);
    mysqli_stmt_execute($stmt);
    $applicants_result = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Applicants</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="employerdashboard.css">
    <link rel="stylesheet" href="applicants.css">
    <style>
        /* Main container adjustments */
        .main-container {
            margin-left: 280px; /* Sidebar width */
            margin-top: 60px;  /* Navbar height */
            padding: 20px;
            min-height: calc(100vh - 60px);
            background: #f8f9fa;
        }

        /* Header container adjustments */
        .header-container {
            margin-bottom: 25px;
        }

        .header-top {
            margin-bottom: 15px;
        }

        /* Back button styling */
        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #4a90e2;
            text-decoration: none;
            font-size: 14px;
        }

        /* Success/Error message positioning */
        .success-message, 
        #error-message, 
        #success-message {
            margin-bottom: 20px;
        }

        /* Applicants grid adjustments */
        .applicants-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            padding: 20px 0;
        }

        /* Applicant card adjustments */
        .applicant-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        /* No applicants message */
        .no-applicants {
            background: white;
            border-radius: 12px;
            padding: 30px;
            text-align: center;
            margin-top: 20px;
        }

        .header {
            margin-bottom: 2rem;
        }

        .header h1 {
            font-size: 1.875rem;
            font-weight: 600;
            color: #1e293b;
        }

        .job-title {
            color: #3b82f6;
            font-size: 1.1rem;
            margin-top: 0.5rem;
        }

        .applicant-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .applicant-photo {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            overflow: hidden;
            border: 2px solid #e2e8f0;
        }

        .applicant-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .applicant-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1e293b;
        }

        .applicant-info {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #475569;
            font-size: 0.95rem;
        }

        .info-item i {
            color: #3b82f6;
            width: 20px;
        }

        .action-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 0.75rem;
            margin-top: 1.25rem;
        }

        .action-btn {
            flex: 1;
            padding: 0.75rem;
            border: none;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.2s ease;
        }

        .accept-btn {
            background-color: #10b981;
            color: white;
        }

        .accept-btn:hover {
            background-color: #059669;
        }

        .reject-btn {
            background-color: #ef4444;
            color: white;
        }

        .reject-btn:hover {
            background-color: #dc2626;
        }

        .status-badge {
            width: 100%;
            text-align: center;
            padding: 0.75rem;
            border-radius: 6px;
            font-weight: 500;
        }

        .status-badge.accepted {
            background-color: #dcfce7;
            color: #166534;
        }

        .status-badge.rejected {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .application-date {
            color: #64748b;
            font-size: 0.875rem;
            margin-top: 1rem;
            text-align: right;
        }

        .no-applicants i {
            font-size: 3rem;
            color: #94a3b8;
            margin-bottom: 1rem;
        }

        @media (max-width: 768px) {
            .main-container {
                margin-left: 0;
                padding: 1rem;
            }

            .applicants-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Remove the active element effect in the sidebar */
        .nav-item {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            color: var(--light-text);
            transition: all 0.3s ease;
            border-radius: 8px;
            margin-bottom: 5px;
            cursor: pointer;
            text-decoration: none;
        }

        .nav-item:hover {
            background-color: var(--primary-light);
            color: var(--primary-color);
        }

        .nav-item i {
            margin-right: 15px;
            font-size: 18px;
        }

        .nav-item a {
            color: inherit;
            text-decoration: none;
            display: flex;
            align-items: center;
            width: 100%;
        }

        /* Remove the active class styling */
        .nav-item.active {
            background-color: transparent;
            color: var(--light-text);
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <!-- <div class="sidebar">
        <div class="logo-container">
            <?php if(!empty($row['profile_image'])): ?>
                <img src="<?php echo htmlspecialchars($row['profile_image']); ?>" 
                     alt="<?php echo htmlspecialchars($company_name); ?>"
                     onerror="this.src='../assets/images/company-logo.png';">
            <?php else: ?>
                <img src="../assets/images/company-logo.png" alt="AutoRecruits.in">
            <?php endif; ?>
        </div>
        <div class="company-info">
            <span style="font-weight: 600; font-size: 16px; display: block; margin-bottom: 5px;">
                <?php echo htmlspecialchars($company_name); ?>
            </span>
            <span style="font-size: 13px; color: var(--light-text);">
                <?php echo htmlspecialchars($email); ?>
            </span>
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
            <div class="nav-item">
                <i class="fas fa-calendar-check"></i>
                <a href="interviews.php">Interviews</a>
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
    </div> -->
    <?php include 'sidebar.php'; ?>
    
    <div class="main-container">
        <?php if(isset($_GET['success']) && $_GET['success'] == 'interview_scheduled'): ?>
            <div class="success-message" id="success-message">
                <i class="fas fa-check-circle"></i>
                Interview scheduled successfully!
            </div>
        <?php endif; ?>
        <?php 
            // Display error or success messages before the header
            if (isset($error_message)) {
                echo $error_message;
            }
            if (isset($success_message)) {
                echo $success_message;
            }
        ?>
        <div class="header-container">
            <div class="header-top">
                <a href="myjoblist.php" class="back-button">
                    <i class="fas fa-arrow-left"></i>
                    Back to Job List
                </a>
            </div>
            
            <div class="header">
                <h1>Job Applicants</h1>
                <div class="job-title">
                    <i class="fas fa-briefcase"></i>
                    <?php echo htmlspecialchars($job_details['job_title'] ?? 'Unknown Job'); ?>
                </div>
            </div>
        </div>

        <!-- <div class="page-header">
            <div class="job-selector">
                <select id="jobSelect" onchange="window.location.href='?job_id=' + this.value">
                    <?php while ($job = mysqli_fetch_assoc($jobs_result)): ?>
                        <option value="<?php echo $job['job_id']; ?>" 
                                <?php echo ($selected_job_id == $job['job_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($job['job_title']); ?> 
                            (<?php echo $job['application_count']; ?> applicants)
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
        </div> -->

        <?php if (mysqli_num_rows($applicants_result) > 0): ?>
            <div class="applicants-grid">
                <?php while ($applicant = mysqli_fetch_assoc($applicants_result)): ?>
                    <div class="applicant-card">
                        <?php
                            $status = strtolower($applicant['status'] ?? 'pending');
                            $statusIcons = [
                                'pending' => 'clock',
                                'accepted' => 'check-circle',
                                'rejected' => 'times-circle',
                                'interview' => 'calendar-check'
                            ];
                        ?>
                        <!-- Status Badge -->
                        <!-- <div class="status-pill <?php echo htmlspecialchars($status); ?>">
                            <i class="fas fa-<?php echo $statusIcons[$status] ?? 'clock'; ?>"></i>
                            <?php echo ucfirst(htmlspecialchars($status)); ?>
                        </div> -->

                        <!-- Main Applicant Info -->
                        <div class="applicant-main">
                            <div class="applicant-photo">
                                <img src="<?php echo !empty($applicant['profile_image']) ? 
                                    htmlspecialchars($applicant['profile_image_path']) : 
                                    '../assets/images/default-user.png'; ?>" 
                                    alt="<?php echo htmlspecialchars($applicant['first_name']); ?>"
                                    onerror="this.src='../assets/images/default-user.png';">
                            </div>
                            
                            <div class="applicant-details">
                                <div class="applicant-name">
                                    <?php echo htmlspecialchars($applicant['first_name'] . ' ' . $applicant['last_name']); ?>
                                </div>
                                
                                <div class="contact-info">
                                    <div class="info-item">
                                        <i class="fas fa-envelope"></i>
                                        <span><?php echo htmlspecialchars($applicant['email']); ?></span>
                                    </div>
                                    <div class="info-item">
                                        <i class="fas fa-phone"></i>
                                        <span><?php echo htmlspecialchars($applicant['phone_number']); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <?php if ($status === 'pending' || empty($status)): ?>
                            <div class="action-buttons">
                                <?php if ($applicant['license_required'] == 'two_wheeler' || 
                                          $applicant['license_required'] == 'four_wheeler' || 
                                          $applicant['badge_required'] == 'yes'): ?>
                                    <a href="view_certificates.php?application_id=<?php echo $applicant['id']; ?>" 
                                       class="action-btn view-certificates-btn">
                                        <i class="fas fa-certificate"></i>
                                        View Certificates
                                    </a>
                                <?php endif; ?>

                                <?php if ($applicant['interview'] == 'yes'): ?>
                                    <a href="schedule_interview.php?job_id=<?php echo $applicant['job_id']; ?>&application_id=<?php echo $applicant['id']; ?>" 
                                       class="action-btn schedule-interview-btn">
                                        <i class="fas fa-calendar-check"></i>
                                        Schedule Interview
                                    </a>
                                <?php else: ?>
                                    <a href="accept.php?application_id=<?php echo $applicant['id']; ?>&status=accepted" 
                                       class="action-btn accept-btn">
                                        <i class="fas fa-check"></i>
                                        Accept
                                    </a>
                                    <a href="accept.php?application_id=<?php echo $applicant['id']; ?>&status=rejected" 
                                       class="action-btn reject-btn">
                                        <i class="fas fa-times"></i>
                                        Reject
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Status Display -->
                        <div class="status-display">
                            <i class="fas fa-info-circle"></i>
                            Current Status: <span class="status-text <?php echo $status; ?>">
                                <?php echo ucfirst(htmlspecialchars($status)); ?>
                            </span>
                        </div>

                        <!-- Application Date -->
                        <div class="application-date">
                            <i class="far fa-clock"></i>
                            Applied on <?php echo date('M d, Y', strtotime($applicant['applied_at'])); ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="no-applicants">
                <i class="fas fa-user-friends"></i>
                <h2>No Applicants Yet</h2>
                <p>There are currently no applicants for this job posting.</p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Function to remove messages and URL parameters after 4 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const errorMessage = document.getElementById('error-message');
            const successMessage = document.getElementById('success-message');

            if (errorMessage || successMessage) {
                setTimeout(() => {
                    // Remove messages with fade effect
                    if (errorMessage) {
                        errorMessage.style.transition = 'opacity 0.5s ease';
                        errorMessage.style.opacity = '0';
                        setTimeout(() => {
                            errorMessage.remove();
                        }, 500);
                    }
                    if (successMessage) {
                        successMessage.style.transition = 'opacity 0.5s ease';
                        successMessage.style.opacity = '0';
                        setTimeout(() => {
                            successMessage.remove();
                        }, 500);
                    }

                    // Remove URL parameters
                    const url = new URL(window.location.href);
                    url.searchParams.delete('error');
                    url.searchParams.delete('success');
                    window.history.replaceState({}, '', url);
                }, 4000);
            }
        });

        // Optional: Existing updateStatus function (unchanged)
        function updateStatus(applicationId, status) {
            if (!confirm(`Are you sure you want to ${status} this application?`)) {
                return;
            }

            fetch('update_application_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `application_id=${applicationId}&status=${status}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(`Application ${status} successfully`, 'success');
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    showToast(data.message || 'Error updating application', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Error updating application', 'error');
            });
        }
    </script>
</body>
</html>
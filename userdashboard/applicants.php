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

    // Get job_id from URL
    $job_id = isset($_GET['job_id']) ? $_GET['job_id'] : null;
    
    if (!$job_id) 
    {
        header("Location: myjoblist.php");
        exit();
    }

    // Fetch job details
    $job_sql = "SELECT job_title FROM tbl_jobs WHERE job_id = ?";
    $stmt = mysqli_prepare($conn, $job_sql);
    mysqli_stmt_bind_param($stmt, "i", $job_id);
    mysqli_stmt_execute($stmt);
    $job_result = mysqli_stmt_get_result($stmt);
    $job_details = mysqli_fetch_assoc($job_result);

    // Updated SQL query to include job_id and employer_id
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
                CONCAT('../database/profile_picture/', u.profile_image) as profile_image_path
            FROM tbl_applications a 
            JOIN tbl_user u ON a.user_id = u.user_id 
            JOIN tbl_login l ON u.user_id = l.login_id 
            JOIN tbl_jobs j ON a.job_id = j.job_id
            WHERE a.job_id = ?";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $job_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    // Debug output
    if (mysqli_num_rows($result) > 0) {
        $debug_applicant = mysqli_fetch_assoc($result);
        mysqli_data_seek($result, 0); // Reset pointer
        error_log("Debug - Application ID: " . $debug_applicant['id'] . ", Status: " . $debug_applicant['status']);
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Applicants | AutoRecruits</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="employerdashboard.css">
    <link rel="stylesheet" href="applicants.css">
    <style>
        .main-container {
            padding: 2rem;
            margin-left: 250px;
            background-color: #f1f5f9;
            min-height: 100vh;
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

        .applicants-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }

        .applicant-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .applicant-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
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
            display: flex;
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

        .no-applicants {
            text-align: center;
            padding: 3rem;
            color: #64748b;
            background: white;
            border-radius: 12px;
            margin-top: 2rem;
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
    <div class="sidebar">
        <div class="logo-container">
            <img src="../assets/images/logo.png" alt="AutoRecruits.in">
        </div>
        <div class="company-info">
            <span>AutoRecruits.in</span>
            <span style="font-size: 13px; color: var(--light-text);">Employer Dashboard</span>
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
    </div>

    <div class="main-container">
        <div class="header">
            <h1>Job Applicants</h1>
            <div class="job-title">
                <i class="fas fa-briefcase"></i>
                <?php echo htmlspecialchars($job_details['job_title']); ?>
            </div>
        </div>

        <?php if (mysqli_num_rows($result) > 0): ?>
            <div class="applicants-grid">
                <?php while ($applicant = mysqli_fetch_assoc($result)): ?>
                    <div class="applicant-card">
                        <div class="applicant-header">
                            <div class="applicant-photo">
                                <img src="<?php echo !empty($applicant['profile_image']) ? 
                                              htmlspecialchars($applicant['profile_image_path']) : 
                                              '../assets/images/default-user.png'; ?>" 
                                     alt="<?php echo htmlspecialchars($applicant['first_name']); ?>"
                                     onerror="this.src='../assets/images/default-user.png';">
                            </div>
                            <div>
                                <div class="applicant-name">
                                    <?php echo htmlspecialchars($applicant['first_name'] . ' ' . $applicant['last_name']); ?>
                                </div>
                            </div>
                        </div>

                        <div class="applicant-info">
                            <div class="info-item">
                                <i class="fas fa-envelope"></i>
                                <?php echo htmlspecialchars($applicant['email']); ?>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-phone"></i>
                                <?php echo htmlspecialchars($applicant['phone_number']); ?>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-clock"></i>
                                Status: <?php echo ucfirst(htmlspecialchars($applicant['status'])); ?>
                            </div>
                        </div>

                        <div class="action-buttons">
                            <?php 
                                $status = strtolower($applicant['status']);
                                if ($status === 'pending' || is_null($applicant['status']) || $status === ''):
                            ?>
                                <a href="accept.php?application_id=<?php echo $applicant['id']; ?>&status=accepted" 
                                    class="action-btn accept-btn">
                                    <i class="fas fa-check"></i>
                                    Accept
                                </a>
                                <a href="updateStatus.php?application_id=<?php echo $applicant['id']; ?>&status=rejected" 
                                    class="action-btn reject-btn">
                                    <i class="fas fa-times"></i>
                                    Reject
                                </a>
                            <?php else: ?>
                                <div class="status-badge <?php echo htmlspecialchars($applicant['status']); ?>">
                                    <?php echo ucfirst(htmlspecialchars($applicant['status'])); ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="application-date">
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
                    // Reload the page after a short delay
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

<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['employer_id'])) {
    header("Location: ../login/loginvalidation.php");
    exit();
}

include '../database/connectdatabase.php';

// Get employer_id from session
$employer_id = $_SESSION['employer_id'];

// Debug the session and connection
echo "<!-- Debug: Session employer_id = " . $_SESSION['employer_id'] . " -->";

// Fetch employer details
$sql = "SELECT * FROM tbl_employer WHERE employer_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $employer_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);

// Debug: Check if file exists
$image_path = $row['profile_image'];
$full_path = __DIR__ . '/' . $image_path; // Get full server path
echo "<!-- 
Debug Info:
Image Path from DB: " . $image_path . "
Full Server Path: " . $full_path . "
File Exists: " . (file_exists($full_path) ? 'Yes' : 'No') . "
File Permissions: " . (file_exists($full_path) ? decoct(fileperms($full_path) & 0777) : 'N/A') . "
-->";

// Fetch email from tbl_login
$sql_email = "SELECT email FROM tbl_login WHERE employer_id = ?";
$stmt_email = mysqli_prepare($conn, $sql_email);
mysqli_stmt_bind_param($stmt_email, "i", $employer_id);
mysqli_stmt_execute($stmt_email);
$result_email = mysqli_stmt_get_result($stmt_email);
$row_email = mysqli_fetch_assoc($result_email);
$email = $row_email['email'] ?? '';

// Get company name
$company_name = $row['company_name'] ?? 'Company Name Not Set';

// Debug output
echo "<!-- 
Debug Info:
Employer ID: " . $employer_id . "
Company Name: " . $company_name . "
Email: " . $email . "
Profile Image: " . ($row['profile_image'] ?? 'Not set') . "
-->";

mysqli_stmt_close($stmt);
mysqli_stmt_close($stmt_email);

// fetching data from tbl_jobs
$sql_fetch="SELECT * FROM tbl_jobs WHERE employer_id=? AND is_deleted=0";
$stmt_fetch=mysqli_prepare($conn,$sql_fetch);
mysqli_stmt_bind_param($stmt_fetch,"i",$employer_id);
mysqli_stmt_execute($stmt_fetch);
$result_fetch=mysqli_stmt_get_result($stmt_fetch);
mysqli_stmt_close($stmt_fetch);

// count for active_jobs
$sql_count = "SELECT COUNT(*) AS active_jobs FROM tbl_jobs WHERE employer_id=? AND is_deleted=0";
$stmt_count = mysqli_prepare($conn, $sql_count);
mysqli_stmt_bind_param($stmt_count, "i", $employer_id);
mysqli_stmt_execute($stmt_count);
$result_count = mysqli_stmt_get_result($stmt_count);
$active_jobs = 0;

if ($row = mysqli_fetch_assoc($result_count)) {
    $active_jobs = $row['active_jobs'];
}

mysqli_stmt_close($stmt_count);

// Add this after the existing count query
$sql_deactivated_count = "SELECT COUNT(*) AS deactivated_jobs FROM tbl_jobs WHERE employer_id=? AND is_deleted=1";
$stmt_deactivated_count = mysqli_prepare($conn, $sql_deactivated_count);
mysqli_stmt_bind_param($stmt_deactivated_count, "i", $employer_id);
mysqli_stmt_execute($stmt_deactivated_count);
$result_deactivated_count = mysqli_stmt_get_result($stmt_deactivated_count);
$deactivated_jobs = 0;

if ($row = mysqli_fetch_assoc($result_deactivated_count)) {
    $deactivated_jobs = $row['deactivated_jobs'];
}
mysqli_stmt_close($stmt_deactivated_count);

// Get current status from URL parameter, default to 'all'
$current_status = isset($_GET['status']) ? $_GET['status'] : 'all';

function time_elapsed_string($datetime) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    if ($diff->y > 0) {
        return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
    }
    if ($diff->m > 0) {
        return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
    }
    if ($diff->d > 0) {
        if ($diff->d == 1) {
            return 'Yesterday';
        }
        return $diff->d . ' days ago';
    }
    if ($diff->h > 0) {
        return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
    }
    if ($diff->i > 0) {
        return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
    }
    return 'Just now';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Jobs | <?php echo htmlspecialchars($company_name); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="myjoblist.css">
    <!-- <style>
        /* Additional styles specific to myjoblist.php */
        .job-filters {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .filter-btn {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            padding: 8px 15px;
            font-size: 14px;
            color: var(--text-color);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .filter-btn:hover, .filter-btn.active {
            background-color: var(--primary-light);
            color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .job-status {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-active {
            background-color: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
        }
        
        .status-closed {
            background-color: rgba(239, 68, 68, 0.1);
            color: var(--danger-color);
        }
        
        .status-draft {
            background-color: rgba(245, 158, 11, 0.1);
            color: var(--warning-color);
        }

        .status-approved {
            background-color: rgba(16, 185, 129, 0.1);
            color: #16a34a;
        }

        .status-rejected {
            background-color: rgba(239, 68, 68, 0.1);
            color: #dc2626;
        }
        
        .job-actions {
            display: flex;
            gap: 10px;
        }
        
        @media (max-width: 768px) {
            .job-actions {
                flex-direction: column;
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

        /* Sidebar Logo Container and Profile Picture Styles */
        .logo-container {
            padding: 20px;
            display: flex;
            justify-content: center;
            border-bottom: 1px solid var(--border-color);
        }

        .logo-container img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--primary-color);
        }

        .company-info {
            padding: 15px 20px;
            text-align: center;
            border-bottom: 1px solid var(--border-color);
        }

        .company-info span:first-child {
            font-weight: 600;
            font-size: 16px;
            display: block;
            margin-bottom: 5px;
        }

        /* Confirmation Panel Styles */
        .overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
        }

        .confirmation-panel {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            z-index: 1001;
            width: 90%;
            max-width: 400px;
            animation: slideIn 0.3s ease forwards;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translate(-50%, -60%);
            }
            to {
                opacity: 1;
                transform: translate(-50%, -50%);
            }
        }

        .confirmation-content {
            text-align: center;
        }

        .confirmation-icon {
            font-size: 48px;
            margin-bottom: 20px;
        }

        .confirmation-icon.danger {
            color: #dc2626;
        }

        .confirmation-text h3 {
            margin-bottom: 10px;
            color: #1f2937;
        }

        .confirmation-text p {
            color: #6b7280;
            margin-bottom: 20px;
        }

        .confirmation-actions {
            display: flex;
            justify-content: center;
            gap: 10px;
        }

        .cancel-btn, .confirm-btn {
            padding: 8px 20px;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .cancel-btn {
            background-color: #f3f4f6;
            color: #4b5563;
            border: none;
        }

        .confirm-btn {
            text-decoration: none;
            border: none;
        }

        .confirm-btn.danger {
            background-color: #dc2626;
            color: white;
        }

        .confirm-btn.danger:hover {
            background-color: #b91c1c;
        }
    </style> -->
</head>
<body>
    <div class="success-message" id="successMessage">
        <i class="fas fa-check-circle"></i>
        <span id="successMessageText">Job has been successfully restored!</span>
    </div>

    <div class="dashboard-container">
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
                <div class="nav-item"><i class="fas fa-th-large"></i><a href="employerdashboard.php">Dashboard</a></div>
                <div class="nav-item"><i class="fas fa-plus-circle"></i><a href="postjob.php">Post a Job</a></div>
                <div class="nav-item active"><i class="fas fa-briefcase"></i><a href="myjoblist.php">My Jobs</a></div>
                <div class="nav-item"><i class="fas fa-users"></i><a href="applicants.php">Applicants</a></div>
                <div class="nav-item"><i class="fas fa-calendar-check"></i><a href="sidebar.php">Interviews</a></div>
            </nav>
            <div class="settings-section">
                <div class="nav-item"><i class="fas fa-user-cog"></i><a href="employer_profile.php">My Profile</a></div>
                <div class="nav-item"><i class="fas fa-sign-out-alt"></i><a href="../login/logout.php">Logout</a></div>
                </div>
                </div>
        <!-- Main Container -->
        <div class="main-container">
            <!-- Main Content -->
            <div class="main-content">
                <div class="header">
                    <h1>My Jobs - <?php echo htmlspecialchars($company_name); ?></h1>
                    <div style="display: flex; gap: 15px;">
                        <!-- <button id="applicantsToggle" class="toggle-sidebar-btn">
                            <i class="fas fa-users"></i>
                            <span>Applicants</span>
                        </button> -->
                        <button class="post-job-btn">
                            <i class="fas fa-plus-circle" style="margin-right: 8px;"></i>
                            <a href="postjob.php">Post a Job</a>
                        </button>
                    </div>
                </div>

                <!-- Job Filters -->
                <div class="job-filters">
                    <button class="filter-btn <?php echo ($current_status === 'all' || $current_status === '') ? 'active' : ''; ?>" 
                            data-status="all">
                        All Active Jobs <span><?php echo $active_jobs; ?></span>
                    </button>
                    <button class="filter-btn <?php echo $current_status === 'pending' ? 'active' : ''; ?>" 
                            data-status="pending">
                        Pending Approval <span><?php 
                            $pending_count = mysqli_fetch_assoc(mysqli_query($conn, 
                                "SELECT COUNT(*) as count FROM tbl_jobs WHERE employer_id=$employer_id AND status='pending' AND is_deleted=0"
                            ))['count'];
                            echo $pending_count; 
                        ?></span>
                    </button>
                    <button class="filter-btn <?php echo $current_status === 'approved' ? 'active' : ''; ?>" 
                            data-status="approved">
                        Approved Jobs <span><?php 
                            $approved_count = mysqli_fetch_assoc(mysqli_query($conn, 
                                "SELECT COUNT(*) as count FROM tbl_jobs WHERE employer_id=$employer_id AND status='approved' AND is_deleted=0"
                            ))['count'];
                            echo $approved_count; 
                        ?></span>
                    </button>
                    <button class="filter-btn <?php echo $current_status === 'rejected' ? 'active' : ''; ?>" 
                            data-status="rejected">
                        Rejected Jobs <span><?php 
                            $rejected_count = mysqli_fetch_assoc(mysqli_query($conn, 
                                "SELECT COUNT(*) as count FROM tbl_jobs WHERE employer_id=$employer_id AND status='rejected' AND is_deleted=0"
                            ))['count'];
                            echo $rejected_count; 
                        ?></span>
                    </button>
                    <button class="filter-btn <?php echo $current_status === 'deactivated' ? 'active' : ''; ?>" 
                            data-status="deactivated">
                        Deactivated <span><?php echo $deactivated_jobs; ?></span>
                    </button>
                </div>

                <!-- Search Section -->
                <div class="search-section">
                <div class="search-container">
                    <div class="search-bar">
                            <svg class="search-icon" focusable="false" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                <path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"></path>
                            </svg>
                            <input type="text" class="search-input" placeholder="Search jobs..." aria-label="Search jobs">
                            <svg class="search-close" focusable="false" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"></path>
                            </svg>
                        </div>
                    </div>
                </div>
                
                <!-- Add this right after the search section and before the job-card-container -->
                <div class="no-results-container">
                    <div class="no-results">
                        <svg class="search-icon" focusable="false" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                            <path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"></path>
                        </svg>
                        <h3>No jobs found</h3>
                        <p>Try adjusting your search criteria</p>
                    </div>
                </div>
                
                <!-- Job Listings -->
                <div class="job-card-container">
                    <?php 
                    $status = isset($_GET['status']) ? $_GET['status'] : 'all';
                    $is_deleted = ($status === 'deactivated') ? 1 : 0;

                    // Get all jobs for this employer
                    $sql = "SELECT * FROM tbl_jobs WHERE employer_id = ?";
                    
                    // Apply filters if selected
                    if (isset($_GET['status'])) {
                        switch ($_GET['status']) {
                            case 'active':
                                $sql .= " AND (status = 'pending' OR status = 'approved') AND is_deleted = 0";
                                break;
                            case 'pending':
                                $sql .= " AND status = 'pending' AND is_deleted = 0";
                                break;
                            case 'approved':
                                $sql .= " AND status = 'approved' AND is_deleted = 0";
                                break;
                            case 'rejected':
                                $sql .= " AND status = 'rejected' AND is_deleted = 0";
                                break;
                            case 'deactivated':
                                $sql .= " AND is_deleted = 1";
                                break;
                            default:
                                $sql .= " AND is_deleted = 0";
                        }
                    } else {
                        // Default view: show all non-deleted jobs
                        $sql .= " AND is_deleted = 0";
                    }
                    
                    $sql .= " ORDER BY job_id DESC";

                    $stmt_fetch = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt_fetch, "i", $employer_id);
                    mysqli_stmt_execute($stmt_fetch);
                    $result_fetch = mysqli_stmt_get_result($stmt_fetch);

                    if(mysqli_num_rows($result_fetch) > 0): 
                        while ($job_data = mysqli_fetch_array($result_fetch)): 
                    ?>
                        <div class="job-card" data-status="<?php echo $job_data['status']; ?>">
                            <div class="job-info">
                                <div class="company-logo">
                                    <i class="fas fa-briefcase"></i>
                                </div>
                                <div class="job-details">
                                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 5px;">
                                        <h3><?php echo htmlspecialchars($job_data['job_title']); ?></h3>
                                        <span class="job-status <?php 
                                            echo match($job_data['status']) {
                                                'deactivated' => 'status-deactivated',
                                                'approved' => 'status-approved',
                                                'rejected' => 'status-rejected',
                                                default => 'status-active'
                                            };
                                        ?>">
                                            <?php echo ucfirst($job_data['status']); ?>
                                        </span>
                                    </div>
                                    <div class="job-meta">
                                        <div class="job-meta-item">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <?php echo htmlspecialchars($job_data['location']); ?>
                                        </div>
                                        <div class="job-meta-item">
                                            <i class="fas fa-calendar-alt"></i>
                                            <?php echo htmlspecialchars($job_data['vacancy_date']); ?>
                                        </div>
                                        <div class="job-meta-item">
                                            ₹<?php echo htmlspecialchars($job_data['salary']); ?>
                                        </div>
                                    </div>
                                    
                                    <div class="job-description" style="margin-top: 15px; font-size: 14px; color: var(--light-text);">
                                        <?php 
                                            $description = htmlspecialchars($job_data['job_description']);
                                            echo (strlen($description) > 150) ? substr($description, 0, 150) . '...' : $description; 
                                        ?>
                                    </div>
                                    
                                    <div class="job-stats" style="display: flex; gap: 15px; margin-top: 15px;">
                                        <?php
                                        // Applicants count
                                        $sql_applicants = "SELECT COUNT(*) as applicant_count FROM tbl_applications WHERE job_id = ?";
                                        $stmt_applicants = mysqli_prepare($conn, $sql_applicants);
                                        mysqli_stmt_bind_param($stmt_applicants, "i", $job_data['job_id']);
                                        mysqli_stmt_execute($stmt_applicants);
                                        $result_applicants = mysqli_stmt_get_result($stmt_applicants);
                                        $applicant_count = mysqli_fetch_assoc($result_applicants)['applicant_count'];
                                        mysqli_stmt_close($stmt_applicants);
                                        ?>
                                        <div class="job-stat-item" style="display: flex; align-items: center; font-size: 13px; color: var(--primary-color);">
                                            <i class="fas fa-users" style="margin-right: 5px;"></i>
                                            <span><?php echo $applicant_count; ?> Applicant<?php echo $applicant_count != 1 ? 's' : ''; ?></span>
                                        </div>
                                        <div class="job-stat-item" style="display: flex; align-items: center; font-size: 13px; color: var(--primary-color);">
                                            <i class="fas fa-eye" style="margin-right: 5px;"></i>
                                            <span>48 Views</span>
                                        </div>
                                        <div class="job-stat-item" style="display: flex; align-items: center; font-size: 13px; color: var(--success-color);">
                                            <i class="fas fa-clock" style="margin-right: 5px;"></i>
                                            <span>Posted <?php echo time_elapsed_string($job_data['created_at']); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="job-actions">
                                <?php if($job_data['is_deleted'] == 0): // For active jobs ?>
                                    <a href="applicants.php?job_id=<?php echo $job_data['job_id']; ?>" class="action-btn view-btn">
                                        <i class="fas fa-users"></i> Applicants
                                    </a>
                                    
                                    <?php if($job_data['interview'] === 'yes'): ?>
                                        <a href="schedule_interview.php?job_id=<?php echo $job_data['job_id']; ?>" 
                                           class="action-btn interview-btn">
                                            <i class="fas fa-calendar-check"></i> Schedule Interview
                                        </a>
                                    <?php endif; ?>

                                    <a href="editjob.php?job_id=<?php echo $job_data['job_id']; ?>" class="action-btn edit-btn">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>

                                    <?php 
                                    // Check if job has any applicants
                                    $sql_check_applicants = "SELECT COUNT(*) as applicant_count FROM tbl_applications WHERE job_id = ?";
                                    $stmt_check = mysqli_prepare($conn, $sql_check_applicants);
                                    mysqli_stmt_bind_param($stmt_check, "i", $job_data['job_id']);
                                    mysqli_stmt_execute($stmt_check);
                                    $result_check = mysqli_stmt_get_result($stmt_check);
                                    $applicant_count = mysqli_fetch_assoc($result_check)['applicant_count'];
                                    mysqli_stmt_close($stmt_check);

                                    if($applicant_count == 0): // Only show deactivate button if no applicants
                                    ?>
                                    <a href="#" class="action-btn delete-btn" onclick="showDeleteConfirmation(<?php echo $job_data['job_id']; ?>); return false;">
                                        <i class="fas fa-trash-alt"></i> Deactivate
                                    </a>
                                <?php else: ?>
                                        <span class="action-btn disabled-btn" title="Cannot deactivate: Job has active applicants">
                                            <i class="fas fa-lock"></i> Cannot Deactivate
                                        </span>
                                    <?php endif; ?>
                                <?php else: // For deactivated jobs ?>
                                    <a href="#" class="action-btn restore-btn" onclick="showRestoreConfirmation(<?php echo $job_data['job_id']; ?>); return false;">
                                        <i class="fas fa-undo"></i> Restore Job
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php 
                        endwhile; 
                    else: 
                    ?>
                        <div class="no-jobs-message">
                            <?php
                            $messageData = [
                                'all' => [
                                    'icon' => 'fa-briefcase',
                                    'title' => 'No Active Jobs',
                                    'message' => 'You haven\'t posted any jobs yet. Click "Post a Job" to get started.'
                                ],
                                'pending' => [
                                    'icon' => 'fa-clock',
                                    'title' => 'No Pending Jobs',
                                    'message' => 'There are no jobs waiting for approval at the moment.'
                                ],
                                'approved' => [
                                    'icon' => 'fa-check-circle',
                                    'title' => 'No Approved Jobs',
                                    'message' => 'None of your jobs have been approved yet. Check back later.'
                                ],
                                'rejected' => [
                                    'icon' => 'fa-times-circle',
                                    'title' => 'No Rejected Jobs',
                                    'message' => 'None of your jobs have been rejected. Keep up the good work!'
                                ],
                                'deactivated' => [
                                    'icon' => 'fa-archive',
                                    'title' => 'No Deactivated Jobs',
                                    'message' => 'You don\'t have any deactivated job listings.'
                                ]
                            ];

                            $currentFilter = isset($_GET['status']) ? $_GET['status'] : 'all';
                            $data = $messageData[$currentFilter] ?? $messageData['all'];
                            ?>
                            <div class="empty-state">
                                <div class="empty-state-icon">
                                    <i class="fas <?php echo $data['icon']; ?>"></i>
                                </div>
                                <h3><?php echo $data['title']; ?></h3>
                                <p><?php echo $data['message']; ?></p>
                                <?php if($currentFilter === 'all'): ?>
                                    <a href="postjob.php" class="post-job-link">
                                        <i class="fas fa-plus-circle"></i>
                                        Post Your First Job
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php 
                    endif; 
                    mysqli_stmt_close($stmt_fetch);
                    ?>
                </div>
            </div>
        </div>

        <!-- Applicants Sidebar -->
        <div class="applicants-sidebar" id="applicantsSidebar">
            <div class="close-sidebar">
                <button id="closeSidebar">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="applicants-header">
                <h2>Recent Applicants</h2>
                <span class="applicant-count">9</span>
            </div>
            <div class="applicant-list">
                <!-- Web Developer Group -->
                <div style="margin-bottom: 20px;">
                    <h3 style="margin-bottom: 12px; font-size: 15px; color: var(--light-text);">Web Developer</h3>
                    <div class="applicant-card">
                        <div class="applicant-info">
                            <div class="applicant-avatar">MZ</div>
                            <div>
                                <div class="applicant-name">Mohd Zaid</div>
                                <div class="applicant-position">React Developer</div>
                            </div>
                        </div>
                    </div>
                    <div class="applicant-card">
                        <div class="applicant-info">
                            <div class="applicant-avatar">SK</div>
                            <div>
                                <div class="applicant-name">Samra Khawar</div>
                                <div class="applicant-position">Node.JS Developer</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Designer Group -->
                <div style="margin-bottom: 20px;">
                    <h3 style="margin-bottom: 12px; font-size: 15px; color: var(--light-text);">Designer</h3>
                    <div class="applicant-card">
                        <div class="applicant-info">
                            <div class="applicant-avatar">BA</div>
                            <div>
                                <div class="applicant-name">Bilal Ahmed</div>
                                <div class="applicant-position">UI/UX Designer</div>
                            </div>
                        </div>
                    </div>
                    <div class="applicant-card">
                        <div class="applicant-info">
                            <div class="applicant-avatar">ZA</div>
                            <div>
                                <div class="applicant-name">Zohail Ali</div>
                                <div class="applicant-position">Product Designer</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <a href="applicants.php" style="display: block; text-align: center; margin-top: 20px; color: var(--primary-color); text-decoration: none; font-weight: 500; font-size: 14px;">
                    View All Applicants <i class="fas fa-arrow-right" style="margin-left: 5px;"></i>
                </a>
            </div>
        </div>
    </div>
    
    <!-- Delete Confirmation Panel -->
    <div id="confirmationOverlay" class="overlay"></div>
    <div class="confirmation-panel" id="deletePanel">
        <div class="confirmation-content">
            <div class="confirmation-icon">
                <i class="fas fa-exclamation-circle"></i>
            </div>
            <div class="confirmation-text">
                <h3>Are you sure?</h3>
                <p>Do you really want to deactivate this job? This process cannot be undone.</p>
            </div>
            <div class="confirmation-actions">
                <button class="cancel-btn" onclick="hideDeleteConfirmation()">No, Cancel</button>
                <a href="#" id="confirmDelete" class="confirm-btn">Yes, Deactivate</a>
            </div>
        </div>
    </div>

    <!-- Restore Confirmation Panel -->
    <div class="confirmation-panel" id="restorePanel">
        <div class="confirmation-content">
            <div class="confirmation-icon success">
                <i class="fas fa-undo"></i>
            </div>
            <div class="confirmation-text">
                <h3>Restore Job</h3>
                <p>Are you sure you want to restore this job? It will become visible to users again.</p>
            </div>
            <div class="confirmation-actions">
                <button class="cancel-btn" onclick="hideRestoreConfirmation()">Cancel</button>
                <a href="#" id="confirmRestore" class="confirm-btn success">Restore</a>
            </div>
        </div>
    </div>

    <!-- Font Awesome -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const applicantsToggle = document.getElementById('applicantsToggle');
            const closeSidebar = document.getElementById('closeSidebar');
            const applicantsSidebar = document.getElementById('applicantsSidebar');
            const body = document.body;
            
            // Create overlay element
            const overlay = document.createElement('div');
            overlay.className = 'sidebar-overlay';
            body.appendChild(overlay);
            
            // Open sidebar
            applicantsToggle.addEventListener('click', function() {
                applicantsSidebar.classList.add('open');
                overlay.classList.add('active');
                body.style.overflow = 'hidden'; // Prevent scrolling when sidebar is open
            });
            
            // Close sidebar
            closeSidebar.addEventListener('click', function() {
                applicantsSidebar.classList.remove('open');
                overlay.classList.remove('active');
                body.style.overflow = ''; // Restore scrolling
            });
            
            // Close sidebar when clicking on overlay
            overlay.addEventListener('click', function() {
                applicantsSidebar.classList.remove('open');
                overlay.classList.remove('active');
                body.style.overflow = ''; // Restore scrolling
            });
            
            // Close sidebar with Escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && applicantsSidebar.classList.contains('open')) {
                    applicantsSidebar.classList.remove('open');
                    overlay.classList.remove('active');
                    body.style.overflow = ''; // Restore scrolling
                }
            });
            
            // Filter buttons functionality
            const filterButtons = document.querySelectorAll('.filter-btn');
            filterButtons.forEach(button => {
                button.addEventListener('click', function() {
                    // Remove active class from all buttons
                    filterButtons.forEach(btn => btn.classList.remove('active'));
                    // Add active class to clicked button
                    this.classList.add('active');

                    const status = this.dataset.status;
                    window.location.href = `myjoblist.php?status=${status}`;
                });
            });

            // Delete Confirmation Functions
            function showDeleteConfirmation(jobId) {
                document.getElementById('confirmationOverlay').style.display = 'block';
                const panel = document.getElementById('deletePanel');
                panel.style.display = 'block';
                panel.classList.add('show');
                document.getElementById('confirmDelete').href = `deletejob.php?id=${jobId}`;
                // Prevent body scrolling
                document.body.style.overflow = 'hidden';
            }

            function hideDeleteConfirmation() {
                document.getElementById('confirmationOverlay').style.display = 'none';
                const panel = document.getElementById('deletePanel');
                panel.classList.remove('show');
                panel.style.display = 'none';
                // Restore body scrolling
                document.body.style.overflow = '';
            }

            // Restore Confirmation Functions
            function showRestoreConfirmation(jobId) {
                document.getElementById('confirmationOverlay').style.display = 'block';
                document.getElementById('restorePanel').style.display = 'block';
                document.getElementById('confirmRestore').href = `restore_job.php?job_id=${jobId}`;
            }

            function hideRestoreConfirmation() {
                document.getElementById('confirmationOverlay').style.display = 'none';
                document.getElementById('restorePanel').style.display = 'none';
            }

            // Close confirmation when clicking overlay
            document.getElementById('confirmationOverlay').addEventListener('click', function() {
                hideDeleteConfirmation();
                hideRestoreConfirmation();
            });

            // Close confirmation with Escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    hideDeleteConfirmation();
                    hideRestoreConfirmation();
                }
            });

            // Make functions globally available
            window.showDeleteConfirmation = showDeleteConfirmation;
            window.hideDeleteConfirmation = hideDeleteConfirmation;
            window.showRestoreConfirmation = showRestoreConfirmation;
            window.hideRestoreConfirmation = hideRestoreConfirmation;
        });
        
        function showSearchBar() {
            // Add search functionality if needed
        }

        // Replace the existing icon color script with this updated version
        document.addEventListener('DOMContentLoaded', function() {
            // Function to force icon colors
            const lockIconColors = () => {
                // Target both the icon containers and their SVG paths
                document.querySelectorAll('.sidebar .nav-item .svg-inline--fa').forEach(icon => {
                    const isLogoutIcon = icon.classList.contains('fa-sign-out-alt');
                    if (!isLogoutIcon) {
                        // Set color on the SVG container
                        icon.style.cssText += 'color: #4a90e6 !important; fill: #4a90e6 !important;';
                        
                        // Force fill on all paths
                        icon.querySelectorAll('path').forEach(path => {
                            path.setAttribute('fill', 'currentColor');
                            path.style.fill = '#4a90e6';
                        });
                        
                        // Mark as color-locked
                        icon.dataset.colorLocked = 'true';
                    }
                });
            };

            // Initial application
            lockIconColors();
            
            // Reapply multiple times to catch any late modifications
            [100, 500, 1000, 2000].forEach(delay => {
                setTimeout(lockIconColors, delay);
            });

            // Set up mutation observer for both the icon and its paths
            const observer = new MutationObserver((mutations) => {
                mutations.forEach(mutation => {
                    if (mutation.target.closest('.svg-inline--fa') && 
                        !mutation.target.closest('.fa-sign-out-alt')) {
                        requestAnimationFrame(lockIconColors);
                    }
                });
            });

            // Observe all SVG icons and their paths
            document.querySelectorAll('.sidebar .nav-item .svg-inline--fa').forEach(icon => {
                observer.observe(icon, {
                    attributes: true,
                    attributeFilter: ['style', 'fill', 'color'],
                    childList: true,
                    subtree: true
                });
            });
        });

        // Force Font Awesome to maintain SVG rendering mode
        window.FontAwesomeConfig = {
            autoReplaceSvg: 'nest',
            observeMutations: true
        };

        document.addEventListener('DOMContentLoaded', function() {
            const searchBar = document.querySelector('.search-bar');
            const searchInput = document.querySelector('.search-input');
            const searchClose = document.querySelector('.search-close');
            const jobCards = document.querySelectorAll('.job-card');
            const noResultsContainer = document.querySelector('.no-results-container');
            const jobCardContainer = document.querySelector('.job-card-container');

            // Function to check input content and update classes
            function updateSearchBar() {
                if (searchInput.value.length > 0) {
                    searchBar.classList.add('has-content');
                } else {
                    searchBar.classList.remove('has-content');
                }
            }

            // Function to filter jobs
            function filterJobs(searchTerm) {
                searchTerm = searchTerm.toLowerCase();
                let hasVisibleCards = false;
                
                jobCards.forEach(card => {
                    const jobTitle = card.querySelector('h3').textContent.toLowerCase();
                    const jobDescription = card.querySelector('.job-description').textContent.toLowerCase();
                    const jobLocation = card.querySelector('.job-meta-item').textContent.toLowerCase();
                    
                    if (jobTitle.includes(searchTerm) || 
                        jobDescription.includes(searchTerm) || 
                        jobLocation.includes(searchTerm)) {
                        card.style.display = '';
                        hasVisibleCards = true;
                    } else {
                        card.style.display = 'none';
                    }
                });

                // Show/hide no results message
                if (hasVisibleCards) {
                    noResultsContainer.style.display = 'none';
                    jobCardContainer.style.display = 'grid';
                } else {
                    noResultsContainer.style.display = 'block';
                    jobCardContainer.style.display = 'none';
                }
            }

            // Listen for input changes
            searchInput.addEventListener('input', function() {
                updateSearchBar();
                filterJobs(this.value);
            });

            // Clear search when close icon is clicked
            searchClose.addEventListener('click', () => {
                searchInput.value = '';
                updateSearchBar();
                filterJobs('');
                searchInput.focus();
            });

            // Initial check
            updateSearchBar();
        });

        // Add this at the start of your script section
        document.addEventListener('DOMContentLoaded', function() {
            // Check for message parameters
            const urlParams = new URLSearchParams(window.location.search);
            const successMessage = document.getElementById('successMessage');
            const messageText = document.getElementById('successMessageText');
            
            if (urlParams.get('restored') === 'true') {
                messageText.textContent = 'Job has been successfully restored!';
                showMessage();
            } else if (urlParams.get('deactivated') === 'true') {
                messageText.textContent = 'Job has been successfully deactivated!';
                showMessage();
            }

            function showMessage() {
                // Show the message
                successMessage.style.display = 'block';
                
                // Clean up the URL
                const newUrl = window.location.pathname + window.location.search.replace(/[?&](restored|deactivated)=true/, '');
                window.history.replaceState({}, '', newUrl);
                
                // Hide the message after 3 seconds
                setTimeout(() => {
                    successMessage.classList.add('hide');
                    setTimeout(() => {
                        successMessage.style.display = 'none';
                        successMessage.classList.remove('hide');
                    }, 300);
                }, 3000);
            }
        });
    </script>
</body>
</html>
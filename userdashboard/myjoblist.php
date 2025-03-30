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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Jobs | <?php echo htmlspecialchars($company_name); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="employerdashboard.css">
    <style>
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
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Main Sidebar -->
        <div class="sidebar">
            <div class="logo-container">
                <?php 
                if(!empty($row['profile_image']) && file_exists($full_path)): 
                    // Debug: Output the HTML being generated
                    echo "<!-- Generated img tag with src: " . htmlspecialchars($image_path) . " -->";
                ?>
                    <img src="<?php echo htmlspecialchars($image_path); ?>" 
                         alt="<?php echo htmlspecialchars($company_name); ?>"
                         onerror="this.src='../assets/images/company-logo.png';"
                         style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 2px solid var(--primary-color);">
                <?php else: 
                    echo "<!-- Using default image because: " . 
                         (empty($row['profile_image']) ? "No image path in DB" : "File not found") . " -->";
                ?>
                    <img src="../assets/images/company-logo.png" 
                         alt="AutoRecruits.in"
                         style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 2px solid var(--primary-color);">
                <?php endif; ?>
            </div>
            <div class="company-info">
                <span><?php echo htmlspecialchars($company_name); ?></span>
                <span style="font-size: 13px; color: var(--light-text);"><?php echo htmlspecialchars($email); ?></span>
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
                    <i class="fas fa-user-cog"></i>
                    <a href="employer_profile.php">My Profile</a>
                </div>
                <div class="nav-item">
                    <i class="fas fa-sign-out-alt"></i>
                    <a href="../login/logout.php">Logout</a>
                </div>
            </div>
        </div>

        <!-- Main Container -->
        <div class="main-container">
            <!-- Main Content -->
            <div class="main-content">
                <div class="header">
                    <h1>My Jobs - <?php echo htmlspecialchars($company_name); ?></h1>
                    <div style="display: flex; gap: 15px;">
                        <button id="applicantsToggle" class="toggle-sidebar-btn">
                            <i class="fas fa-users"></i>
                            <span>Applicants</span>
                        </button>
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
                        All Active Jobs (<?php echo $active_jobs; ?>)
                    </button>
                    <button class="filter-btn <?php echo $current_status === 'pending' ? 'active' : ''; ?>" 
                            data-status="pending">
                        Pending Approval
                    </button>
                    <button class="filter-btn <?php echo $current_status === 'approved' ? 'active' : ''; ?>" 
                            data-status="approved">
                        Approved Jobs
                    </button>
                    <button class="filter-btn <?php echo $current_status === 'rejected' ? 'active' : ''; ?>" 
                            data-status="rejected">
                        Rejected Jobs
                    </button>
                    <button class="filter-btn <?php echo $current_status === 'deactivated' ? 'active' : ''; ?>" 
                            data-status="deactivated">
                        Deactivated (<?php echo $deactivated_jobs; ?>)
                    </button>
                </div>

                <!-- Search Bar -->
                <div class="search-container">
                    <div class="search-bar">
                        <select>
                            <option value="all">All Categories</option>
                            <option value="salary">Salary</option>
                            <option value="location">Location</option>
                            <option value="title">Job Title</option>
                        </select>
                        <input type="text" placeholder="Search for jobs..." onfocus="showSearchBar()">
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
                                            <span>Posted 3 days ago</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="job-actions">
                                <?php if($job_data['status'] != 'deactivated'): ?>
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
                                    <a href="#" class="action-btn delete-btn" onclick="showDeleteConfirmation(<?php echo $job_data['job_id']; ?>); return false;">
                                        <i class="fas fa-trash-alt"></i> Deactivate
                                    </a>
                                <?php else: ?>
                                    <a href="#" onclick="showRestoreConfirmation(<?php echo $job_data['job_id']; ?>); return false;" 
                                       class="action-btn reactivate-btn">
                                        <i class="fas fa-redo"></i> Restore
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php 
                        endwhile; 
                    else: 
                    ?>
                        <div style="text-align: center; padding: 20px;">
                            <p>No <?php echo $status === 'deactivated' ? 'deactivated' : 'active'; ?> jobs found.</p>
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
            <div class="confirmation-icon danger">
                <i class="fas fa-trash-alt"></i>
            </div>
            <div class="confirmation-text">
                <h3>Deactivate Job</h3>
                <p>Are you sure you want to deactivate this job? It will no longer be visible to users.</p>
            </div>
            <div class="confirmation-actions">
                <button class="cancel-btn" onclick="hideDeleteConfirmation()">Cancel</button>
                <a href="#" id="confirmDelete" class="confirm-btn danger">Deactivate</a>
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
                document.getElementById('deletePanel').style.display = 'block';
                document.getElementById('confirmDelete').href = `deletejob.php?id=${jobId}`;
            }

            function hideDeleteConfirmation() {
                document.getElementById('confirmationOverlay').style.display = 'none';
                document.getElementById('deletePanel').style.display = 'none';
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
    </script>
</body>
</html>
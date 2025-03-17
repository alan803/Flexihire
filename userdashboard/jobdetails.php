<?php
    session_start();
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../login/login.php");
        exit();
    }

    include '../database/connectdatabase.php';
    $dbname = "project";
    mysqli_select_db($conn, $dbname);

    // Get job_id from URL
    $job_id = isset($_GET['job_id']) ? $_GET['job_id'] : null;

    if (!$job_id) {
        header("Location: userdashboard.php");
        exit();
    }

    // Fetch job details
    $sql = "SELECT * FROM tbl_jobs WHERE job_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $job_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $job = mysqli_fetch_assoc($result);

    //fecth user profile
    $user_id = $_SESSION['id'] ?? $_SESSION['user_id'];
    $sql_user = "SELECT * FROM tbl_user WHERE user_id = '$user_id'";
    $result_user = mysqli_query($conn, $sql_user);
    $user = mysqli_fetch_assoc($result_user);
    $profile_image = $user['profile_image'];

    // Debug
    error_log("User ID: " . $user_id . ", Job ID: " . $job_id);

    // Check if job is already bookmarked
    $check_bookmark_sql = "SELECT * FROM tbl_bookmarks WHERE user_id = ? AND job_id = ?";
    $check_stmt = mysqli_prepare($conn, $check_bookmark_sql);
    mysqli_stmt_bind_param($check_stmt, "ii", $user_id, $job_id);
    mysqli_stmt_execute($check_stmt);
    $bookmark_result = mysqli_stmt_get_result($check_stmt);
    $is_bookmarked = mysqli_num_rows($bookmark_result) > 0;

    // Debug
    error_log("Is Bookmarked: " . ($is_bookmarked ? 'Yes' : 'No'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Details</title>
    <link rel="stylesheet" href="jobdetails.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-left">
            <!-- <div class="grid-container">
                <i class="fas fa-bars" id="grid"></i>
            </div> -->
            <h1>Job Details</h1>
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
                    </div>
                    <div class="dropdown-divider"></div>
                    <a href="profiles/user/userprofile.php"><i class="fas fa-user"></i> Profile</a>
                    <a href="../login/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="dashboard-container">
        <!-- Fixed Sidebar -->
        <div class="sidebar">
            <div class="sidebar-menu">
                <a href="userdashboard.php" class="sidebar-link">
                    <i class="fas fa-home"></i>
                    <span>Home</span>
                </a>
                <!-- <a href="bookmark.php" class="sidebar-link">
                    <i class="fas fa-bookmark"></i>
                    <span>Bookmarks</span>
                </a> -->
                <a href="applyjob.php" class="sidebar-link">
                    <i class="fas fa-paper-plane"></i>
                    <span>Apply Job</span>
                </a>
                <!-- <a href="jobs/appliedjobs.php" class="sidebar-link">
                    <i class="fas fa-check-circle"></i>
                    <span>Applied Jobs</span>
                </a> -->
                <a href="bookmark.php" class="sidebar-link">
                    <i class="fas fa-bookmark"></i>
                    <span>Bookmarks</span>
                </a>
                <a href="sidebar/appointment/appointment.html" class="sidebar-link">
                    <i class="fas fa-calendar"></i>
                    <span>Appointments</span>
                </a>
                <a href="profiles/user/userprofile.php" class="sidebar-link">
                    <i class="fas fa-user"></i>
                    <span>Profile</span>
                </a>
            </div>
            <div class="logout-container">
                <div class="sidebar-divider"></div>
                <a href="../login/logout.php" class="logout-link">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <main class="main-content">
            <div class="job-details-container">
                <!-- Move back button inside -->
                <div class="back-section">
                    <a href="userdashboard.php" class="back-button">
                        <i class="fas fa-arrow-left"></i>
                        <span>Back to Jobs</span>
                    </a>
                </div>

                <div class="job-header">
                    <h2><?php echo htmlspecialchars($job['job_title']); ?></h2>
                    <div class="job-meta">
                        <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($job['location']); ?>, <?php echo htmlspecialchars($job['town']); ?></span>
                        <span><i class="fas fa-money-bill-wave"></i> ₹<?php echo htmlspecialchars($job['salary']); ?></span>
                        <span><i class="fas fa-calendar-plus"></i> Posted: <?php echo date('Y-m-d', strtotime($job['created_at'])); ?></span>
                        <span><i class="fas fa-calendar-alt"></i> Valid Until: <?php echo date('Y-m-d', strtotime($job['vacancy_date'])); ?></span>
                    </div>
                </div>

                <div class="job-content">
                    <div class="content-section">
                        <h3>Job Description</h3>
                        <p><?php echo nl2br(htmlspecialchars($job['job_description'])); ?></p>
                    </div>

                    <div class="content-section">
                        <h3>Additional Details</h3>
                        <div class="details-grid">
                            <div class="detail-item">
                                <span class="label">Vacancy:</span>
                                <span class="value"><?php echo htmlspecialchars($job['vacancy']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Working Days:</span>
                                <span class="value"><?php echo htmlspecialchars($job['working_days']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Working Hours:</span>
                                <span class="value"><?php echo htmlspecialchars($job['start_time']); ?> - <?php echo htmlspecialchars($job['end_time']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Category:</span>
                                <span class="value"><?php echo htmlspecialchars($job['category']); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="content-section">
                        <h3>Requirements</h3>
                        <div class="requirements-list">
                            <div class="requirement-item">
                                <i class="fas fa-id-card"></i>
                                <span>License Required: <?php if($job['license_required'])
                                                                {
                                                                    echo $job['license_required'];
                                                                }
                                                                else
                                                                {
                                                                    echo 'No';
                                                                }
                                                        ?>
                                </span>
                            </div>
                            <div class="requirement-item">
                                <i class="fas fa-certificate"></i>
                                <span>Badge Required: <?php if($job['badge_required'])
                                                                {
                                                                    echo $job['badge_required'];
                                                                }
                                                                else
                                                                {
                                                                    echo 'No';
                                                                }
                                                        ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="content-section">
                        <h3>Contact Information</h3>
                        <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($job['contact_no']); ?></p>
                    </div>

                    <div class="job-actions">
                        <button class="apply-btn">
                            <i class="fas fa-paper-plane"></i>
                            Apply Now
                        </button>
                        <button onclick="toggleBookmark(<?php echo $job_id; ?>)" class="save-btn <?php echo $is_bookmarked ? 'saved' : ''; ?>">
                            <?php if ($is_bookmarked): ?>
                                <i class="fas fa-bookmark"></i> Saved
                            <?php else: ?>
                                <i class="far fa-bookmark"></i> Save Job
                            <?php endif; ?>
                        </button>
                        <a href="report.php?user_id=<?php echo $user_id; ?>&job_id=<?php echo $job_id; ?>" class="report-link">
                        <button class="report-btn">
                            <i class="fas fa-flag"></i> Report
                        </button>
                        </a>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <style>
    .save-btn {
        transition: all 0.3s ease;
    }

    .save-btn.saved {
        background: #9747FF;
        color: white;
        border-color: #9747FF;
    }

    .save-btn.saved i {
        color: #FFD700;
        animation: bookmarkPop 0.3s ease;
    }

    @keyframes bookmarkPop {
        0% { transform: scale(1); }
        50% { transform: scale(1.2); }
        100% { transform: scale(1); }
    }

    .save-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(151, 71, 255, 0.2);
    }

    .save-btn.saved:hover {
        background: #8A2BE2;
        border-color: #8A2BE2;
    }

    /* Add animation for initial load if saved */
    .save-btn.saved i {
        animation: initialBookmarkPop 0.5s ease;
    }

    @keyframes initialBookmarkPop {
        0% { transform: scale(0.8); }
        50% { transform: scale(1.1); }
        100% { transform: scale(1); }
    }

    .report-btn {
        background-color: transparent;
        color: #FF5722;
        border: 1px solid #FF5722;
        padding: 10px 20px;
        border-radius: 8px;
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .report-btn:hover {
        background-color: #FF5722;
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(255, 87, 34, 0.2);
    }

    .report-btn i {
        font-size: 16px;
        transition: transform 0.3s ease;
    }

    .report-btn:hover i {
        transform: rotate(20deg);
    }

    .report-btn:active {
        transform: translateY(0);
        box-shadow: none;
    }
    </style>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const saveBtn = document.querySelector('.save-btn');
        if (saveBtn.classList.contains('saved')) {
            saveBtn.querySelector('i').style.animation = 'initialBookmarkPop 0.5s ease';
        }
    });

    function toggleBookmark(jobId) {
        const formData = new FormData();
        formData.append('job_id', jobId);

        // Debug log
        console.log('Sending job_id:', jobId);

        fetch('bookmarkprocess.php', {
            method: 'POST',
            body: formData,
            headers: {
                // Remove Content-Type header to let browser set it with boundary for FormData
            }
        })
        .then(response => {
            console.log('Raw response:', response); // Debug log
            return response.json();
        })
        .then(data => {
            console.log('Response data:', data); // Debug log
            if (data.success) {
                const btn = document.querySelector('.save-btn');
                if (data.action === 'bookmarked') {
                    btn.innerHTML = '<i class="fas fa-bookmark"></i> Saved';
                    btn.classList.add('saved');
                    btn.querySelector('i').style.animation = 'bookmarkPop 0.3s ease';
                } else if (data.action === 'unbookmarked') {
                    btn.innerHTML = '<i class="far fa-bookmark"></i> Save Job';
                    btn.classList.remove('saved');
                }
            } else {
                console.error('Error:', data.message); // Debug log
                alert(data.message || 'Error processing bookmark');
            }
        })
        .catch(error => {
            console.error('Fetch error:', error); // Debug log
            alert('Error processing bookmark');
        });
    }
    </script>
</body>
</html>
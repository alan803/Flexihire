<?php
    session_start();

    // Message handling
    if (isset($_SESSION['message'])) 
    {
        $message = $_SESSION['message'];
        $message_type = $_SESSION['message_type'] ?? 'info';
        unset($_SESSION['message']);
        unset($_SESSION['message_type']);
    }

    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) 
    {
        header("Location: ../login/loginvalidation.php");
        exit();
    }

    include '../database/connectdatabase.php';
    $dbname = "project";
    mysqli_select_db($conn, $dbname);

    // Get user data
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
        
        $display_name = !empty($user_data['username']) 
            ? $user_data['username'] 
            : $user_data['first_name'] . " " . $user_data['last_name'];
        
        $profile_image = $user_data['profile_image'];
        $_SESSION['display_name'] = $display_name;
    } else {
        error_log("Database error or user not found for ID: $user_id");
        session_destroy();
        header("Location: ../login/loginvalidation.php");
        exit();
    }

    // Fetch appointments for the user (excluding cancelled ones)
    $sql_appointments = "SELECT a.appointment_id, a.job_id, a.appointment_date, a.appointment_time, 
                           a.status, a.interview_type, a.location,
                           j.job_title, e.company_name
                    FROM tbl_appointments a
                    JOIN tbl_jobs j ON a.job_id = j.job_id
                    JOIN tbl_employer e ON j.employer_id = e.employer_id
                    WHERE a.user_id = ? AND a.status != 'cancelled'
                    ORDER BY a.appointment_date DESC, a.appointment_time DESC";

    $stmt = mysqli_prepare($conn, $sql_appointments);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $appointments_result = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointments | FlexiHire</title>
    <link rel="stylesheet" href="appointment.css">
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
                <span class="nav-username"><?php echo htmlspecialchars($display_name); ?></span>
                <?php 
                $profile_path = "/mini project/database/profile_picture/" . $profile_image;
                if (!empty($profile_image) && file_exists($_SERVER['DOCUMENT_ROOT'] . $profile_path)): ?>
                    <img src="<?php echo $profile_path; ?>" class="profile-pic" alt="Profile">
                <?php else: ?>
                    <img src="employer_pf/deafult.webp" class="profile-pic" alt="Default Profile">
                <?php endif; ?>
                <div class="dropdown-menu">
                    <a href="profiles/user/userprofile.php"><i class="fas fa-user"></i> Profile</a>
                    <a href="../login/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Toast Notification -->
    <div id="toast" class="toast">
        <div class="toast-content">
            <div class="toast-icon">
                <i class="fas"></i>
            </div>
            <div class="toast-message-container">
                <div class="toast-title"></div>
                <span id="toast-message"></span>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-menu">
                <a href="userdashboard.php"><i class="fas fa-home"></i> Home</a>
                <a href="applied.php"><i class="fas fa-paper-plane"></i> Applied Job</a>
                <a href="bookmark.php"><i class="fas fa-bookmark"></i> Bookmarks</a>
                <a href="appointment.php" class="active"><i class="fas fa-calendar"></i> Appointments</a>
                <a href="reportedjobs.php"><i class="fas fa-flag"></i> Reported Jobs</a>
                <a href="reviews.php"><i class="fas fa-star"></i> Reviews</a>
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
            <div class="header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <h2 style="font-size: 1.5rem; color: 'black';">Your Appointments</h2>
                <select id="status-filter" style="padding: 0.75rem; border: 1px solid rgba(0, 0, 0, 0.1); border-radius: 6px; background: var(--white);">
                    <option value="all">All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="accepted">Accepted</option>
                    <option value="rejected">Rejected</option>
                    <option value="interview scheduled">Interview Scheduled</option>
                </select>
            </div>

            <!-- Appointment Listings -->
            <div class="appointment-listings">
                <?php if ($appointments_result && mysqli_num_rows($appointments_result) > 0): ?>
                    <?php while ($appointment = mysqli_fetch_assoc($appointments_result)): ?>
                        <div class="appointment-card">
                            <div class="appointment-header">
                                <div class="header-left">
                                    <h3><?php echo htmlspecialchars($appointment['job_title']); ?></h3>
                                    <span class="company-name">
                                        <i class="fas fa-building"></i> 
                                        <?php echo htmlspecialchars($appointment['company_name']); ?>
                                    </span>
                                </div>
                                <div class="header-right">
                                    <span class="appointment-datetime">
                                        <i class="fas fa-calendar"></i> 
                                        <?php echo date('F j, Y', strtotime($appointment['appointment_date'])); ?>
                                        <i class="fas fa-clock ml-2"></i> 
                                        <?php echo date('g:i A', strtotime($appointment['appointment_time'])); ?>
                                    </span>
                                </div>
                            </div>

                            <div class="appointment-details">
                                <div class="detail-row">
                                    <div class="detail-item">
                                        <span class="label">Position</span>
                                        <span class="value"><?php echo htmlspecialchars($appointment['job_title']); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">Interview Type</span>
                                        <span class="value interview-type">
                                            <i class="fas fa-<?php echo $appointment['interview_type'] === 'Physical' ? 'building' : 
                                                ($appointment['interview_type'] === 'Online' ? 'video' : 'phone'); ?>"></i>
                                            <?php echo htmlspecialchars($appointment['interview_type']); ?> Interview
                                        </span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">
                                            <?php echo $appointment['interview_type'] === 'Physical' ? 'Location' : 
                                                ($appointment['interview_type'] === 'Online' ? 'Meeting Link' : 'Phone Number'); ?>
                                        </span>
                                        <span class="value">
                                            <?php if($appointment['interview_type'] === 'Online'): ?>
                                                <a href="<?php echo htmlspecialchars($appointment['location']); ?>" target="_blank" class="meeting-link">
                                                    <i class="fas fa-external-link-alt"></i> Join Meeting
                                                </a>
                                            <?php else: ?>
                                                <?php echo htmlspecialchars($appointment['location']); ?>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">Status</span>
                                        <span class="status-badge status-<?php echo strtolower($appointment['status']); ?>">
                                            <?php echo ucfirst(htmlspecialchars($appointment['status'])); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <?php if(!empty($appointment['notes'])): ?>
                                <div class="appointment-notes">
                                    <span class="label">Notes</span>
                                    <p><?php echo nl2br(htmlspecialchars($appointment['notes'])); ?></p>
                                </div>
                            <?php endif; ?>

                            <div class="appointment-actions">
                                <?php if($appointment['status'] === 'pending'): ?>
                                    <a href="reschedule_interview.php?appointment_id=<?php echo $appointment['appointment_id']; ?>" 
                                       class="action-btn btn-reschedule">
                                        <i class="fas fa-calendar-alt"></i> Reschedule
                                    </a>
                                    <a href="cancel_interview.php?appointment_id=<?php echo $appointment['appointment_id']; ?>" 
                                       class="action-btn btn-cancel"
                                       onclick="return confirm('Are you sure you want to cancel this interview?')">
                                        <i class="fas fa-times"></i> Cancel
                                    </a>
                                <?php endif; ?>
                                <?php if($appointment['interview_type'] === 'Online'): ?>
                                    <a href="<?php echo htmlspecialchars($appointment['location']); ?>" 
                                       target="_blank" 
                                       class="action-btn btn-join">
                                        <i class="fas fa-video"></i> Join Meeting
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-appointments">
                        <div class="no-appointments-content">
                            <div class="calendar-icon">
                                <i class="fas fa-calendar-times"></i>
                            </div>
                            <h2>No Appointments</h2>
                            <p>You have no scheduled appointments at this time.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script src="appointment.js"></script>
    <script>
    // Show toast message if exists
    <?php if (isset($message)): ?>
        document.addEventListener('DOMContentLoaded', function() {
            showToast('<?php echo addslashes($message); ?>', '<?php echo $message_type; ?>');
        });
    <?php endif; ?>

    function showToast(message, type = 'info') {
        const toast = document.getElementById('toast');
        const toastMessage = document.getElementById('toast-message');
        const toastTitle = toast.querySelector('.toast-title');
        const icon = toast.querySelector('i');
        
        // Reset classes
        toast.className = 'toast';
        icon.className = 'fas';
        
        // Set type-specific properties
        switch(type) {
            case 'success':
                toastTitle.textContent = 'Success';
                icon.classList.add('fa-check-circle');
                toast.classList.add('success');
                break;
            case 'error':
                toastTitle.textContent = 'Error';
                icon.classList.add('fa-exclamation-circle');
                toast.classList.add('error');
                break;
            default:
                toastTitle.textContent = 'Information';
                icon.classList.add('fa-info-circle');
                toast.classList.add('info');
        }
        
        toastMessage.textContent = message;
        toast.classList.add('show');
        
        // Hide toast after 4 seconds with fade out
        setTimeout(() => {
            toast.style.transition = 'opacity 0.5s ease';
            toast.style.opacity = '0';
            setTimeout(() => {
                toast.classList.remove('show');
                toast.style.opacity = '1'; // Reset opacity for next use
            }, 500); // Wait for fade out to complete
        }, 4000); // Show for 4 seconds
    }

    document.getElementById('status-filter').addEventListener('change', function() {
        const selectedStatus = this.value.toLowerCase();
        const appointmentCards = document.querySelectorAll('.appointment-card');

        appointmentCards.forEach(card => {
            const statusBadge = card.querySelector('.status-badge');
            if (!statusBadge) return;
            
            const cardStatus = statusBadge.textContent.trim().toLowerCase();
            
            if (selectedStatus === 'all' || cardStatus === selectedStatus) {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }
        });
    });
    </script>
</body>
</html>
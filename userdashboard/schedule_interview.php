<?php
session_start();
include '../database/connectdatabase.php';
$dbname = "project";
mysqli_select_db($conn, $dbname);

if (!isset($_SESSION['employer_id'])) {
    header("Location: ../login/loginvalidation.php");
    exit();
}

// Get job_id and application_id from URL
$job_id = $_GET['job_id'] ?? null;
$application_id = $_GET['application_id'] ?? null;

if (!$job_id || !$application_id) 
{
    header("Location: applicants.php?job_id=" . $job_id);
    exit();
}

// Get employer details and verify ownership
$employer_id = $_SESSION['employer_id'];

// Verify the application exists and belongs to this job
$sql = "SELECT a.user_id, a.job_id, j.job_title, u.first_name, u.last_name, j.interview
        FROM tbl_applications a
        JOIN tbl_jobs j ON a.job_id = j.job_id
        JOIN tbl_user u ON a.user_id = u.user_id
        WHERE a.id = ? AND a.job_id = ? AND j.employer_id = ?";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "iii", $application_id, $job_id, $employer_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$application = mysqli_fetch_assoc($result);

if (!$application || $application['interview'] !== 'yes') 
{
    header("Location: applicants.php?job_id=" . $job_id);
    exit();
}

$user_id = $application['user_id'];

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $appointment_date = $_POST["appointment_date"];
    $appointment_time = $_POST["appointment_time"];
    $interview_type = $_POST["interview_type"];
    $status = "pending";
    $notes = !empty($_POST["notes"]) ? $_POST["notes"] : NULL;

    // Set location based on interview type
    $location = NULL;
    $error = false;

    switch($interview_type) {
        case "Physical":
            if (empty($_POST["location"])) {
                $error_message = "Location is required for physical interviews";
                $error = true;
            } else {
                $location = $_POST["location"];
            }
            break;

        case "Online":
            if (empty($_POST["meeting_link"])) {
                $error_message = "Meeting link is required for online interviews";
                $error = true;
            } else {
                $location = $_POST["meeting_link"];
            }
            break;

        case "Phone":
            if (empty($_POST["phone_number"])) {
                $error_message = "Phone number is required for phone interviews";
                $error = true;
            } else {
                $location = $_POST["phone_number"];
            }
            break;
    }

    if (!$error) {
        try {
            // Start transaction
            mysqli_begin_transaction($conn);

            // Debug the received IDs
            echo "Debug: Received application_id: " . $application_id . "<br>";
            echo "Debug: Received job_id: " . $job_id . "<br>";

            // 1. First update the application status
            $update_sql = "UPDATE tbl_applications SET status = 'Interview Scheduled' WHERE id = ?";
            $update_stmt = mysqli_prepare($conn, $update_sql);
            mysqli_stmt_bind_param($update_stmt, "i", $application_id);
            
            if (!mysqli_stmt_execute($update_stmt)) {
                throw new Exception("Failed to update application status: " . mysqli_error($conn));
            }

            $rows_affected = mysqli_stmt_affected_rows($update_stmt);
            echo "Debug: Rows affected by update: " . $rows_affected . "<br>";

            if ($rows_affected === 0) {
                throw new Exception("No application found with ID: " . $application_id);
            }

            // 2. Then insert the appointment
            $insert_sql = "INSERT INTO tbl_appointments 
                          (user_id, job_id, employer_id, appointment_date, appointment_time, 
                           interview_type, status, location, notes) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $insert_stmt = mysqli_prepare($conn, $insert_sql);
            $status = 'Pending';
            mysqli_stmt_bind_param($insert_stmt, "iiissssss", 
                $user_id, 
                $job_id, 
                $employer_id, 
                $appointment_date, 
                $appointment_time,
                $interview_type, 
                $status,
                $location, 
                $notes
            );
            
            echo "Debug: Parameters:<br>";
            echo "user_id: $user_id<br>";
            echo "job_id: $job_id<br>";
            echo "employer_id: $employer_id<br>";
            echo "appointment_date: $appointment_date<br>";
            echo "appointment_time: $appointment_time<br>";
            echo "interview_type: $interview_type<br>";
            echo "status: $status<br>";
            echo "location: $location<br>";
            echo "notes: $notes<br>";
            
            if (!mysqli_stmt_execute($insert_stmt)) {
                throw new Exception("Failed to create appointment: " . mysqli_error($conn));
            }

            // If both operations succeeded, commit the transaction
            mysqli_commit($conn);

            // Verify final state
            $verify_sql = "SELECT * FROM tbl_applications WHERE id = ?";
            $verify_stmt = mysqli_prepare($conn, $verify_sql);
            mysqli_stmt_bind_param($verify_stmt, "i", $application_id);
            mysqli_stmt_execute($verify_stmt);
            $verify_result = mysqli_stmt_get_result($verify_stmt);
            $updated_data = mysqli_fetch_assoc($verify_result);
            
            // Redirect with success message
            header("Location: applicants.php?job_id=" . $job_id . "&success=interview_scheduled");
            exit();
        } catch (Exception $e) {
            // Rollback on error
            mysqli_rollback($conn);
            
            $error_message = "Error: " . $e->getMessage();
            echo "Debug: Error occurred - " . $error_message . "<br>";
            echo "Debug: Last MySQL Error: " . mysqli_error($conn) . "<br>";
        } finally {
            // Close statements
            if (isset($update_stmt)) mysqli_stmt_close($update_stmt);
            if (isset($insert_stmt)) mysqli_stmt_close($insert_stmt);
            if (isset($verify_stmt)) mysqli_stmt_close($verify_stmt);
        }
    }
}

// Get employer details for sidebar
$sql = "SELECT e.*, l.email 
        FROM tbl_employer e 
        JOIN tbl_login l ON e.employer_id = l.employer_id 
        WHERE e.employer_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $employer_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);

// Get job details
$sql_job = "SELECT job_title FROM tbl_jobs WHERE job_id = ? AND employer_id = ?";
$stmt_job = mysqli_prepare($conn, $sql_job);
mysqli_stmt_bind_param($stmt_job, "ii", $job_id, $employer_id);
mysqli_stmt_execute($stmt_job);
$result_job = mysqli_stmt_get_result($stmt_job);
$job = mysqli_fetch_assoc($result_job);

if (!$job) {
    header("Location: myjoblist.php");
    exit();
}

// Get applicant details
$sql = "SELECT u.first_name, u.last_name, j.job_title 
        FROM tbl_user u 
        JOIN tbl_jobs j ON j.job_id = ? 
        WHERE u.user_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $job_id, $user_id);
mysqli_stmt_execute($stmt);
$applicant_result = mysqli_stmt_get_result($stmt);
$applicant = mysqli_fetch_assoc($applicant_result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule Interview</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="employerdashboard.css">
    <link rel="stylesheet" href="schedule_interview.css">
    <style>
        /* Main container adjustments */
        .main-container {
            margin-left: 280px; /* Sidebar width */
            margin-top: 60px;  /* Navbar height */
            padding: 20px;
            min-height: calc(100vh - 60px);
            background: #f8f9fa;
        }

        /* Form container adjustments */
        .form-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        /* Form header adjustments */
        .form-header {
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .form-header h2 {
            margin: 0 0 10px 0;
            color: #333;
        }

        .form-header p {
            margin: 5px 0;
            color: #666;
        }

        /* Error message positioning */
        .error-message {
            margin-bottom: 20px;
            padding: 10px 15px;
            border-radius: 6px;
            background-color: #fee;
            color: #e33;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Form fields adjustments */
        .form-group {
            margin-bottom: 20px;
        }

        /* Interview fields adjustments */
        .interview-fields {
            margin-top: 15px;
        }

        /* Form actions */
        .form-actions {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
    </style>
</head>
<body>
    <!-- <div class="dashboard-container">
        
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
                <span class="company-name"><?php echo htmlspecialchars($row['company_name'] ?? 'Company Name'); ?></span>
                <span class="company-email"><?php echo htmlspecialchars($row['email'] ?? 'Email'); ?></span>
            </div>
            <nav class="nav-menu">
                <a href="employerdashboard.php" class="nav-item">
                    <i class="fas fa-th-large"></i>
                    <span>Dashboard</span>
                </a>
                <a href="postjob.php" class="nav-item">
                    <i class="fas fa-plus-circle"></i>
                    <span>Post a Job</span>
                </a>
                <a href="myjoblist.php" class="nav-item active">
                    <i class="fas fa-briefcase"></i>
                    <span>My Jobs</span>
                </a>
                <a href="applicants.php" class="nav-item">
                    <i class="fas fa-users"></i>
                    <span>Applicants</span>
                </a>
                <a href="interviews.php" class="nav-item">
                    <i class="fas fa-calendar-check"></i>
                    <span>Interviews</span>
                </a>
            </nav>
            <div class="settings-section">
                <a href="employer_profile.php" class="nav-item">
                    <i class="fas fa-user-cog"></i>
                    <span>My Profile</span>
                </a>
                <a href="../login/logout.php" class="nav-item">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div> -->
        
        <?php include 'sidebar.php'; ?>

        <!-- Main Content -->
        <div class="main-container">
            <div class="form-container">
                <div class="form-header">
                    <h2>Schedule Interview</h2>
                    <p>Scheduling for: <?php echo htmlspecialchars($applicant['first_name'] . ' ' . $applicant['last_name']); ?></p>
                    <p>Position: <?php echo htmlspecialchars($applicant['job_title']); ?></p>
                </div>

                <?php if(isset($error_message)): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <form method="post" action="">
                    <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user_id); ?>">
                    <input type="hidden" name="job_id" value="<?php echo htmlspecialchars($job_id); ?>">

                    <div class="form-group">
                        <label class="required">Appointment Date</label>
                        <input type="date" name="appointment_date" required>
                    </div>

                    <div class="form-group">
                        <label class="required">Appointment Time</label>
                        <input type="time" name="appointment_time" required>
                    </div>

                    <div class="form-group">
                        <label class="required">Interview Type</label>
                        <select name="interview_type" id="interview_type" required>
                            <option value="">Select Interview Type</option>
                            <option value="Physical">Physical Interview</option>
                            <option value="Online">Online Interview</option>
                            <option value="Phone">Phone Interview</option>
                        </select>
                    </div>

                    <div id="physical_fields" class="interview-fields" style="display:none;">
                        <div class="form-group">
                            <label class="required">Location</label>
                            <input type="text" name="location">
                        </div>
                    </div>

                    <div id="online_fields" class="interview-fields" style="display:none;">
                        <div class="form-group">
                            <label class="required">Meeting Link</label>
                            <input type="url" name="meeting_link">
                        </div>
                    </div>

                    <div id="phone_fields" class="interview-fields" style="display:none;">
                        <div class="form-group">
                            <label class="required">Phone Number</label>
                            <input type="text" name="phone_number">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Notes (Optional)</label>
                        <textarea name="notes"></textarea>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-calendar-check"></i>
                            Schedule Interview
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="schedule_interview.js"></script>
</body>
</html>
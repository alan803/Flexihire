<?php
// Add error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include '../database/connectdatabase.php';
$dbname = "project";
mysqli_select_db($conn, $dbname);

// Verify employer is logged in
if (!isset($_SESSION['employer_id'])) {
    header("Location: ../login/loginvalidation.php");
    exit();
}

$employer_id = $_SESSION['employer_id'];
$appointment_id = $_GET['appointment_id'] ?? null;

// Validate appointment_id
if (!$appointment_id) {
    $_SESSION['error'] = "Invalid appointment ID";
    header("Location: interviews.php");
    exit();
}

// Get appointment details with validation
$sql = "SELECT a.*, j.job_title, u.first_name, u.last_name 
        FROM tbl_appointments a
        JOIN tbl_jobs j ON a.job_id = j.job_id
        JOIN tbl_user u ON a.user_id = u.user_id
        WHERE a.appointment_id = ? AND a.employer_id = ?";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $appointment_id, $employer_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    $_SESSION['error'] = "Appointment not found";
    header("Location: interviews.php");
    exit();
}

$appointment = mysqli_fetch_assoc($result);

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get basic form data
    $appointment_date = $_POST["appointment_date"] ?? '';
    $appointment_time = $_POST["appointment_time"] ?? '';
    $interview_type = $_POST["interview_type"] ?? '';
    $notes = $_POST["notes"] ?? '';
    $error = false;

    // Validate required fields
    if (empty($appointment_date) || empty($appointment_time) || empty($interview_type)) {
        $error = true;
        $error_message = "Please fill in all required fields.";
    }

    // Get location based on interview type
    switch($interview_type) {
        case "Physical":
            $location = $_POST["location"] ?? '';
            if (empty($location)) {
                $error = true;
                $error_message = "Location is required for physical interviews.";
            }
            break;
        case "Online":
            $location = $_POST["meeting_link"] ?? '';
            if (empty($location)) {
                $error = true;
                $error_message = "Meeting link is required for online interviews.";
            }
            break;
        case "Phone":
            $location = $_POST["phone_number"] ?? '';
            if (empty($location)) {
                $error = true;
                $error_message = "Phone number is required for phone interviews.";
            }
            break;
        default:
            $error = true;
            $error_message = "Invalid interview type selected.";
    }

    if (!$error) {
        try {
            // Simple update query with all fields
            $sql = "UPDATE tbl_appointments 
                   SET appointment_date = ?,
                       appointment_time = ?,
                       interview_type = ?,
                       location = ?,
                       notes = ?
                   WHERE appointment_id = ? AND employer_id = ?";
            
            $stmt = mysqli_prepare($conn, $sql);
            if ($stmt === false) {
                throw new Exception("Failed to prepare statement: " . mysqli_error($conn));
            }

            mysqli_stmt_bind_param($stmt, "sssssii", 
                $appointment_date,
                $appointment_time,
                $interview_type,
                $location,
                $notes,
                $appointment_id,
                $employer_id
            );

            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Failed to execute update: " . mysqli_stmt_error($stmt));
            }

            if (mysqli_stmt_affected_rows($stmt) > 0) {
                $_SESSION['success'] = "Interview updated successfully";
                header("Location: interviews.php?success=interview_updated");
                exit();
            } else {
                $error_message = "No changes were made to the interview.";
            }

        } catch (Exception $e) {
            $error_message = "Error updating appointment: " . $e->getMessage();
            error_log($error_message);
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reschedule Interview</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="reschedule_interview.css">
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
            <div class="form-container">
                <div class="form-header">
                    <h2>Reschedule Interview</h2>
                    <p>Rescheduling for: <?php echo htmlspecialchars($appointment['first_name'] . ' ' . $appointment['last_name']); ?></p>
                    <p>Position: <?php echo htmlspecialchars($appointment['job_title']); ?></p>
                </div>

                <?php if(isset($error_message)): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <form method="post" action="">
                    <div class="form-group">
                        <label class="required">Appointment Date</label>
                        <input type="date" name="appointment_date" 
                               value="<?php echo htmlspecialchars($appointment['appointment_date']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="required">Appointment Time</label>
                        <input type="time" name="appointment_time" 
                               value="<?php echo htmlspecialchars($appointment['appointment_time']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="required">Interview Type</label>
                        <select name="interview_type" id="interview_type" required>
                            <option value="">Select Interview Type</option>
                            <option value="Physical" <?php echo ($appointment['interview_type'] == 'Physical') ? 'selected' : ''; ?>>Physical Interview</option>
                            <option value="Online" <?php echo ($appointment['interview_type'] == 'Online') ? 'selected' : ''; ?>>Online Interview</option>
                            <option value="Phone" <?php echo ($appointment['interview_type'] == 'Phone') ? 'selected' : ''; ?>>Phone Interview</option>
                        </select>
                    </div>

                    <!-- Debug output -->
                    <div style="display:none;">
                        Current type: <?php echo htmlspecialchars($appointment['interview_type']); ?>
                    </div>

                    <div id="physical_fields" class="interview-fields" 
                         style="display:<?php echo $appointment['interview_type'] === 'Physical' ? 'block' : 'none'; ?>">
                        <div class="form-group">
                            <label class="required">Location</label>
                            <input type="text" name="location" 
                                   value="<?php echo $appointment['interview_type'] === 'Physical' ? htmlspecialchars($appointment['location']) : ''; ?>">
                        </div>
                    </div>

                    <div id="online_fields" class="interview-fields" 
                         style="display:<?php echo $appointment['interview_type'] === 'Online' ? 'block' : 'none'; ?>">
                        <div class="form-group">
                            <label class="required">Meeting Link</label>
                            <input type="url" name="meeting_link" 
                                   value="<?php echo htmlspecialchars($appointment['location']); ?>">
                        </div>
                    </div>

                    <div id="phone_fields" class="interview-fields" 
                         style="display:<?php echo $appointment['interview_type'] === 'Phone' ? 'block' : 'none'; ?>">
                        <div class="form-group">
                            <label class="required">Phone Number</label>
                            <input type="text" name="phone_number" 
                                   value="<?php echo htmlspecialchars($appointment['location']); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Notes (Optional)</label>
                        <textarea name="notes"><?php echo htmlspecialchars($appointment['notes'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-actions">
                        <a href="interviews.php" class="btn btn-cancel">
                            <i class="fas fa-times"></i>
                            Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-calendar-check"></i>
                            Update Interview
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="reschedule_interview.js"></script>
</body>
</html>
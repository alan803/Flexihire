<?php
session_start();
include '../database/connectdatabase.php';

if (!isset($_SESSION['employer_id'])) {
    header("Location: ../login/loginvalidation.php");
    exit();
}

$employer_id = $_SESSION['employer_id'];
$dbname = "project";
mysqli_select_db($conn, $dbname);

// Fetch employer details
$sql = "SELECT u.company_name, l.email 
        FROM tbl_login AS l
        JOIN tbl_employer AS u ON l.employer_id = u.employer_id
        WHERE u.employer_id = ?";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $employer_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$username = "Unknown"; // Default value
$email = "Not Available";

if ($result && mysqli_num_rows($result) > 0) {
    $employer_data = mysqli_fetch_assoc($result);
    $email = $employer_data['email'];
    $username = $employer_data['company_name'];
}

mysqli_stmt_close($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Listings</title>
    <link rel="stylesheet" href="myjoblist.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
<div class="sidebar">
    <div class="logo-container">
        <img src="logo.png" alt="AutoRecruits.in">
    </div>
    <div class="company-info">
        <span><?php echo htmlspecialchars($username); ?></span>
    </div>
    <nav class="nav-menu">
        <div class="nav-item active">
            <i class="fas fa-th-large"></i>
            <a href="employerdashboard.php">Home</a>
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
            <i class="fas fa-calendar-check"></i>
            <a>Interviews</a>
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

<div class="job_list">
    <?php if (isset($_GET['message'])): ?>
        <div class="alert">
            <?php echo htmlspecialchars($_GET['message']); ?>
        </div>
    <?php endif; ?>

    <!-- Active Jobs Section -->
    <h2>Active Job</h2>
    <?php
    $sql = "SELECT job_id, job_title, salary, job_description, vacancy 
            FROM tbl_jobs 
            WHERE employer_id = ? AND is_deleted = 0";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $employer_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo '<div class="job-item">';
            echo '<h3>' . htmlspecialchars($row['job_title']) . '</h3>';
            echo '<p>Salary: ₹' . htmlspecialchars($row['salary']) . '</p>';
            echo '<p>Description: ' . htmlspecialchars($row['job_description']) . '</p>';
            echo '<p>Vacancies: ' . htmlspecialchars($row['vacancy']) . '</p>';
            echo '<div class="button-container">';
            echo '<button class="vacancy-button"><a href="editjob.php">Edit</a></button>';
            echo '<button class="vacancy-button">
                    <a href="deletejob.php?id=' . $row['job_id'] . '&action=deactivate" id="delete">Deactivate</a>
                  </button>';
            echo '</div>';
            echo '</div>';
        }
    } else {
        echo "<p>No active jobs available.</p>";
    }
    $stmt->close();
    ?>

    <!-- Inactive Jobs Section -->
    <h2>Inactive Job</h2>
    <?php
    $sql = "SELECT job_id, job_title, salary, job_description, vacancy 
            FROM tbl_jobs 
            WHERE employer_id = ? AND is_deleted = 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $employer_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo '<div class="job-item inactive">';
            echo '<h3>' . htmlspecialchars($row['job_title']) . ' (Inactive)</h3>';
            echo '<p>Salary: ₹' . htmlspecialchars($row['salary']) . '</p>';
            echo '<p>Description: ' . htmlspecialchars($row['job_description']) . '</p>';
            echo '<p>Vacancies: ' . htmlspecialchars($row['vacancy']) . '</p>';
            echo '<div class="button-container">';
            echo '<button class="vacancy-button">
                    <a href="restore_job.php?id=' . $row['job_id'] . '&action=activate" id="delete">Restore</a>
                  </button>';
            echo '</div>';
            echo '</div>';
        }
    } else {
        echo "<p>No inactive jobs available.</p>";
    }
    $stmt->close();
    ?>
</div>
</body>
</html>

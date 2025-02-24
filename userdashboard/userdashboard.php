<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/loginvalidation.php");
    exit();
}

include '../database/connectdatabase.php';
$dbname = "project";
mysqli_select_db($conn, $dbname);

// Initialize variables
$username = '';
$email = '';

// Get user data
$user_id = $_SESSION['user_id'];
$sql = "SELECT u.first_name, u.last_name, l.email, u.username, u.profile_image, u.user_id
    FROM tbl_login l
    JOIN tbl_user u ON l.user_id = u.user_id
    WHERE l.login_id = '$user_id'";

$result = mysqli_query($conn, $sql);

if ($result && mysqli_num_rows($result) > 0) {
    $user_data = mysqli_fetch_assoc($result);
    $email = $user_data['email'];
    $username = $user_data['first_name'] . " " . $user_data['last_name'];
    $new_username = $user_data['username'];
    $profile_image = $user_data['profile_image'];
    $employer_id = $user_data['user_id'];  // Set employer_id correctly
} else {
    error_log("Database error or user not found for ID: $user_id");
    session_destroy();
    header("Location: ../login/loginvalidation.php");
    exit();
}

// Fetch active jobs
$sql_fetch = "SELECT job_id, job_title, job_description, location, salary, vacancy_date FROM tbl_jobs WHERE is_deleted = 0";
$stmt = $conn->prepare($sql_fetch);
$stmt->execute();
$result_fetch = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
    <link rel="stylesheet" href="userdashboard.css">
    <script src="userdashboard.js"></script>
</head>
<body>

<span class="nav">
    <ul class="navbar">
        <li>
            <div class="profile-container">
                <?php if (!empty($profile_image)): ?>
                    <img src="/mini project/database/profile_picture/<?php echo $profile_image; ?>" id="profilePic" class="profile-pic" alt="Profile">
                <?php else: ?>
                    <img src="profile.png" id="profilePic" class="profile-pic" alt="Profile">
                <?php endif; ?>
                <div id="dropdownMenu" class="dropdown-menu">
                    <ul>
                        <li><p id="username"><?php echo !empty($new_username) ? $new_username : $username; ?></p></li>
                        <li><a href="profiles/user/userprofile.php">Profile</a></li>
                        <li><a href="../login/logout.php">Logout</a></li>
                    </ul>
                </div>
            </div>
        </li>
    </ul>
</span>

<div class="grid-container">
    <img src="grid.png" id="grid" class="grid">
</div>

<div class="sidebar" id="sidebar">
    <ul class="sidebar-menu">
        <li><a href="../userdashboard/userdashboard.html">Job List</a></li>
        <li><a href="../userdashboard/sidebar/jobgrid/jobgrid.html">Job Grid</a></li>
        <li><a href="../userdashboard/sidebar/applyjob/applyjob.html">Apply Job</a></li>
        <li><a href="../userdashboard/sidebar/jobdetails/jobdetails.html">Job Details</a></li>
        <li><a href="../userdashboard/sidebar/jobcategory/jobcategory.html">Job Category</a></li>
        <li><a href="../userdashboard/sidebar/appointment/appointment.html">Appointments</a></li>
        <li><a href="profiles/user/userprofile.php">Profile</a></li>
    </ul>
</div>

<div class="searchbar" id="searchbar">
    <input type="text" placeholder="Search your job" name="search" id="search">
    <input type="text" placeholder="Location" name="location" id="location">
    <div>
        <input type="text" placeholder="Min Salary" name="minsalary" id="minsalary">
        <input type="text" placeholder="Max Salary" name="maxsalary" id="maxsalary">
    </div>
    <input type="date" name="date" id="date">
</div>

<!-- Job Listings -->
<div class="job_list">
    <div class="job-container">
        <?php
    echo '<div class="job-container">'; // Start horizontal job container

    $sql = "SELECT job_id, job_title, salary, job_description, location, vacancy_date 
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
            echo '<p><strong>Description:</strong> ' . htmlspecialchars($row['job_description']) . '</p>';
            echo '<p><strong>Location:</strong> ' . htmlspecialchars($row['location']) . '</p>';
            echo '<p><strong>Salary:</strong> ₹' . htmlspecialchars($row['salary']) . '</p>';
            echo '<p><strong>Vacancy Date:</strong> ' . htmlspecialchars($row['vacancy_date']) . '</p>';
            echo '<div class="button-container">';
            // echo '<button class="edit-button"><a href="editjob.php?id=' . $row['job_id'] . '">Edit</a></button>';
            echo '<button class="delete-button"><a href="deletejob.php?id=' . $row['job_id'] . '&action=deactivate">Apply</a></button>';
            echo '</div>';
            echo '</div>';
        }
    } else {
        echo "<p>No active jobs available.</p>";
    }

    echo '</div>'; // End job container

    $stmt->close();
        ?>


    </div>
</div>

<script>
    const profilePic = document.getElementById('profilePic');
    const dropdownMenu = document.getElementById('dropdownMenu');
    const searchbar = document.getElementById('searchbar');
    const gridicon = document.getElementById('grid');
    const sidebar = document.getElementById('sidebar');

    // Toggle dropdown
    profilePic.addEventListener('click', function(event) {
        event.stopPropagation();
        dropdownMenu.classList.toggle('show');
    });

    document.addEventListener('click', function(event) {
        if (!profilePic.contains(event.target) && !dropdownMenu.contains(event.target)) {
            dropdownMenu.classList.remove('show');
        }
    });

    dropdownMenu.addEventListener('click', function(event) {
        event.stopPropagation();
    });

    // Sidebar toggle
    let isOpen = localStorage.getItem('sidebarOpen') === 'true';
    if (isOpen) {
        sidebar.classList.add('show');
    } else {
        searchbar.classList.add('expanded');
    }

    grid.addEventListener('click', function() {
        if (isOpen) {
            sidebar.classList.remove('show');
            searchbar.classList.add('expanded');
            isOpen = false;
        } else {
            sidebar.classList.add('show');
            searchbar.classList.remove('expanded');
            isOpen = true;
        }
        localStorage.setItem('sidebarOpen', isOpen);
    });
</script>

</body>
</html>

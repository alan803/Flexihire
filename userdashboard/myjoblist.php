<?php
    session_start();
    $employer_id=$_SESSION['employer_id'];
    include '../database/connectdatabase.php';
    $dbname="project";
    mysqli_select_db($conn,$dbname);
    $error="";

    if (!isset($_SESSION['employer_id'])) 
    {
        header("Location: ../login/loginvalidation.php");
        exit();
    }

    $sql = "SELECT u.company_name, l.email 
    FROM tbl_login AS l
    JOIN tbl_employer AS u ON l.employer_id = u.employer_id
    WHERE u.employer_id = ?";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $employer_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result && mysqli_num_rows($result) > 0) 
    {
        $employer_data = mysqli_fetch_array($result);
        $email = $employer_data['email'];
        $username = $employer_data['company_name'];
    } 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="myjoblist.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
<div class="sidebar">
        <div class="logo-container">
            <img src="logo.png" alt="AutoRecruits.in">
        </div>
        <div class="company-info">
            <span><?php echo $username;?></span>
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
                <a>My Profile</a>
            </div>
            <div class="nav-item">
                <i class="fas fa-sign-out-alt"></i>
                <a href="../login/logout.php">Logout</a>
            </div>
        </div>
    </div>
    <div class="job_list">
        <h2>Job Listings</h2>
        <?php
            $sql = "SELECT job_title, salary, job_description, vacancy FROM tbl_job_posting WHERE employer_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $employer_id);
            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                echo '<div class="job-item">';
                echo '<h3>' . htmlspecialchars($row['job_title']) . '</h3>';
                echo '<p>Salary: ' . htmlspecialchars($row['salary']) . '  ₹</p>';
                echo '<p>Description: ' . htmlspecialchars($row['job_description']) . '</p>';
                echo '<p>Vacancies: ' . htmlspecialchars($row['vacancy']) . '</p>';
                echo '<div class="button-container">';
                echo '<button class="vacancy-button">Total Vacancies: ' . htmlspecialchars($row['vacancy']) . '</button>';
                echo '<button class="vacancy-button">Available Vacancies</button>';
                echo '</div>';
                echo '</div>';
            }
            $stmt->close();
        ?>
    </div>
</body>
</html>
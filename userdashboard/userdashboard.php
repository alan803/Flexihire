<?php
    session_start();
    
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../login/login.php");
        exit();
    }

    include '../database/connectdatabase.php';
    $dbname = "project";
    mysqli_select_db($conn, $dbname);
    
    // Initialize variables with default values
    $username = 'Guest';
    $email = '';

    // Get user data from database using user_id
    $user_id = $_SESSION['user_id'];
    $sql = "SELECT * FROM tbl_login WHERE id='$user_id'"; // Changed from tbl_user to tbl_login

    // Check if query was successful
    $result = mysqli_query($conn, $sql);

    if ($result && mysqli_num_rows($result) > 0) {
        $user_data = mysqli_fetch_assoc($result);
        if ($user_data) {
            $email = $user_data['email'];
            $username = $user_data['username'];
        }
    } else {
        // Handle database error or missing user
        error_log("Database error or user not found for ID: $user_id");
        session_destroy();
        header("Location: ../login/login.php");
        exit();
    }

    // Get email from database instead of session
    // $email = $user_data['email'] ?? '';
    

    // Code for getting username
    // if ($email) 
    // {
        

    //     $user = mysqli_fetch_assoc($resultt);
    //     $username = $user['username'] ?? 'Guest';
    // } else {
    //     $username = 'Guest';
    // }

?>
<!DOCTYPE html>
<html lang="en">
<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="userdashboard.css">
    <script src="userdashboard.js"></script>
</head>
<body>
    <!-- <img id="logo" src="logowithoutbcakground.png"> -->
        <span class="nav">
            <ul class="navbar">
                <!-- <li id="list"><b><a href="homepage.html">Home</a></b></li> 
                <li id="list"><b><a href="">About</a></b></li>
                <li id="list"><b><a href="">Job</a></b></li>
                <li id="list"><b><a href="">Contact</a></b></li>-->
                <li>
                    <div class="profile-container">
                        <img src="profile.png" id="profilePic" class="profile-pic" alt="Profile">
                        <div id="dropdownMenu" class="dropdown-menu">
                            <ul>
                                <li><p id="username"><?php echo $username; ?></p></li>
                                <li id=dashb><a href="sam.php">Profile</a></li>
                                <li id="dashb"><a href="../login/logout.php">Logout</a></li>
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
                <li><a id="sidebar-item" href="../userdashboard/userdashboard.html">Job List</a></li>
                <li><a id="sidebar-item" href="../userdashboard/sidebar/jobgrid/jobgrid.html">Job Grid</a></li>
                <li><a id="sidebar-item" href="../userdashboard/sidebar/applyjob/applyjob.html">Apply job</a></li>
                <li><a id="sidebar-item" href="../userdashboard/sidebar/jobdetails/jobdetails.html">Job Details</a></li>
                <li><a id="sidebar-item" href="../userdashboard/sidebar/jobcategory/jobcategory.html">Job Category</a></li>
                <li><a id="sidebar-item" href="../userdashboard/sidebar/appointment/appointment.html">Appointments</a></li>
                <li><a id="sidebar-item" href="profiles/user/user.php">Profile</a></li>
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
        <div class="jobblock" id="jpbblock">
            <div class="job1" id="job1">
                
            </div>
        </div>
        

    <script>
        const profilePic = document.getElementById('profilePic');
        const dropdownMenu = document.getElementById('dropdownMenu');
        const searchbar=document.getElementById('searchbar');


        // Toggle dropdown when profile pic is clicked
        profilePic.addEventListener('click', function(event) {
            event.stopPropagation(); // Prevent event from bubbling up
            dropdownMenu.classList.toggle('show');
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            if (!profilePic.contains(event.target) && !dropdownMenu.contains(event.target)) {
                dropdownMenu.classList.remove('show');
            }
        });

        // Prevent dropdown from closing when clicking inside it
        dropdownMenu.addEventListener('click', function(event) {
            event.stopPropagation();
        });

        // sidebar
        const gridicon = document.getElementById('grid');
        const sidebar = document.getElementById('sidebar');
        
        // Check localStorage on page load
        let isOpen = localStorage.getItem('sidebarOpen') === 'true';
        
        // Set initial state based on localStorage
        if (isOpen) {
            sidebar.classList.add('show');
        }
        else
        {
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
            // Save state to localStorage
            localStorage.setItem('sidebarOpen', isOpen);
        });
    </script>
</body>
</html>
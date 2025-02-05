<?php
include '../database/connectdatabase.php';
$dbname = "project";
mysqli_select_db($conn, $dbname);

// Admin details
$admin_email = "adminflexihire2025@gmail.com";
$admin_password = "adminflexihire2025@";
$admin_username = "flexihireadmin";
$role = 3;  // Admin role

// Hash the password
$hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);

// Prepare the insert statement
$insert_admin = "INSERT INTO tbl_login (username, email, password, role) 
                 VALUES (?, ?, ?, ?)";

$stmt = mysqli_prepare($conn, $insert_admin);
mysqli_stmt_bind_param($stmt, "sssi", $admin_username, $admin_email, $hashed_password, $role);

if (mysqli_stmt_execute($stmt)) {
    echo "Admin account created successfully";
} else {
    echo "Error creating admin account: " . mysqli_error($conn);
}

mysqli_close($conn);
?>
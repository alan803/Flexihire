<?php
include 'connectdatabase.php';
$dbname = "project";

mysqli_select_db($conn, $dbname);

// Hash the password for security
$admin_email = "flexihire369@gmail.com";
$admin_password = password_hash("flexihire2025@", PASSWORD_DEFAULT);
$admin_role = 3; // Assuming role '3' represents admin

$sql = "INSERT INTO tbl_login (email, password, role) 
        VALUES ('$admin_email', '$admin_password', '$admin_role')";

if (mysqli_query($conn, $sql)) {
    echo "Admin account added successfully.";
} else {
    die("Error inserting admin data: " . mysqli_error($conn));
}

mysqli_close($conn);
?>

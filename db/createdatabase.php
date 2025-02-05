<?php
include 'connect.php';


$databasename = "taste";
$sql = "CREATE DATABASE IF NOT EXISTS $databasename";


if (mysqli_query($conn, $sql)) {
    echo "Database created successfully";
} else {
    echo "Error creating database: " . mysqli_error($conn);
}


mysqli_close($conn);
?>

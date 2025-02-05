<?php
$servername = "localhost";
$username = "root";
$password = "";


// Connect to MySQL server and select the database
$conn = mysqli_connect($servername, $username, $password);


// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
} else {
    echo "Connected to the database server and selected database successfully!<br>";
}
?>

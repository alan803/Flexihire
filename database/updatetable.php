<?php
include 'connectdatabase.php';
$dbname="project";
mysqli_select_db($conn,$dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// SQL query to drop the table
$sql = "DROP TABLE categories";

// Execute the query
if ($conn->query($sql) === TRUE) {
    echo "Table dropped successfully";
} else {
    echo "Error dropping table: " . $conn->error;
}

// Close the connection
$conn->close();
?>

<?php
include 'connectdatabase.php';

$sql = "ALTER TABLE tbl_jobs ADD COLUMN is_deleted TINYINT(1) DEFAULT 0";

if ($conn->query($sql) === TRUE) {
    echo "Column 'is_deleted' added successfully.";
} else {
    echo "Error adding column: " . $conn->error;
}

$conn->close();
?>

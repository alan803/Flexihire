<?php
    include'connectdatabase.php';
    $dbname = "project";
    $table = "tbl_login"; // Table name

    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // SQL to change data type of 'role' column from ENUM to INT
    $sql = "ALTER TABLE $table CHANGE role role INT(11) NOT NULL";

    if ($conn->query($sql) === TRUE) {
        echo "Column 'role' data type changed to INT successfully";
    } else {
        echo "Error modifying column: " . $conn->error;
    }

    // Close connection
    $conn->close();
?>

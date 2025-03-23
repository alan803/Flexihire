<?php
    include 'connectdatabase.php';
    $dbname="project";
    mysqli_select_db($conn,$dbname);

    $sql="ALTER TABLE tbl_login 
ADD COLUMN avg_rating FLOAT DEFAULT 0, 
ADD COLUMN total_reviews INT DEFAULT 0;";

if(mysqli_query($conn,$sql))
{
    echo    "Table updated successfully";
}
else
{
    echo "Error updating table: " . mysqli_error($conn);
}

mysqli_close($conn);

?>
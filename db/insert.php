<?php
    include 'connect.php';
    $databasename="taste";
    mysqli_select_db($conn,$databasename);

    if($_SERVER['REQUEST_METHOD']=='POST')
    {
        $name=$_POST['name'];
        $age=$_POST['age'];

        $sql="INSERT INTO employe(name,age)  VALUES('$name','$age')";
        if(mysqli_query($conn,$sql))
        {
            echo "value inserted";
        }
        else
        {
            die("error ".mysqli_error($conn));
        }
    }
    mysqli_close($conn);
?>
<html>
    <body>
        <form method="POST">
            <input type="text" name="name" placeholder="Enter ur name"><br>
            <input type="text" name="age" placeholder="Enter ur age"><br>
            <input type="submit">
        </form>
    </body>
</html>
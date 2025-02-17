<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" type="text/css" href="postjob.css">
</head>
<body>
    <div class="form-container">
        <form method="post" id="add_job">
            <input type="text" name="job_title" id="job_title" placeholder="Enter the jobtitle"><br>
            <input type="text" name="location" id="location" placeholder="Enter the location"><br>
            <input type="text" name="job_description" id="job_description" placeholder="Enter job_description"><br>
            <input type="text" name="working_hour" id="working_hour" placeholder="Enter the time limit of the job"><br>
            <label>Vacancy date : </label><br>
            <input type="date" name="date" id="date"><br>
            <input type="text" name="vacancy" id="vacancy" placeholder="Enter the no of vacancy"><br>
            <input type="text" name="salary" id="salary" placeholder="Enter the salary"><br>
            <label>Application time limit : </label><br>
            <input type="date" name="last_date" id="last_date"><br>
            <input type="submit" value="Add Job">
        </form>
    </div>
</body>
</html>
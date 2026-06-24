<?php
// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database connection
$server = "localhost";
$user = "root";
$password = "";
$db = "miniprjct";

$con = mysqli_connect($server, $user, $password, $db);
if (!$con) {
    die("Connection failed: " . mysqli_connect_error());
}

// Function to fetch patients based on search term
function fetchPatients($con, $searchTerm) {
    $searchTerm = mysqli_real_escape_string($con, $searchTerm);
    $sql = "SELECT pid, name, age,address FROM reg 
            WHERE name LIKE '%$searchTerm%' 
           
            OR address LIKE '%$searchTerm%'";
    return mysqli_query($con, $sql);
}

$patients = fetchPatients($con, ''); // Default fetch for all patients
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient List</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400..700&display=swap" rel="stylesheet">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/minipro/css/pat_list.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
table tr {
    cursor: pointer;
}
/* Styles for table rows */
table tr:nth-child(even) {
    background-color: #f9f9f9; /* Light grey color for even rows */
}

table tr:nth-child(odd) {
    background-color: #ffffff; /* White color for odd rows */
}

/* Hover effect */
table tr:hover {
    background-color: #c8d0d9; /* Light grey color when hovering */
}

</style>
</head>

<body>
<div class="container">
    <h1>Patient List</h1>
    
    <!-- Search Form -->
    <div class="mb-4">
        <input type="text" id="searchTerm" class="form-control" placeholder="Search by Name, Phone, Email, etc.">
    </div>

    <div class="table-responsive"> 
        <table class="table table-striped" id="patientTable">
            <thead>
                <tr>
                    <th>Patient ID</th>
                    <th>Name</th>
                    <th>Age</th> 
                    <th>Address</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (mysqli_num_rows($patients) > 0) {
                    while ($row = mysqli_fetch_assoc($patients)) {
                        echo "<tr onclick=\"window.location.href='consultation.php?pid=" . $row['pid'] . "'\">";
                        echo "<td>" . $row['pid'] . "</td>";
                        echo "<td>" . $row['name'] . "</td>";
                        echo "<td>" . $row['age'] . "</td>";
                        echo "<td>" . $row['address'] . "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='10' class='text-center'>No Patients Found</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div> 
</div>


    <script>
        $(document).ready(function() {
            
            $("#searchTerm").on("input", function() {
                let searchTerm = $(this).val();

                $.ajax({
                    url: "fetch.php", 
                    type: "POST",
                    data: { searchTerm: searchTerm },
                    success: function(data) {
                        $("#patientTable tbody").html(data);
                    }
                });
            });
        });
    </script>
</body>
</html>

<?php

if (isset($con) && $con) {
    mysqli_close($con);
}
?>

<?php
// Database connection
$server = "localhost";
$user = "root";
$password = "";
$db = "miniprjct";

$con = mysqli_connect($server, $user, $password, $db);
if (!$con) {
    die("Connection failed: " . mysqli_connect_error());
}

// Initialize an empty result variable
$resultData = '';

if (isset($_POST['searchTerm'])) {
    $searchTerm = mysqli_real_escape_string($con, $_POST['searchTerm']);
    $sql = "SELECT pid, name,age, address FROM reg 
            WHERE name LIKE '%$searchTerm%' 
            OR address LIKE '%$searchTerm%'";
    $result = mysqli_query($con, $sql);

    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            // Make sure pid is correctly included in the onclick function
            $resultData .= "<tr onclick=\"window.location.href='consultation.php?pid=" . $row['pid'] . "'\">";
            $resultData .= "<td>" . $row['pid'] . "</td>";
            $resultData .= "<td>" . $row['name'] . "</td>";
            $resultData .= "<td>" . $row['age'] . "</td>";
            $resultData .= "<td>" . $row['address'] . "</td>";
            $resultData .= "</tr>";
        }
    } else {
        $resultData = "<tr><td colspan='3' class='text-center'>No Patients Found</td></tr>";
    }
}

// Close database connection
mysqli_close($con);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Patients</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        tr:hover {
            background-color: #f1f1f1;
            cursor: pointer;
        }
        .text-center {
            text-align: center;
        }
    </style>
    <script>
        function searchPatients() {
            const searchTerm = document.getElementById('searchTerm').value;

            const xhr = new XMLHttpRequest();
            xhr.open("POST", "", true);  // Keeping it as the same page
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onreadystatechange = function () {
                if (xhr.readyState === XMLHttpRequest.DONE) {
                    document.getElementById('results').innerHTML = xhr.responseText;
                }
            };
            xhr.send("searchTerm=" + encodeURIComponent(searchTerm));
        }
    </script>
</head>
<body>

<h1>Search Patients</h1>
<input type="text" id="searchTerm" placeholder="Enter patient name or address" onkeyup="searchPatients()">

<table>
    <thead>
        <tr>
            <th>Patient ID</th>
            <th>Name</th>
            <th>Age</th>
            <th>Address</th>            
        </tr>
    </thead>
    <tbody id="results">
        <!-- Search results will be displayed here -->
        <?php echo $resultData; ?>
    </tbody>
</table>

</body>
</html>

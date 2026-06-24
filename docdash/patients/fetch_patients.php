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

// Check if search term is set
if (isset($_POST['searchTerm'])) {
    $searchTerm = mysqli_real_escape_string($con, $_POST['searchTerm']);
    $sql = "SELECT pid, name, dob, age, phone, email, gender, marital, blood, address FROM reg 
            WHERE name LIKE '%$searchTerm%' 
            OR phone LIKE '%$searchTerm%' 
            OR email LIKE '%$searchTerm%' 
            OR gender LIKE '%$searchTerm%' 
            OR marital LIKE '%$searchTerm%' 
            OR blood LIKE '%$searchTerm%' 
            OR address LIKE '%$searchTerm%'";
    $result = mysqli_query($con, $sql);

    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr>";
            echo "<td>" . $row['pid'] . "</td>";
            echo "<td>" . $row['name'] . "</td>";
            echo "<td>" . date('d-m-Y', strtotime($row['dob'])) . "</td>";
            echo "<td>" . $row['age'] . "</td>";
            echo "<td>" . $row['phone'] . "</td>";
            echo "<td>" . $row['email'] . "</td>";
            echo "<td>" . $row['gender'] . "</td>";
            echo "<td>" . $row['marital'] . "</td>";
            echo "<td>" . $row['blood'] . "</td>";
            echo "<td>" . $row['address'] . "</td>";
            echo "</tr>";
        }
    } else {
        echo "<tr><td colspan='10' class='text-center'>No Patients Found</td></tr>";
    }
}

// Close database connection
mysqli_close($con);
?>

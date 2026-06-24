<?php
// Start session if not already started
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

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve and sanitize input data
    $mid = mysqli_real_escape_string($con, $_POST['mid']);
    $mname = mysqli_real_escape_string($con, $_POST['mname']);
    $dosage = mysqli_real_escape_string($con, $_POST['dosage']);
    $price = mysqli_real_escape_string($con, $_POST['price']);
    $stock = mysqli_real_escape_string($con, $_POST['stock']);

    // Update query
    $sql = "UPDATE medicines SET 
            mname = '$mname',
            dosage = '$dosage',
            price = '$price',
            stock = '$stock'
            WHERE mid = '$mid'";

    if (mysqli_query($con, $sql)) {
        echo "Medicine updated successfully.";
    } else {
        echo "Error updating medicine: " . mysqli_error($con);
    }

    mysqli_close($con);
}
?>

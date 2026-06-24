<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

$server = "localhost";
$user = "root";
$password = "";
$db = "miniprjct";

// Connect to the database
$con = mysqli_connect($server, $user, $password, $db);

// Check the connection
if (!$con) {
    die("Connection failed: " . mysqli_connect_error());
}

if (isset($_POST['username'])) {
    $username = $_POST['username'];

    // Log the incoming username for debugging
    error_log("Received username: $username");

    // Check if the username is in email format or phone number
    if (filter_var($username, FILTER_VALIDATE_EMAIL)) {
        $query = "SELECT * FROM signup WHERE username = ?";
    } else if (preg_match('/^\d{10}$/', $username)) {
        $query = "SELECT * FROM signup WHERE username = ?";
    } else {
        echo "Invalid username format"; // Invalid username format
        exit;
    }

    // Prepare and execute the query
    if ($stmt = $con->prepare($query)) { // Use $con instead of $conn
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        // Check if the username already exists
        if ($stmt->num_rows > 0) {
            echo "exists"; // Username already exists
        } else {
            echo "available"; // Username is available
        }

        $stmt->close();
    } else {
        echo "error preparing statement"; // Database error
        error_log("Failed to prepare the statement.");
    }

    $con->close(); // Close the connection
} else {
    echo "error"; // No username provided
    error_log("No username received.");
}
?>

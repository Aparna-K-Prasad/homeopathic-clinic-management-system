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

// Check if the mids are set in the POST request
if (isset($_POST['mids']) && is_array($_POST['mids'])) {
    $mids = $_POST['mids'];

    // Prepare the delete statement
    $stmt = $con->prepare("DELETE FROM medicines WHERE mid = ?");

    // Check if the statement was prepared successfully
    if ($stmt === false) {
        http_response_code(500); // Internal server error
        echo "Error: " . mysqli_error($con);
        exit;
    }

    // Loop through each mid and execute the delete statement
    foreach ($mids as $mid) {
        $stmt->bind_param("s", $mid); // Bind the mid parameter
        if (!$stmt->execute()) {
            http_response_code(500); // Internal server error
            echo "Error: " . mysqli_error($con);
            exit;
        }
    }

    // Close the statement
    $stmt->close();
    echo "Success"; // Return success message
} else {
    http_response_code(400); // Bad request
    echo "No medicine IDs provided.";
}

mysqli_close($con);
?>

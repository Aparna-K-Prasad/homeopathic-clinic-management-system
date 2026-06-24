<?php
session_start();

$server = "localhost";
$user = "root";
$password = "";
$db = "miniprjct";

// Connect to the database
$con = mysqli_connect($server, $user, $password, $db);
if (!$con) {
    die("Connection failed: " . mysqli_connect_error());
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve and sanitize form data
    $username = mysqli_real_escape_string($con, $_POST['username']);
    $password = mysqli_real_escape_string($con, $_POST['password']);

    // Check if the username exists in the signup table
    $stmt = $con->prepare("SELECT * FROM signup WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // User found, verify password
        $userData = $result->fetch_assoc();

        if (password_verify($password, $userData['password'])) {
            // Password is correct
            $_SESSION['username'] = $username;

            // Check for redirection after clicking "Registration"
            if (isset($_POST['redirectToReg']) && $_POST['redirectToReg'] === 'true') {
                header("Location:/minipro/reg/reg.php");
                exit();
            }

            // Check if user came to book an appointment
            if (isset($_POST['redirectToBooking']) && $_POST['redirectToBooking'] === 'true') {
                header("Location:/minipro/appt/appt.php");
            } else {
                // Default redirection based on user type
                if ($username === "doc12@gmail.com") {
                    header("Location: /minipro/docdash/dash.html");
                } else {
                    header("Location: /minipro/patdash/pdash.html");
                }
            }
            exit();
        } else {
            echo "Invalid password.";
        }
    } else {
        echo "Username not found.";
    }

    $stmt->close();
} else {
    // If user is already logged in, redirect based on username
    if (isset($_SESSION['username'])) {
        if ($_SESSION['username'] === "doc12@gmail.com") {
            header("Location: /minipro/docdash/dash.html");
        } else {
            header("Location: /minipro/patdash/pdash.html");
        }
        exit();
    }
}

mysqli_close($con);
?>

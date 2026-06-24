<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
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

// Handle the registration process
$message = ""; // For error messages
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $uname = $_POST['username'];
    $pwd = $_POST['password'];
    $cpassword = $_POST['cpassword']; // Confirm Password

    if (empty($uname) || empty($pwd) || empty($cpassword)) {
        $message = 'Username and passwords cannot be empty.';
    } elseif ($pwd !== $cpassword) {
        $message = 'Passwords do not match.';
    } else {
        $uname = mysqli_real_escape_string($con, $uname);
        $pwd = mysqli_real_escape_string($con, $pwd);

        // Check if username already exists
        $query = "SELECT username FROM signup WHERE username=?";
        $stmt = $con->prepare($query);
        $stmt->bind_param("s", $uname);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $message = 'Username already exists.';
        } else {
            // Hash the password
            $hashed_pwd = password_hash($pwd, PASSWORD_DEFAULT);

            $stmt = $con->prepare("INSERT INTO signup (username, password) VALUES (?, ?)");
            $stmt->bind_param("ss", $uname, $hashed_pwd);

            if ($stmt->execute()) {
                $_SESSION['username'] = $uname;
                header("Location:/minipro/reg/reg.php");
                exit();
            } else {
                $message = "Error: " . $stmt->error;
            }

            $stmt->close();
        }
    }
}

mysqli_close($con);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Signup Page</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400..700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/minipro/css/signlog.css"> <!-- Link to the CSS file -->
    <link rel="stylesheet" href="https://unpkg.com/boxicons/css/boxicons.min.css">
</head>
<body>
    <header>
        <div class="header-container">
            <h1>Roopa's Homeo Clinic</h1>
            <nav>
                <ul>
                    <li><a href='/minipro/home/home.html'>Home</a></li>
                    <li><a href='/minipro/signlog/log.html'>Login</a></li>
                </ul>
            </nav>
        </div>
    </header>
    <div class="container">
        <h1><i class="bx bx-user-plus"></i> Signup</h1>

        <?php if ($message): ?>
            <div class="alert alert-danger"><?php echo $message; ?></div>
        <?php endif; ?>

        <form method="POST" id="signupForm">
            <div class="mb-3 position-relative">
                <label for="username" class="form-label"><i class="bx bx-user"></i> Username (Email/Phone):</label>
                <input type="text" class="form-control" id="username" name="username" required>
                <div id="usernameError" class="error text-danger"></div>
            </div>

            <div class="mb-3 position-relative">
                <label for="password" class="form-label"><i class="bx bx-lock"></i> Password:</label>
                <input type="password" class="form-control" id="password" name="password" required>
                <div id="passwordError" class="error"></div>
            </div>

            <div class="mb-3 position-relative">
                <label for="cpassword" class="form-label"><i class="bx bx-lock"></i> Confirm Password:</label>
                <input type="password" class="form-control" id="cpassword" name="cpassword" required>
                <div id="confirmPasswordError" class="error"></div>
            </div>

            <!-- Toggle Password Visibility -->
            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="showPassword">
                <label class="form-check-label" for="showPassword">Show Password</label>
            </div>

            <div class="text-center">
                <button type="submit" class="btn btn-primary mt-4"><i class="bx bx-user-plus"></i> Sign Up</button>
            </div>
            <p id="error-message" style="color: rgb(148, 61, 61); display: none;">Invalid username or password.</p>
        </form>
    </div>

    <script src="/minipro/js/sign.js"></script> <!-- Link to the JavaScript file -->
</body>
</html>

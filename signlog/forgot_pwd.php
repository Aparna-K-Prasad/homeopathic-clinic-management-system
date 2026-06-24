<?php

// Database connection
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "miniprjct"; // Replace with your database name

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$alertMessage = $alertType = "";
// Handle AJAX request to validate the username
if (isset($_POST['check_username'])) {
    $username = $_POST['check_username'];

    $stmt = $conn->prepare("SELECT * FROM signup WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    echo ($result->num_rows > 0) ? "valid" : "invalid";
    $stmt->close();
    exit;
}

// Handle password reset on form submission
$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['check_username'])) {
    $username = $_POST['username'];
    $new_password = $_POST['new_password'];

    // Hash the new password
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    $update_password = $conn->prepare("UPDATE signup SET password = ? WHERE username = ?");
    $update_password->bind_param("ss", $hashed_password, $username);

    if ($update_password->execute()) {
        $alertMessage = "Password updated successfully.";
        $alertType = "success";
    } else {
        $alertMessage = "Error updating password.";
        $alertType = "error";
    }
    $update_password->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>

    <!-- Fonts and CSS -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400..700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://unpkg.com/boxicons/css/boxicons.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
      function showAlert(message, type) {
        Swal.fire({
            icon: type,
            title: type === 'success' ? 'Success' : 'Error',
            text: message,
            confirmButtonText: 'OK'
        }).then(() => {
                // Navigate back after the alert is closed
              window.location.href= log.html;
            });
        }
        function togglePassword() {
            const passwordField = document.getElementById("new_password");
            const toggleIcon = document.getElementById("toggle-password");
            if (passwordField.type === "password") {
                passwordField.type = "text";
                toggleIcon.classList.replace("bx-show", "bx-hide");
            } else {
                passwordField.type = "password";
                toggleIcon.classList.replace("bx-hide", "bx-show");
            }
        }

        // Display feedback function
        function displayFeedback(element, message, color) {
            element.textContent = message;
            element.style.color = color;
        }

        // AJAX to validate the username
        function validateUsername() {
            const username = document.getElementById("username").value;
            const usernameFeedback = document.getElementById("username-feedback");
            const passwordFeedback = document.getElementById("password-feedback");

            displayFeedback(passwordFeedback, "", "");

            if (username.trim() === "") return;

            const xhr = new XMLHttpRequest();
            xhr.open("POST", "", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    if (xhr.responseText === "valid") {
                        displayFeedback(usernameFeedback, "Username found!", "green");
                    } else {
                        displayFeedback(usernameFeedback, "Username not found, please ensure you have entered the correct username!", "red");
                    }
                }
            };
            xhr.send("check_username=" + encodeURIComponent(username));
        }

        // Password validation
        function validatePasswordField() {
            const password = document.getElementById("new_password").value;
            const passwordFeedback = document.getElementById("password-feedback");
            const passwordPattern = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]).{6,}$/;

            if (passwordPattern.test(password)) {
                displayFeedback(passwordFeedback, "Strong password!", "green");
            } else {
                displayFeedback(passwordFeedback, "Password must be at least 6 characters with uppercase, lowercase, numbers, and symbols.", "red");
            }
        }
    </script>

    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #f5f5f5;
        }

        .reset-box {
            width: 800px;
            height: 400px;
            padding: 20px;
            background-color: white;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
        }

        h2 {
            font-family: 'Dancing Script', cursive;
            font-size: 30px;
            text-align: center;
            margin-bottom: 20px;
            color: #1c77fd;
        }

        .toggle-icon {
            position: absolute;
            right: 15px;
            top: 38px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <?php if ($alertMessage): ?>
    <script>
        Swal.fire({
            icon: '<?php echo $alertType; ?>',
            title: '<?php echo $alertType === "success" ? "Success" : "Error"; ?>',
            text: '<?php echo $alertMessage; ?>',
            confirmButtonText: 'OK'
        }).then((result) => {
            if (result.isConfirmed && '<?php echo $alertType; ?>' === 'success') {
                window.location.href = 'log.html';
            }
        });
    </script>
    <?php endif; ?>

    <div class="reset-box">
        <h2><i class="bx bx-key"></i> Reset Password</h2>

        <?php if ($message): ?>
            <div class="alert alert-danger"><?php echo $message; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3 position-relative">
                <label for="username" class="form-label"><i class="bx bx-user"></i> Username (Email/Phone):</label>
                <input type="text" class="form-control" id="username" name="username" required onblur="validateUsername()">
                <div id="username-feedback" class="form-text"></div>
            </div>

            <div class="mb-3 position-relative">
                <label for="new_password" class="form-label"><i class="bx bx-lock"></i> New Password:</label>
                <input type="password" class="form-control" id="new_password" name="new_password" required oninput="validatePasswordField()">
                <i class="bx bx-show toggle-icon" id="toggle-password" onclick="togglePassword()"></i>
                <div id="password-feedback" class="form-text"></div>
            </div>

            <div class="text-center">
                <button type="submit" class="btn btn-primary mt-5"><i class="bx bx-refresh"></i> Reset Password</button>
            </div>
        </form>
    </div>
</body>
</html>

<?php
session_start(); // Start session before any output
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "miniprjct";

// Buffer the output
ob_start();

// Function to show SweetAlert
// Function to show SweetAlert
function showAlert($icon, $title, $text, $redirect = null) {
    return "<script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: '{$icon}',
                title: '{$title}',
                text: '{$text}',
                confirmButtonText: 'OK',
            }).then((result) => {
                if (result.isConfirmed) {
                    " . ($redirect ? "setTimeout(() => { window.location.href = '{$redirect}'; }, 500);" : "") . "
                } else if (result.isDismissed) {
                    window.history.back(); // Go back to the previous page
                }
            });
        });
    </script>";
}



$alertMessage = ''; // Store alert messages

// Connect to database
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    $alertMessage = showAlert('error', 'Connection Failed', 'Database connection failed', '/minipro/patdash/appt/appt.php');
} else {
    $loggedInUsername = $_SESSION['username'] ?? null;

    if (!$loggedInUsername) {
        $alertMessage = showAlert('error', 'Authentication Error', 'Please login first', '/minipro/signlog/log.html');
    } else {
        // Get patient ID
        $sql = "SELECT pid FROM reg WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $loggedInUsername);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $alertMessage = showAlert('error', 'User Not Found', 'Please register first', '/minipro/patdash/profile.php');
        } else {
            $pid = $result->fetch_assoc()['pid'];

            // Process appointment booking
            if ($_SERVER["REQUEST_METHOD"] == "POST") {
                if (!isset($_POST['appt_date']) || !isset($_POST['appt_time'])) {
                    $alertMessage = showAlert('warning', 'Missing Information', 'Please select both date and time', '/minipro/patdash/appt/appt.php');
                } else {
                    $appt_date = $_POST['appt_date'];
                    $appt_time = $_POST['appt_time'];
                    $request_status = "Pending";

                    // Check existing appointment
                    $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM appointment_request WHERE requested_date = ? AND requested_time = ?");
                    $checkStmt->bind_param("ss", $appt_date, $appt_time);
                    $checkStmt->execute();
                    $checkResult = $checkStmt->get_result();
                    $checkRow = $checkResult->fetch_assoc();

                    if ($checkRow['count'] > 0) {
                        $alertMessage = showAlert('warning', 'Time Slot Unavailable', 'This time slot is already booked', '/minipro/patdash/appt/appt.php');
                    } else {
                        // Check token limit
                        $tokenStmt = $conn->prepare("SELECT COUNT(*) as count FROM appointment_request WHERE requested_date = ? AND pid = ?");
                        $tokenStmt->bind_param("ss", $appt_date, $pid);
                        $tokenStmt->execute();
                        $tokenResult = $tokenStmt->get_result();
                        $tokenRow = $tokenResult->fetch_assoc();

                        if ($tokenRow['count'] >= 12) {
                            $alertMessage = showAlert('error', 'Token Limit Reached', 'Maximum appointments reached for this date', '/minipro/patdash/appt/appt.php');
                        } else {
                            // Insert appointment
                            $insertStmt = $conn->prepare("INSERT INTO appointment_request (pid, requested_date, requested_time, request_status) VALUES (?, ?, ?, ?)");
                            $insertStmt->bind_param("ssss", $pid, $appt_date, $appt_time, $request_status);

                            if ($insertStmt->execute()) {
                                $alertMessage = showAlert('success', 'Appointment Request! ', 'Your appointment request has been sent.We will get back to you shortly.', '/minipro/patdash/appt/appt.php');
                            } else {
                                $alertMessage = showAlert('error', 'Booking Failed', 'Failed to book appointment. Please try again.', '/minipro/patdash/appt/appt.php');
                            }
                            $insertStmt->close();
                        }
                        $tokenStmt->close();
                    }
                    $checkStmt->close();
                }
            }
        }
        $stmt->close();
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clinic Appointment</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400..700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/minipro/css/pappt.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body> 
    <?php echo $alertMessage; // Output any alerts ?>

    <div class="appointment-container">
        <h1>Book Your Appointment</h1>
        <form id="appointment-form" method="post" action="">
            <div class="calendar-section">
                <h3>Select Appointment Date</h3>
                <div class="calendar-header">
                    <button type="button" class="nav-arrow" id="prevBtn">&#8249;</button>
                    <h4>
                        <span id="monthDisplay">January</span>
                        <select id="yearSelect"></select> 
                    </h4>
                    <button type="button" class="nav-arrow" id="nextBtn">&#8250;</button>
                </div>
                <table class="calendar-table">
                    <thead>
                        <tr>
                            <th>Sun</th>
                            <th>Mon</th>
                            <th>Tue</th>
                            <th>Wed</th>
                            <th>Thu</th>
                            <th>Fri</th>
                            <th>Sat</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Calendar content will be injected here -->
                    </tbody>
                </table>
            </div>
        
            <div class="time-slots-section">
                <h3 class="time-slot-header" style="display: none;">Select Available Time</h3>
                <div class="time-slots">
                    <!-- Time slots content will be injected here -->
                </div>
            </div>
        
            <!-- Hidden fields to store selected appointment date and time -->
            <input type="hidden" id="appointment_date" name="appt_date">
            <input type="hidden" id="appointment_time" name="appt_time">
        </form>
    </div>

    <script src="/minipro/js/appt.js"></script>
</body>
</html>
<?php
ob_end_flush(); // Flush the output buffer
?>

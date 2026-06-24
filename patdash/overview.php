<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "miniprjct";
session_start();

// Database connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check for connection errors
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize patient name and ID variables
$patientName = "Patient";
$patientId = null; 

// Check if the user is logged in by checking the session
if (isset($_SESSION['username'])) {
    $uname = $_SESSION['username']; 

    // Prepare SQL query to get the patient's name and pid based on the username
    $query = "SELECT name, pid FROM reg WHERE username = ?";
    $stmt = $conn->prepare($query);

    if (!$stmt) {
        die("SQL Error: " . $conn->error);
    }

    $stmt->bind_param("s", $uname);
    $stmt->execute();
    $result = $stmt->get_result();

    // Fetch the patient's name and pid
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $patientName = $row['name'];
        $patientId = $row['pid'];
    } else {
        // User not found: Redirect to a registration message
        die("<h2>You are not registered, kindly <a href='profile.php'>register yourself here</a>.</h2>");
    }
    $stmt->close();
} else {
    die("User not logged in.");
}

// Check if patientId is retrieved
if (is_null($patientId)) {
    die("Patient ID is null. Check the username: $uname");
}

// Function to generate calendar
function generateCalendar($month, $year, $conn, $patientId) {
    $query = "SELECT appt_date FROM appointments WHERE MONTH(appt_date) = ? AND YEAR(appt_date) = ? AND pid = ? AND status != 'cancelled'";
    $stmt = $conn->prepare($query);

    if (!$stmt) {
        die("SQL Error: " . $conn->error);
    }

    $stmt->bind_param("iis", $month, $year, $patientId);
    $stmt->execute();
    $result = $stmt->get_result();

    $appointments = [];
    while ($row = $result->fetch_assoc()) {
        $appointments[] = date('j', strtotime($row['appt_date']));
    }
    $stmt->close();

    // Create calendar
    $calendar = '<table class="calendar"><tr>';
    $calendar .= '<th>Sun</th><th>Mon</th><th>Tue</th><th>Wed</th><th>Thu</th><th>Fri</th><th>Sat</th></tr><tr>';

    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    $firstDayOfWeek = date('w', mktime(0, 0, 0, $month, 1, $year));

    // Add empty cells before the first day
    for ($i = 0; $i < $firstDayOfWeek; $i++) {
        $calendar .= '<td></td>';
    }

    for ($day = 1; $day <= $daysInMonth; $day++) {
        $timestamp = mktime(0, 0, 0, $month, $day, $year);

        if ($timestamp < time()) {
            $calendar .= "<td>$day</td>";
        } else {
            $calendar .= in_array($day, $appointments) ? "<td class='highlight'>$day</td>" : "<td>$day</td>";
        }

        if (($day + $firstDayOfWeek) % 7 == 0) {
            $calendar .= '</tr><tr>';
        }
    }

    while (($day + $firstDayOfWeek) % 7 != 0) {
        $calendar .= '<td></td>';
        $day++;
    }

    $calendar .= '</tr></table>';
    return $calendar;
}

$currentMonth = date('n');
$currentYear = date('Y');

$appointmentDetails = [];
$query = "SELECT appt_date, appt_time, status FROM appointments WHERE MONTH(appt_date) = ? AND YEAR(appt_date) = ? AND pid = ? AND status != 'cancelled' AND appt_date >= CURDATE()";
$stmt = $conn->prepare($query);
$stmt->bind_param("iis", $currentMonth, $currentYear, $patientId);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $appointmentDetails[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard</title>
    <link rel="stylesheet" href="/minipro/css/overview.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400..700&display=swap" rel="stylesheet">
    
</head>
<body>
    <section class="slide">
        <div class="health-bag-slide">
            <div class="content">
                <h1>Welcome <span class="highlight"><?php echo htmlspecialchars($patientName); ?></span></h1>
                <h2>Have a nice day...</h2>
            </div>
            <div class="image-container">
                <img src="https://img.freepik.com/free-vector/hand-drawn-national-doctor-s-day-illustration-with-medics-essentials_23-2149447532.jpg?t=st=1729763614~exp=1729767214~hmac=6cb2a9466ef63584214b2b13f0cf221eb225d6ac5302abb43103528e80370022&w=996" alt="Patient" class="pat-image">
            </div>
        </div>
    </section>

    <div class="container">
        <div class="calendar-container">
            <?php echo generateCalendar($currentMonth, $currentYear, $conn, $patientId); ?>
        </div>
        <div class="appointments-box">
            <h1>Appointment Summary</h1>
            <ul>
                <?php
                if (empty($appointmentDetails)) {
                    echo "<li>You have no upcoming appointments scheduled.</li>";
                } else {
                    foreach ($appointmentDetails as $appointment) {
                        echo "<li><strong>" . date('d-m-Y', strtotime($appointment['appt_date'])) . " at " . htmlspecialchars($appointment['appt_time']) . "</strong></li>";
                    }
                }
                ?>
            </ul>
        </div>
    </div>

    <?php $conn->close(); ?>
</body>
</html>

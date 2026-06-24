<?php
$servername = "localhost"; // Typically "localhost"
$username = "root"; // Your actual database username
$password = ""; // Your actual database password (leave empty if none)
$dbname = "miniprjct"; // Your database name

// Create connection
$connection = mysqli_connect($servername, $username, $password, $dbname);

// Check connection
if (!$connection) {
    die("Connection failed: " . mysqli_connect_error());
}

// Query to count today's approved appointments
$sqlTotalApprovedToday = "SELECT COUNT(*) AS total_approved_today FROM appointments WHERE status = 'Approved' AND DATE(appt_date) = CURDATE()";
$resultTotalApprovedToday = mysqli_query($connection, $sqlTotalApprovedToday);
$totalApprovedToday = 0; // Initialize the variable

if ($resultTotalApprovedToday) {
    $rowTotalApprovedToday = mysqli_fetch_assoc($resultTotalApprovedToday);
    $totalApprovedToday = $rowTotalApprovedToday['total_approved_today'];
} else {
    echo "Error: " . mysqli_error($connection);
}



// Query to count pending requests
$sqlPending = "SELECT COUNT(*) AS total_pending FROM appointment_request WHERE request_status = 'Pending'";
$resultPending = mysqli_query($connection, $sqlPending);
$totalPending = 0; // Initialize the variable

if ($resultPending) {
    $rowPending = mysqli_fetch_assoc($resultPending);
    $totalPending = $rowPending['total_pending'];
} else {
    echo "Error: " . mysqli_error($connection);
}

// Now you can use $totalApproved, $totalPending, and $totalApprovedToday as needed

// Function to generate calendar
function generateCalendar($month, $year, $appointments = []) {
    $calendar = '';
    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    $firstDayOfMonth = mktime(0, 0, 0, $month, 1, $year);
    $firstDayOfWeek = date('w', $firstDayOfMonth);

    // Start calendar
    $calendar .= '<div class="calendar-box">'; // Add a wrapper for styling
    $calendar .= '<table class="calendar"><tr>';
    $calendar .= '<th>Sun</th><th>Mon</th><th>Tue</th><th>Wed</th><th>Thu</th><th>Fri</th><th>Sat</th></tr><tr>';

    // Add empty cells before the first day
    for ($i = 0; $i < $firstDayOfWeek; $i++) {
        $calendar .= '<td></td>';
    }

    // Fill the calendar days
    for ($day = 1; $day <= $daysInMonth; $day++) {
        $timestamp = mktime(0, 0, 0, $month, $day, $year);

        if ($timestamp < time()) {
            // Don't highlight past appointments
            $calendar .= '<td>' . $day . '</td>';
        } else {
            // Highlight future appointments
            if (in_array($day, $appointments)) {
                $calendar .= '<td class="highlight">' . $day . '</td>'; // Highlight appointments
            } else {
                $calendar .= '<td>' . $day . '</td>';
            }
        }

        // Start a new row at the end of the week
        if (($day + $firstDayOfWeek) % 7 == 0) {
            $calendar .= '</tr><tr>';
        }
    }

    // Add empty cells after the last day
    while (($day + $firstDayOfWeek) % 7 != 0) {
        $calendar .= '<td></td>';
        $day++;
    }

    $calendar .= '</tr></table>';
    $calendar .= '</div>'; // Close the calendar box
    return $calendar;
}

/// Get current month and year
$currentMonth = date('n'); // 1 to 12
$currentYear = date('Y');

// Initialize appointments as an empty array
$appointments = []; // No specific appointments to highlight

// Generate calendar with no specific appointments
$calendar = generateCalendar($currentMonth, $currentYear, $appointments);

// Close the connection
mysqli_close($connection);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard</title>
<link rel="stylesheet" href="/minipro/css/over.css"> <!-- Your custom CSS -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400..900;1,400..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/boxicons/css/boxicons.min.css">

    <style>
        .container {
            display: flex;
        }
        .left-panel, .right-panel {
            flex: 1;
            margin: 10px;
            height: 400px; /* Increase the height of both panels */
        }
        .right-panel {
            margin-top: -50px; /* Move the right panel up */
        }
        .calendar-box {
            border: 1px solid #ccc;
            padding: 10px;
            background-color: #f9f9f9;
        }
        .calendar {
            width: 100%;
            border-collapse: collapse;
        }
        .calendar th, .calendar td {
            width: 14.28%;
            text-align: center;
            padding: 5px;
            border: 1px solid #ccc;
        }
        .cards-container {
            display: flex; /* Use flexbox to align items horizontally */
            gap: 10px;
            margin-top: 70px;
        }
        .card {
            flex: 1; /* Each card takes equal width */
            border: 1px solid #ccc;
            padding: 20px;
            background-color: #f9f9f9; /* Light gray background */
            min-height: 300px; /* Reduced minimum height */ 
        }
        .date-box 
        .appointments-box {
            border: 2px solid #000; /* Thicker black border */
            padding: 10px;
            text-align: center;
            margin-bottom: 10px;
        }
        .appointments-box {
            padding: 20px;
        }
        .date-text {
            margin-top: 50px; /* Space between the heading and date */
        }
        .requests-text {
            margin-top: 50px; /* Space between the heading and total requests */
        }
    </style>
</head>
<body>

    <!-- Hero Section -->
    <section class="slide">
        <div class="health-bag-slide">
            <div class="content">
                <h1>Welcome <span class="highlight">Doctor</span></h1>
                <p class="welcome-message">Healing Starts With a SMILE<i class='bx bx-wink-smile' style="margin-left: 5px;color:#2873b1;"></i></p>     
            </div>
            <div class="image-container">
                <img src="https://homeocureclinic.co.in/wp-content/uploads/2020/08/Dr.-Pratiksha-Best-homeopathy-in-mumbai-1536x1536.png" alt="Doctor" class="doctor-image">
            </div>
        </div>
    </section>

    <div class="container">
        <!-- Left Panel (Calendar) -->
        <div class="left-panel">
            <div class="calendar-container">
                <?php echo generateCalendar($currentMonth, $currentYear, $appointments); ?>
            </div>
        </div>

<!-- Right Panel (Cards) -->
    <div class="right-panel">
        <div class="cards-container">
            <!-- Total Appointments Card -->
            <div class="card total-appointments-card">
               <h3>Total Appointments</h3>
               <p class="date-text">Date: <?php echo date('d-m-Y'); ?></p> <!-- Display current date -->
               <p>Total Appointments For Today: <?php echo $totalApprovedToday ; ?></p> <!-- Display total appointments for today -->
          </div>
         <!-- Requests For Appointment Card -->   
         <div class="card requests-appointment-card">
                   <h3>Appointment Request</h3> <!-- Changed from "Request For Appointment" -->
                   <p class="requests-text">Pending Requests: <?php echo $totalPending; ?></p> <!-- Display total requests -->
                </div>
            </div>
        </div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {

            // Data from PHP
            const totalApproved = <?php echo $totalApproved; ?>;
            const totalPending = <?php echo $totalPending; ?>;
            const totalCanceled = <?php echo $totalCanceled; ?>;

          // Appointment Requests Pie Chart
          var ctxRequestAppointments = document.getElementById('requestAppointmentsChart').getContext('2d');
          var requestAppointmentsChart = new Chart(ctxRequestAppointments, {
              type: 'pie',
              data: {
                  labels: ['Approved', 'Cancelled'],
                  datasets: [{
                      data: [totalApproved, totalCanceled], // PHP data for approved and cancelled
                      backgroundColor: ['#0000FF', '#FF5252'], // Change Approved color from green (#4CAF50) to blue (#0000FF)
        }]
            }
        });
    });
</script>
</body>
</html>
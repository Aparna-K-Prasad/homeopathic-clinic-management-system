<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "miniprjct";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Start the session
session_start();
$loggedInUsername = $_SESSION['username'] ?? null;

// Get the selected month from the form submission (if any)


// Get the current month
$currentMonth = date('n'); // Numeric month without leading zeros (1 to 12)

// Get the selected month from the form submission (if any)
$selectedMonth = $_GET['month'] ?? $currentMonth;

// Determine the heading for the selected month
$monthHeading = $selectedMonth 
    ? "Appointments for " . date('F', mktime(0, 0, 0, $selectedMonth, 1)) 
    : "Appointments for All Months";

if ($loggedInUsername) {
    // Retrieve pid using the username from the reg table
    $sql = "SELECT pid FROM reg WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $loggedInUsername);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $patientId = $row['pid']; // Get the patient's ID

        $sql = "
        SELECT a.*, r.new_date, r.new_time
        FROM appointment_request a
        LEFT JOIN reschedule r ON a.request_id = r.request_id
        INNER JOIN appointments appt ON a.pid = appt.pid 
                                      AND appt.appt_date = IFNULL(r.new_date, a.requested_date)
                                      AND appt.appt_time = IFNULL(r.new_time, a.requested_time)
        WHERE a.pid = ? 
        AND a.request_status != 'declined' 
        AND a.request_status != 'cancelled'
        AND (
            (a.request_status != 'rescheduled' AND a.requested_date >= CURDATE())
            OR 
            (a.request_status = 'rescheduled' AND r.new_date >= CURDATE())
        )
        AND appt.status = 'approved'
    ";
    
        if ($selectedMonth) {
            $sql .= " AND (MONTH(a.requested_date) = ? OR MONTH(r.new_date) = ?)";
        }
        $sql .= " ORDER BY 
                  CASE 
                    WHEN a.request_status = 'rescheduled' THEN r.new_date 
                    ELSE a.requested_date 
                  END ASC, 
                  a.requested_time ASC";

        $stmt = $conn->prepare($sql);
        if ($selectedMonth) {
            $stmt->bind_param("sii", $patientId, $selectedMonth, $selectedMonth);
        } else {
            $stmt->bind_param("s", $patientId);
        }
        $stmt->execute();
        $result = $stmt->get_result();

        // Display the appointment requests
        echo "<!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Your Appointment Status</title>
            <link rel='preconnect' href='https://fonts.googleapis.com'>
            <link rel='preconnect' href='https://fonts.gstatic.com' crossorigin>
            <link href='https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400;700&display=swap' rel='stylesheet'>
            <link href='https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400..900;1,400..900&display=swap' rel='stylesheet'>
            <link rel='stylesheet' href='/minipro/css/rqst.css'>
            <style>
                form {
                    margin-bottom: 30px;
                    text-align: center;
                }
                label {
                    font-family: 'Playfair Display', serif;
                    font-size: 20px;
                    margin-right: 10px;
                }
                      h1 {
        font-family: 'Dancing Script', cursive;
        font-weight: 700;
        font-size: 50px;
        color: #34557a;
        text-align: center;
        margin-bottom: 30px;
    }
    th{
            background-color: #34557a !important; 
            color: #ffffff !important;
            font-family: 'Playfair Display', serif;
            font-weight: 600;
        }
            </style>
        </head>
        <body>
            <h1>Your Appointment Status</h1>
            <hr style='margin-bottom: 40px;'>

            <form method='GET' action=''>
                <label for='month'>Select Month:</label>
                <select name='month' id='month' onchange='this.form.submit()'>
                    <option value=''>All Months</option>";
                    // Generate month options dynamically
                    for ($m = 1; $m <= 12; $m++) {
                        $monthName = date('F', mktime(0, 0, 0, $m, 1));
                        $selected = ($selectedMonth == $m) ? 'selected' : '';
                        echo "<option value='$m' $selected>$monthName</option>";
                    }
        echo "  </select>
            </form>";

        echo "<h2 style=\"font-family: 'Playfair Display', serif; font-weight: 400; text-align:center;\">" 
             . htmlspecialchars($monthHeading) . 
             "</h2>";

        if ($result->num_rows > 0) {
            echo "<table>
                    <tr>
                        <th>Sl No</th>
                        <th>Appointment Date</th>
                        <th>Appointment Time</th>
                        <th>Status</th>
                        <th>New Appointment Date</th>
                        <th>New Appointment Time</th>
                    </tr>";

            $serialNo = 1;
            $today = new DateTime();

            while ($row = $result->fetch_assoc()) {
                // Format the dates
                $appointmentDate = new DateTime($row['requested_date']);
                $rescheduledDate = !empty($row['new_date']) ? new DateTime($row['new_date']) : null;
                
                // Skip if both dates are in the past
                if ($row['request_status'] == 'rescheduled' && $rescheduledDate < $today) {
                    continue;
                }
                if ($row['request_status'] != 'rescheduled' && $appointmentDate < $today) {
                    continue;
                }

                $formattedDate = $appointmentDate->format('d-m-Y');
                $formattedRescheduleDate = $rescheduledDate ? $rescheduledDate->format('d-m-Y') : '';

                // Format the times
                $originalTime = date('h:i A', strtotime($row['requested_time']));
                $newTime = !empty($row['new_time']) ? date('h:i A', strtotime($row['new_time'])) : '';

                // Determine status display
                $status = $row['request_status'];
                $statusClass = '';
                switch(strtolower($status)) {
                    case 'approved':
                        $statusClass = 'status-approved';
                        break;
                    case 'pending':
                        $statusClass = 'status-pending';
                        break;
                    case 'rescheduled':
                        $statusClass = 'status-rescheduled';
                        break;
                }

                echo "<tr>
                        <td>" . htmlspecialchars($serialNo) . "</td>
                        <td>" . htmlspecialchars($formattedDate) . "</td>
                        <td>" . htmlspecialchars($originalTime) . "</td>
                        <td class='" . $statusClass . "'>" . htmlspecialchars($status) . "</td>
                        <td>" . ($formattedRescheduleDate ? htmlspecialchars($formattedRescheduleDate) : '-') . "</td>
                        <td>" . ($newTime ? htmlspecialchars($newTime) : '-') . "</td>
                        
                    </tr>";

                $serialNo++;
            }
            echo "</table>";
        } else {
            echo "<p style='font-family: Playfair Display, serif; font-weight: 400; font-style:italic; text-align:center;'>No upcoming appointments found.</p>";
        }

        // Updated CSS with new table header styling
        echo "<style>
                .status-approved {
                    color: #28a745;
                    font-weight: bold;
                }
                .status-pending {
                    color: #ffc107;
                    font-weight: bold;
                }
                .status-rescheduled {
                    color: #17a2b8;
                    font-weight: bold;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 20px;
                    background-color: white;
                    box-shadow: 0 1px 3px rgba(0,0,0,0.2);
                }
                th, td {
                    padding: 12px;
                    text-align: left;
                    border-bottom: 1px solid #ddd;
                }
                th {
                    background-color: #2c3e50; /* Dark blue header */
                    color: white;
                    font-weight: bold;
                    font-family: 'Playfair Display', serif;
                }
                tr:hover {
                    background-color: #f5f5f5;
                }
                td {
                    font-family: 'Playfair Display', serif;
                }
                tr:nth-child(even) {
                    background-color: #f8f9fa;
                }
              </style>";
    } else {
        echo "<p style='font-family: Playfair Display, serif; font-weight: 400; font-style:italic; text-align:center;'>No patient found for this username.</p>";
    }

    $stmt->close();
} else {
    echo "<p style='font-family: Playfair Display, serif; font-weight: 400; font-style:italic; text-align:center;'>You must be logged in to view your appointment status.</p>";
}

// Close the database connection
$conn->close();

echo "</body>
</html>";
?>

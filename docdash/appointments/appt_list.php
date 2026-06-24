<?php

$servername = "localhost";
$username = "root"; 
$password = "";
$dbname = "miniprjct"; 

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

session_start();

$currentDate = date("Y-m-d");
$searchDate = $currentDate;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['search_date'])) {
    $searchDate = $_POST['search_date'];
}

function formatDate($dateString) {
    return date("d F Y", strtotime($dateString));
}

// Prepare the SQL statement for scheduled appointments
$sqlScheduled = "SELECT a.token, a.appt_date, a.appt_time, a.status, r.pid, r.name FROM appointments a JOIN reg r ON a.pid = r.pid WHERE a.appt_date = ? AND a.status = 'Approved' ORDER BY a.appt_time ASC";
$stmtScheduled = $conn->prepare($sqlScheduled);

if ($stmtScheduled === false) {
    die("Error preparing scheduled statement: " . $conn->error);
}

$stmtScheduled->bind_param("s", $searchDate);
$stmtScheduled->execute();
$resultScheduled = $stmtScheduled->get_result();

if ($resultScheduled === false) {
    die("Error executing scheduled query: " . $stmtScheduled->error);
}

// Prepare the SQL statement for cancelled appointments
$sqlCancelled = "SELECT a.token, a.appt_date, a.appt_time, a.status, r.pid, r.name FROM appointments a JOIN reg r ON a.pid = r.pid WHERE a.appt_date = ? AND a.status = 'Cancelled'ORDER BY a.appt_time ASC";
$stmtCancelled = $conn->prepare($sqlCancelled);

if ($stmtCancelled === false) {
    die("Error preparing cancelled statement: " . $conn->error);
}

$stmtCancelled->bind_param("s", $searchDate);
$stmtCancelled->execute();
$resultCancelled = $stmtCancelled->get_result();

if ($resultCancelled === false) {
    die("Error executing cancelled query: " . $stmtCancelled->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment List</title>
    <link rel="stylesheet" href="/minipro/css/appt_list.css">
</head>
<body>
    <div class="container">
        <h1>Appointment List for <?php echo formatDate($searchDate); ?></h1>
        
        <form method="POST" action="">
            <label for="search_date">Select Date:</label>
            <input type="date" id="search_date" name="search_date" value="<?php echo $searchDate; ?>" required onchange="this.form.submit()">
        </form>

        <h2>Scheduled Appointments</h2>
        <table border="1">
            <thead>
                <tr>
                    <th>Serial No.</th>
                    <th>Patient ID</th>
                    <th>Patient Name</th>
                    <th>Appointment Time</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $hasScheduled = false; 
                $serialNo=1;
                while ($row = $resultScheduled->fetch_assoc()): 
                    // Check for both Scheduled and Approved status
                    if ($row['status'] === 'Scheduled' || $row['status'] === 'Approved'): 
                        $hasScheduled = true; ?>
                        <tr>
                        <td><?php echo $serialNo++; ?></td>
                            <td><?php echo $row['pid']; ?></td>
                            <td><?php echo htmlspecialchars($row['name']); ?></td> 
                            <td><?php echo date("h:i A", strtotime($row['appt_time'])); ?></td> 
                        </tr>
                    <?php 
                    endif; 
                endwhile; 
                if (!$hasScheduled): ?>
                    <tr><td colspan="4">No scheduled or approved appointments for this date.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <h2>Cancelled Appointments</h2>
        <table border="1">
            <thead>
                <tr>
                <th>Serial No.</th>
                    <th>Patient ID</th>
                    <th>Patient Name</th>
                    <th>Appointment Time</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $hasCancelled = false; // Track if there are canceled appointments
                while ($row = $resultCancelled->fetch_assoc()):
                    if ($row['status'] === 'Cancelled'): 
                        $hasCancelled = true; ?>
                        <tr>
                        <td><?php echo $serialNo++; ?></td>
                            <td><?php echo $row['pid']; ?></td>
                            <td><?php echo htmlspecialchars($row['name']); ?></td> 
                            <td><?php echo date("h:i A", strtotime($row['appt_time'])); ?></td> 
                        </tr>
                    <?php 
                    endif; 
                endwhile; ?>
                <?php if (!$hasCancelled): ?>
                    <tr><td colspan="4">No canceled appointments for this date.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

    </div>
</body>
</html>

<?php
$stmtScheduled->close();
$stmtCancelled->close();
$conn->close();
?>

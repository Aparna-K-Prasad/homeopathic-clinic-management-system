<?php
session_start();

// Database connection
$servername = "localhost";
$username = "root"; // your database username
$password = ""; // your database password
$dbname = "miniprjct"; // your database name

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = ""; // Initialize message variable

// Get the logged-in user's username from the session
$loggedInUsername = $_SESSION['username'] ?? null;

if (!$loggedInUsername) {
    die("Username not provided. Please log in.");
}

// Retrieve pid from reg table based on username
$sql = "SELECT pid FROM reg WHERE username = ?";
$stmtPid = $conn->prepare($sql);
$stmtPid->bind_param("s", $loggedInUsername);
$stmtPid->execute();
$stmtPid->bind_result($pid);
$stmtPid->fetch();
$stmtPid->close();

if (!$pid) {
    die("No PID found for the user: " . htmlspecialchars($loggedInUsername));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appt_date = $_POST['appt_date'];
    $token = $_POST['token'];

    // Update the status to 'Cancelled'
    $updateSql = "UPDATE appointments SET status = 'Cancelled' WHERE appt_date = ? AND token = ? AND pid = ?";
    $stmt = $conn->prepare($updateSql);
    $stmt->bind_param("ssi", $appt_date, $token, $pid);

    if ($stmt->execute()) {
        $message = "Your appointment has been successfully cancelled.";
    } else {
        $message = "Error: " . $stmt->error;
    }
    $stmt->close(); // Close the statement after execution
}

// Retrieve appointments to display for cancellation for the logged-in user
$query = "SELECT appt_date, token, appt_time FROM appointments WHERE status != 'Cancelled' AND pid = ? AND appt_date >= CURDATE()  ORDER BY appt_time AND appt_date ASC ";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $pid);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cancel Appointment</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400..900;1,400..900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/minipro/css/cancel_appt.css">
    <!-- Include SweetAlert CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
</head>
<body>

<div class="container mt-5">
    <h1 style="margin-bottom: 60px;">Cancel Your Appointment</h1><hr style= "margin-bottom: 60px;">
    
    <?php if ($message) : ?>
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: '<?php echo addslashes($message); ?>'
            });
        </script>
    <?php endif; ?>

    <?php if ($result->num_rows > 0) : ?>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Serial No.</th>
                    <th>Appointment Date</th>
                    <th>Appointment Time</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $serialNo = 1; // Initialize serial number
                while ($row = $result->fetch_assoc()) : ?>
                    <tr>
                        <td><?php echo $serialNo++; ?></td>
                        <td><?php echo htmlspecialchars(date("d-m-Y", strtotime($row['appt_date']))); ?></td>
                        
                        <td><?php echo htmlspecialchars(date("h:i A", strtotime($row['appt_time']))); ?></td>
                        <td>
                            <form method="POST" action="">
                            <input type="hidden" name="token" value="<?php echo htmlspecialchars($row['token']); ?>">
                                <input type="hidden" name="appt_date" value="<?php echo htmlspecialchars($row['appt_date']); ?>">
                                
                                <button type="submit" class="btn btn-danger" onclick="confirmCancel(event)">Cancel Appointment</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else : ?>
       <h2 style="font-family: 'Playfair Display', serif; font-weight: 400; font-style:italic;">No appointments found to cancel.</h2>
    <?php endif; ?>
</div>

<!-- Include SweetAlert JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
<script>
    function confirmCancel(event) {
        event.preventDefault(); // Prevent the form from submitting immediately
        const form = event.target.closest('form'); // Get the form element

        Swal.fire({
            title: 'Are you sure?',
            text: "Do you really want to cancel this appointment?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, cancel it!',
            cancelButtonText: 'No, keep it'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit(); // Submit the form if confirmed
            }
        });
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
$conn->close();
?>

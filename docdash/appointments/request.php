<?php

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "miniprjct";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// First, update past appointments to 'Declined'
$updateSql = "UPDATE appointment_request 
              SET request_status = 'Declined' 
              WHERE request_status = 'Pending' 
              AND (
                  requested_date < CURDATE() 
                  OR (
                      requested_date = CURDATE() 
                      AND requested_time < CURTIME()
                  )
              )";

$conn->query($updateSql);

// Then fetch only current and future pending appointments
$sql = "SELECT * FROM appointment_request 
        WHERE request_status = 'Pending' 
        AND (
            requested_date > CURDATE() 
            OR (
                requested_date = CURDATE() 
                AND requested_time > CURTIME()
            )
        ) 
        ORDER BY requested_date ASC, requested_time ASC";
$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();

    $appointmentsByDate = [];

    while ($row = $result->fetch_assoc()) {
        $appointmentsByDate[$row['requested_date']][] = $row;
    }

    echo "<!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Appointment Requests</title>
        <link rel='preconnect' href='https://fonts.googleapis.com'>
        <link rel='preconnect' href='https://fonts.gstatic.com' crossorigin>
        <link href='https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400;700&display=swap' rel='stylesheet'>
        <link rel='stylesheet' href='/minipro/css/rqst.css'> 
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    </head>
    <body>
        <h1>Appointment Requests</h1>";

    foreach ($appointmentsByDate as $date => $appointments) {
        
        $formattedDate = date("d-m-Y", strtotime($date));
        
        echo "<h2>Requests for " . htmlspecialchars($formattedDate) . "</h2>";
        echo "<table>
                <tr>
                    <th>Request ID</th>
                    <th>Patient ID</th>
                    <th>Requested Date</th>
                    <th>Requested Time</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>";

        $requestIdCounter = 1;

        foreach ($appointments as $row) {
            echo "<tr>
                    <td>" . $requestIdCounter++ . "</td>
                    <td>" . htmlspecialchars($row['pid']) . "</td>
                    <td>" . htmlspecialchars(date("d-m-Y", strtotime($row['requested_date']))) . "</td>
                    <td>" . htmlspecialchars($row['requested_time']) . "</td>
                    <td>" . htmlspecialchars($row['request_status']) . "</td>
                    <td>
                        <form method='POST' action='approval_decline.php' style='display:inline;'>
                            <input type='hidden' name='request_id' value='" . htmlspecialchars($row['request_id']) . "'>
                            <input type='hidden' name='action' value='approve'>
                            <button type='submit' class='approve'>Approve</button>
                        </form>
                        
                        <button type='button' class='reshedule' onclick='openRescheduleModal(" . htmlspecialchars($row['request_id']) . ")'>Reschedule</button>
                        
                        <form method='POST' action='approval_decline.php' style='display:inline;'>
                            <input type='hidden' name='request_id' value='" . htmlspecialchars($row['request_id']) . "'>
                            <input type='hidden' name='action' value='decline'>
                            <button type='submit' class='decline'>Decline</button>
                        </form>
                    </td>
                </tr>";
        }
        echo "</table>";
    }

    if (empty($appointmentsByDate)) {
        echo "<p>No pending appointment requests found.</p>";
    }

    $stmt->close();
} else {
    echo "Error preparing statement: " . $conn->error;
}

$conn->close();

echo "</body>
</html>";
?>

<!-- Reschedule Modal -->
<div id="rescheduleModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Reschedule Appointment</h2>
        <form method="POST" action="approval_decline.php" id="rescheduleForm">
            <input type="hidden" name="action" value="reschedule">
            <input type="hidden" name="request_id" id="modal_request_id">
            
            <div class="form-group">
                <label for="new_date">New Date:</label>
                <input type="date" id="new_date" name="re_date" required>
            </div>
            
            <div class="form-group">
                <label for="new_time">New Time:</label>
                <select id="new_time" name="re_time" required>
                    <option value="">Select Time</option>
                </select>
            </div>
            
            <button type="submit" class="submit-btn">Submit</button>
        </form>
    </div>
</div>

<style>
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.modal-content {
    background-color: #fefefe;
    margin: 15% auto;
    padding: 20px;
    border: 1px solid #888;
    width: 80%;
    max-width: 500px;
    border-radius: 5px;
}

.close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.close:hover {
    color: black;
}

.form-group {
    margin: 15px 0;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
}

.form-group input,
.form-group select {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.submit-btn {
    background-color: #4CAF50;
    color: white;
    padding: 10px 15px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

.submit-btn:hover {
    background-color: #45a049;
}
</style>

<script>
function openRescheduleModal(requestId) {
    var modal = document.getElementById('rescheduleModal');
    document.getElementById('modal_request_id').value = requestId;
    modal.style.display = 'block';
    
    // Set minimum date to today
    var today = new Date().toISOString().split('T')[0];
    var dateInput = document.getElementById('new_date');
    dateInput.min = today;
    
    // Add event listener for date changes
    dateInput.addEventListener('change', updateTimeSlots);
}

function showAlert(type, title, message) {
    Swal.fire({
        title: title,
        text: message,
        icon: type,
        confirmButtonText: 'OK'
    }).then((result) => {
        if (type === 'success') {
            window.location.href = 'request.php';
        }
    });
}

function updateTimeSlots() {
    const dateInput = document.getElementById('new_date');
    const timeSlotSelect = document.getElementById('new_time');
    const selectedDate = new Date(dateInput.value);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    
    // Clear existing options
    timeSlotSelect.innerHTML = '<option value="">Select Time</option>';
    
    // Check if date is valid
    if (!dateInput.value) {
        timeSlotSelect.disabled = true;
        return;
    }
    
    const dayOfWeek = selectedDate.toLocaleDateString('en-US', { weekday: 'long' });
    
    // Check for past date
    if (selectedDate < today) {
        showAlert('error', 'Error', 'You cannot select a past date.');
        timeSlotSelect.disabled = true;
        return;
    }
    
    // Check for Sunday
    if (dayOfWeek === 'Sunday') {
        showAlert('error', 'Error', 'Appointments are not available on Sundays.');
        timeSlotSelect.disabled = true;
        return;
    }
    
    timeSlotSelect.disabled = false;
    
    // Define time slots based on day
    const timeSlots = dayOfWeek === 'Saturday'
        ? [
            '09:00:00', '09:30:00', '10:00:00', '10:30:00', 
            '11:00:00', '11:30:00', '12:00:00', 
            '17:00:00', '17:30:00', '18:00:00','18:30:00','19:00:00','19:30:00'
          ]
        : [
            '08:00:00', '08:30:00', '09:00:00', '09:30:00', 
            '10:00:00','10:30:00','11:00:00', '11:30:00', '16:00:00','16:30:00','17:00:00', '17:30:00','18:00:00','18:30:00'
          ];
    
    const now = new Date();
    
    timeSlots.forEach(slot => {
        const [hours, minutes] = slot.split(':');
        let slotDate = new Date(selectedDate);
        slotDate.setHours(parseInt(hours), parseInt(minutes), 0);
        
        const option = document.createElement('option');
        option.value = slot;
        
        // Format display time
        const displayTime = new Date(slotDate).toLocaleTimeString('en-US', {
            hour: 'numeric',
            minute: '2-digit',
            hour12: true
        });
        option.textContent = displayTime;
        
        // Disable past time slots for today
        if (selectedDate.toDateString() === now.toDateString() && slotDate < now) {
            option.disabled = true;
            option.textContent += ' (Unavailable)';
        }
        
        timeSlotSelect.appendChild(option);
    });
}

// Close button functionality
document.querySelector('.close').onclick = function() {
    document.getElementById('rescheduleModal').style.display = 'none';
}

// Click outside modal to close
window.onclick = function(event) {
    var modal = document.getElementById('rescheduleModal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}
</script>

<!-- Add SweetAlert2 CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

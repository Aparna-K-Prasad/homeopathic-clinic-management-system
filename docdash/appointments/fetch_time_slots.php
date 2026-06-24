<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "miniprjct";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_GET['date'])) {
    $selectedDate = $_GET['date'];
    
    $availableSlots = ['09:00:00', '10:00:00', '11:00:00', '12:00:00', '13:00:00', '14:00:00', '15:00:00', '16:00:00'];
    
    $bookedSql = "SELECT appt_time FROM appointments WHERE appt_date = ?";
    $stmt = $conn->prepare($bookedSql);
    $stmt->bind_param("s", $selectedDate);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $bookedSlots = [];
    while ($row = $result->fetch_assoc()) {
        $bookedSlots[] = $row['appt_time'];
    }

    $availableSlots = array_diff($availableSlots, $bookedSlots);
    
    echo json_encode(array_values($availableSlots));
}

$conn->close();
?>

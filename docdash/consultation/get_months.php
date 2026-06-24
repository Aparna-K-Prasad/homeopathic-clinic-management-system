<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "miniprjct";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Connection failed']));
}

$pid = $_GET['pid'];
$year = $_GET['year'];

$sql = "SELECT DISTINCT MONTH(date) AS month, MONTHNAME(date) AS month_name
        FROM inv
        WHERE pid = ? AND YEAR(date) = ?
        ORDER BY month ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $pid, $year);
$stmt->execute();
$result = $stmt->get_result();

$months = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $months[] = ['value' => $row['month'], 'name' => $row['month_name']];
    }
}

echo json_encode(['success' => true, 'months' => $months]);
?>

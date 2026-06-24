<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "miniprjct";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$year = $_GET['year'];
$pid = $_GET['pid'];

$query = "SELECT DISTINCT MONTH(upload_date) AS month FROM patient_images WHERE pid = ? AND YEAR(upload_date) = ? ORDER BY MONTH(upload_date) ASC";
$stmt = $conn->prepare($query);
$stmt->bind_param("si", $pid, $year);
$stmt->execute();
$result = $stmt->get_result();

$months = [];
while ($row = $result->fetch_assoc()) {
    $months[] = str_pad($row['month'], 2, '0', STR_PAD_LEFT);
}

echo json_encode($months);
?>

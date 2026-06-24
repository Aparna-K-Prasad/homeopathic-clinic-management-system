<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "miniprjct";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$query = isset($_GET['query']) ? $_GET['query'] : '';

// Modified query to fetch all medicines regardless of stock
$sql = "SELECT mid, mname, dosage, stock 
        FROM medicines 
        WHERE mname LIKE ? 
        ORDER BY mname ASC";

$stmt = $conn->prepare($sql);
$searchTerm = "%$query%";
$stmt->bind_param("s", $searchTerm);
$stmt->execute();
$result = $stmt->get_result();
$medicines = $result->fetch_all(MYSQLI_ASSOC);

header('Content-Type: application/json');
echo json_encode($medicines);
?> 
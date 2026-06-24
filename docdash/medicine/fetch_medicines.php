<?php
header('Content-Type: application/json');

$server = "localhost";
$user = "root";
$pass = "";
$db = "miniprjct";

$conn = new mysqli($server, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$query = isset($_GET['query']) ? $_GET['query'] : '';

$sql = "SELECT DISTINCT mname, mid, dosage FROM medicines";
if (!empty($query)) {
    $sql .= " WHERE mname LIKE ?";
}
$sql .= " ORDER BY mname";

$stmt = $conn->prepare($sql);

if (!empty($query)) {
    $searchTerm = "%" . $query . "%";
    $stmt->bind_param("s", $searchTerm);
}

$stmt->execute();
$result = $stmt->get_result();
$medicines = array();

while ($row = $result->fetch_assoc()) {
    $medicines[] = array(
        "mid" => $row['mid'],
        "mname" => $row['mname'],
        "dosage" => $row['dosage']
    );
}

echo json_encode($medicines);
$conn->close();
?>

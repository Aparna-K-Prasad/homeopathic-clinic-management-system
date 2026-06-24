<?php
// Assuming you have a database connection established
$searchTerm = isset($_POST['searchTerm']) ? $_POST['searchTerm'] : '';
$server = "localhost";
$user = "root";
$password = "";
$db = "miniprjct";

$con = mysqli_connect($server, $user, $password, $db);
if (!$con) {
    die("Connection failed: " . mysqli_connect_error());
}
// Fetch medicines
$medicines = []; // Array to hold medicines
$lowStock = []; // Array to hold low stock medicines
$outOfStock = []; // Array to hold out of stock medicines

// Example SQL queries to fetch data
$sqlMedicines = "SELECT * FROM medicines WHERE stock >= 50 AND (mname LIKE '%$searchTerm%' OR dosage LIKE '%$searchTerm%')";
$resultMedicines = mysqli_query($con, $sqlMedicines);
while ($row = mysqli_fetch_assoc($resultMedicines)) {
    $medicines[] = $row;
}

// Fetch low stock medicines
$sqlLowStock = "SELECT * FROM medicines WHERE stock > 0 AND stock < 50 AND (mname LIKE '%$searchTerm%' OR dosage LIKE '%$searchTerm%')";
$resultLowStock = mysqli_query($con, $sqlLowStock);
while ($row = mysqli_fetch_assoc($resultLowStock)) {
    $lowStock[] = $row;
}

// Fetch out of stock medicines
$sqlOutOfStock = "SELECT * FROM medicines WHERE stock < 0 AND (mname LIKE '%$searchTerm%' OR dosage LIKE '%$searchTerm%')";
$resultOutOfStock = mysqli_query($con, $sqlOutOfStock);
while ($row = mysqli_fetch_assoc($resultOutOfStock)) {
    $outOfStock[] = $row;
}

// Return the results as JSON
echo json_encode([
    "medicines" => $medicines,
    "lowStock" => $lowStock,
    "outOfStock" => $outOfStock
]);
?>

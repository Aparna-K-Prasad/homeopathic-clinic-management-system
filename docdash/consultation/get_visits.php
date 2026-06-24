<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "miniprjct";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$pid = $_POST['pid'];
$year = $_POST['year'];
$month = $_POST['month'];

$start_date = date('Y-m-01', strtotime("$year-$month"));
$end_date = date('Y-m-t', strtotime("$year-$month"));

$sql = "SELECT DISTINCT date 
        FROM inv 
        WHERE pid = ? 
        AND date BETWEEN ? AND ? 
        ORDER BY date DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("sss", $pid, $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . date('d-m-Y', strtotime($row['date'])) . "</td>";
        echo "<td>
                <a href='visit_details.php?pid=" . $pid . "&date=" . $row['date'] . "' 
                   class='btn btn-info btn-sm'>
                    View Details
                </a>
              </td>";
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='2' class='text-center'>No visits found for this month</td></tr>";
}
?>

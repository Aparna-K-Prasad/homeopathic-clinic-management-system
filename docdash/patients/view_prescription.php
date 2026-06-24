<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "miniprjct";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$i_id = isset($_GET['i_id']) ? $_GET['i_id'] : null;

if (!$i_id) {
    die("Investigation ID not provided");
}

// Updated SQL query to match your prescription table structure
$sql = "SELECT p.frequency, p.duration, p.quantity, m.mname 
        FROM prescription p
        JOIN medicines m ON p.mid = m.mid
        WHERE p.i_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $i_id);
$stmt->execute();
$result = $stmt->get_result();
$prescriptions = [];

while($row = $result->fetch_assoc()) {
    $prescriptions[] = [
        'medicine' => $row['mname'],
        'frequency' => $row['frequency'],
        'duration' => $row['duration'],
        'quantity' => $row['quantity']
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prescription Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .prescription-content {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="prescription-content">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Prescription Details</h5>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Medicine</th>
                                <th>Frequency</th>
                                <th>Quantity (Per time)</th>
                                <th>Duration (Days)</th>
                               
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($prescriptions as $prescription): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($prescription['medicine']); ?></td>
                                    <td><?php echo htmlspecialchars($prescription['frequency']); ?></td>
                                    <td><?php echo htmlspecialchars($prescription['quantity']); ?></td>
                                    <td><?php echo htmlspecialchars($prescription['duration']); ?></td>
                                    
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="text-center mt-3 no-print">
                <button onclick="history.back()" class="btn btn-secondary">Back</button>
            </div>
        </div>
    </div>
</body>
</html> 
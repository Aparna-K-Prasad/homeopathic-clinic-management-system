<?php
session_start();
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "miniprjct";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$pid = $_GET['pid'];
$date = $_GET['date'];

// Get patient name
$patient_query = "SELECT name FROM reg WHERE pid = ?";
$stmt = $conn->prepare($patient_query);
$stmt->bind_param("s", $pid);
$stmt->execute();
$patient_result = $stmt->get_result();
$patient_name = $patient_result->fetch_assoc()['name'];

// Get investigation details
$sql = "SELECT i.*, 
        v.blood_pressure, v.heart_rate, v.temperature, 
        v.weight, v.height, v.oxygen_saturation, v.respiratory_rate,
        v.allergies
        FROM inv i
        LEFT JOIN vitals v ON i.pid = v.pid AND i.date = v.vdate
        WHERE i.pid = ? AND i.date = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $pid, $date);
$stmt->execute();
$result = $stmt->get_result();
$visit_data = $result->fetch_assoc();

// Get prescriptions
$sql_pres = "SELECT p.*, m.mname, m.dosage as med_dosage 
             FROM prescription p 
             JOIN medicines m ON p.mid = m.mid
             JOIN inv i ON p.i_id = i.i_id
             WHERE i.pid = ? AND i.date = ?";
$stmt_pres = $conn->prepare($sql_pres);
$stmt_pres->bind_param("ss", $pid, $date);
$stmt_pres->execute();
$prescriptions = $stmt_pres->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visit Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .details-section {
            background-color: #f8f9fa;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .section-title {
            color: #0d6efd;
            border-bottom: 2px solid #0d6efd;
            padding-bottom: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3>Visit Details</h3>
            <p class="text-muted">Patient: <?php echo htmlspecialchars($patient_name); ?> (<?php echo htmlspecialchars($pid); ?>)</p>
            <p class="text-muted">Date: <?php echo date('d-m-Y', strtotime($date)); ?></p>
        </div>
        <a href="consultation.php?pid=<?php echo $pid; ?>&tab=history" class="btn btn-secondary">Back</a>
    </div>

    <!-- Investigation Details -->
    <div class="details-section">
        <h4 class="section-title">Investigation Details</h4>
        <div class="row">
            <div class="col-md-12">
                <p><strong>Symptoms:</strong> <?php echo htmlspecialchars($visit_data['symptoms']); ?></p>
                <p><strong>Investigation:</strong> <?php echo htmlspecialchars($visit_data['investigation']); ?></p>
                <p><strong>Diagnosis:</strong> <?php echo htmlspecialchars($visit_data['diagnosis']); ?></p>
                <?php if ($visit_data['follow']): ?>
                    <p><strong>Follow-up Date:</strong> <?php echo date('d-m-Y', strtotime($visit_data['follow'])); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Vitals -->
    <div class="details-section">
        <h4 class="section-title">Vitals</h4>
        <div class="row">
            <div class="col-md-6">
                <p><strong>Blood Pressure:</strong> <?php echo $visit_data['blood_pressure'] ? $visit_data['blood_pressure'] . ' mmHg' : 'Not recorded'; ?></p>
                <p><strong>Heart Rate:</strong> <?php echo $visit_data['heart_rate'] ? $visit_data['heart_rate'] . ' bpm' : 'Not recorded'; ?></p>
                <p><strong>Temperature:</strong> <?php echo $visit_data['temperature'] ? $visit_data['temperature'] . ' °C' : 'Not recorded'; ?></p>
                <p><strong>Weight:</strong> <?php echo $visit_data['weight'] ? $visit_data['weight'] . ' kg' : 'Not recorded'; ?></p>
            </div>
            <div class="col-md-6">
                <p><strong>Height:</strong> <?php echo $visit_data['height'] ? $visit_data['height'] . ' cm' : 'Not recorded'; ?></p>
                <p><strong>Oxygen Saturation:</strong> <?php echo $visit_data['oxygen_saturation'] ? $visit_data['oxygen_saturation'] . ' %' : 'Not recorded'; ?></p>
                <p><strong>Respiratory Rate:</strong> <?php echo $visit_data['respiratory_rate'] ? $visit_data['respiratory_rate'] . ' breaths/min' : 'Not recorded'; ?></p>
                <p><strong>Allergies:</strong> <?php echo $visit_data['allergies'] ? htmlspecialchars($visit_data['allergies']) : 'None reported'; ?></p>
            </div>
        </div>
    </div>

    <!-- Prescription -->
    <div class="details-section">
        <h4 class="section-title">Prescription</h4>
        <?php if ($prescriptions->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Medicine</th>
                            <th>Medicine Dosage</th>
                            <th>Frequency</th>
                            <th>Duration</th>
                            <th>Quantity</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($pres = $prescriptions->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($pres['mname']); ?></td>
                                <td><?php echo htmlspecialchars($pres['med_dosage']); ?></td>
                                <td><?php echo htmlspecialchars($pres['frequency']); ?></td>
                                <td><?php echo htmlspecialchars($pres['duration']); ?></td>
                                <td><?php echo htmlspecialchars($pres['quantity']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

        <?php else: ?>
            <p>No prescriptions recorded for this visit.</p>
        <?php endif; ?>
    </div>
   
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 
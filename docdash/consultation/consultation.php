<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "miniprjct";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get patient details
$pid = isset($_GET['pid']) ? $_GET['pid'] : null;
if (!$pid) {
    echo '<p>Error: Patient ID not provided.</p>';
    exit;
}

// Fetch username based on pid
$sql = "SELECT name,age FROM reg WHERE pid = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $pid,);
$stmt->execute();
$stmt->bind_result($name,$age);
$stmt->fetch();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultation Page</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .tabs {
            display: flex;
            border-bottom: 2px solid #ccc;
            background-color: #f5f5f5;
            margin-bottom: 20px;
        }
        .tab-button {
            flex: 1;
            padding: 12px;
            text-align: center;
            border: none;
            background-color: #f5f5f5;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.3s, color 0.3s;
        }
        .tab-button:hover, .tab-button.active {
            background-color: #007bff;
            color: white;
        }
        .tab-content {
            display: none;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        input[type="text"], input[type="date"], textarea {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .patient-info {
            background-color: #f8f9fa;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
    </style>
</head>
<body class="container mt-4">

    <!-- Patient Info Section -->
    <div class="patient-info">
        <div>Date: <strong><?php echo date('F j, Y'); ?></strong></div>
        <div>Patient ID: <strong><?php echo htmlspecialchars($pid); ?></strong></div>
        <div>Name: <strong><?php echo htmlspecialchars($name); ?></strong></div>
        <div>Age: <strong><?php echo htmlspecialchars($age); ?></strong></div>
    </div>

    <!-- Tabs -->
    <div class="tabs">
        <button id="inv-button" class="tab-button active" onclick="openTab('inv')">Investigation</button>
        <button id="medhis-button" class="tab-button" onclick="openTab('medhis')">Medical History</button>
        <button id="vitals-button" class="tab-button" onclick="openTab('vitals')">Vitals</button>
        <button id="lab-button" class="tab-button" onclick="openTab('lab')">Images & Reports</button>
    </div>

    <!-- Investigation Tab -->
    <div class="tab-content" id="inv">
        <?php include 'inv.php'; ?>
    </div>

    <!-- Medical History Tab -->
    <div class="tab-content" id="medhis">
        <?php include 'med_his.php'; ?>
    </div>

    <!-- Vitals Tab -->
    <div class="tab-content" id="vitals">
        <?php include 'vitals.php'; ?>
    </div>

    <!-- Lab Reports Tab -->
    <div class="tab-content" id="lab">
        <div class="d-flex flex-column align-items-center gap-3 mb-3 mt-5">
        <button class="btn btn-success w-50" onclick="window.location.href='upload_images.php?pid=<?php echo $pid; ?>'">Upload Images & Reports</button>
            <button class="btn btn-primary w-50" onclick="window.location.href='view_images.php?pid=<?php echo $pid; ?>'">View Images & Reports</button>
          
        </div>
    </div>

    <script>
        function openTab(tabName) {
            // Hide all tab contents
            var tabContents = document.getElementsByClassName('tab-content');
            for (var i = 0; i < tabContents.length; i++) {
                tabContents[i].style.display = 'none';
            }

            // Remove active class from all buttons
            var tabButtons = document.getElementsByClassName('tab-button');
            for (var i = 0; i < tabButtons.length; i++) {
                tabButtons[i].classList.remove('active');
            }

            // Show the selected tab content and mark button as active
            document.getElementById(tabName).style.display = 'block';
            document.getElementById(tabName + '-button').classList.add('active');
        }

        // Show Investigation tab by default when page loads
        document.addEventListener('DOMContentLoaded', function() {
            openTab('inv');
        });
    </script>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
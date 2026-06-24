<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "miniprjct";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vitals</title>
    <!-- Include SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pid = $_GET['pid'];
    
    // Collect input data (allowing empty values)
    $blood_pressure = !empty($_POST['bp']) ? $conn->real_escape_string($_POST['bp']) : null;
    $pulse = !empty($_POST['pulse']) ? $conn->real_escape_string($_POST['pulse']) : null;
    $temperature = !empty($_POST['temp']) ? $conn->real_escape_string($_POST['temp']) : null;
    $weight = !empty($_POST['weight']) ? $conn->real_escape_string($_POST['weight']) : null;
    $height = !empty($_POST['height']) ? $conn->real_escape_string($_POST['height']) : null;
    $oxygen = !empty($_POST['oxygen']) ? $conn->real_escape_string($_POST['oxygen']) : null;
    $respiratory_rate = !empty($_POST['respiratory']) ? $conn->real_escape_string($_POST['respiratory']) : null;
    $allergies = !empty($_POST['allergies']) ? $conn->real_escape_string($_POST['allergies']) : null;

    $sql = "INSERT INTO vitals (pid, vdate, blood_pressure, heart_rate, temperature, weight, height, oxygen_saturation, respiratory_rate, allergies) 
            VALUES (?, CURRENT_DATE, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "sssssssss", 
        $pid, $blood_pressure, $pulse, $temperature, 
        $weight, $height, $oxygen, $respiratory_rate, $allergies
    );

    if ($stmt->execute()) {
        echo "<script>
                Swal.fire({
                    title: 'Success!',
                    text: 'Vitals saved successfully',
                    icon: 'success',
                    confirmButtonText: 'OK',
                    allowOutsideClick: false,  // Prevent clicking outside
                    backdrop: '#FFFFFF',       // White backdrop
                    showClass: {
                        popup: 'animate__animated animate__fadeInDown'
                    },
                    hideClass: {
                        popup: 'animate__animated animate__fadeOutUp'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = 'consultation.php?pid=" . $pid . "&tab=vitals';
                    }
                });
              </script>";
    } else {
        echo "<script>
                Swal.fire({
                    title: 'Error!',
                    text: 'Error saving vitals',
                    icon: 'error',
                    confirmButtonText: 'OK',
                    allowOutsideClick: false,  // Prevent clicking outside
                    backdrop: '#FFFFFF',       // White backdrop
                    showClass: {
                        popup: 'animate__animated animate__fadeInDown'
                    },
                    hideClass: {
                        popup: 'animate__animated animate__fadeOutUp'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = 'consultation.php?pid=" . $pid . "&tab=vitals';
                    }
                });
              </script>";
    }
    $stmt->close();
}
?>

<h3>Vitals</h3>
<form action="vitals.php?pid=<?php echo $_GET['pid']; ?>" method="POST">
    <input type="hidden" name="pid" value="<?php echo htmlspecialchars($_GET['pid']); ?>">
    <div class="form-group">
        <label for="bp">Blood Pressure (mmHg):</label>
        <input type="text" id="bp" name="bp" placeholder="e.g., 120/80" class="form-control">
    </div>
    <div class="form-group">
        <label for="pulse">Pulse (bpm):</label>
        <input type="text" id="pulse" name="pulse" placeholder="e.g., 72" class="form-control">
    </div>
    <div class="form-group">
        <label for="temp">Temperature (°C):</label>
        <input type="text" id="temp" name="temp" placeholder="e.g., 36.6" class="form-control">
    </div>
    <div class="form-group">
        <label for="weight">Weight (kg):</label>
        <input type="text" id="weight" name="weight" placeholder="e.g., 70" class="form-control">
    </div>
    <div class="form-group">
        <label for="height">Height (cm):</label>
        <input type="text" id="height" name="height" placeholder="e.g., 170" class="form-control">
    </div>
    <div class="form-group">
        <label for="oxygen">Oxygen Saturation (%):</label>
        <input type="text" id="oxygen" name="oxygen" placeholder="e.g., 98" class="form-control">
    </div>
    <div class="form-group">
        <label for="respiratory">Respiratory Rate (breaths/min):</label>
        <input type="text" id="respiratory" name="respiratory" placeholder="e.g., 16" class="form-control">
    </div>
    <div class="mb-3">
        <label for="allergies" class="form-label">Allergies</label>
        <input type="text" class="form-control" id="allergies" name="allergies" placeholder="Enter any known allergies">
    </div>
    <button type="submit" class="btn btn-primary mt-3">Save Vitals</button>
</form>

</body>
</html>
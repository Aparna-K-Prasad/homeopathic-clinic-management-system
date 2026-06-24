<?php
// Start output buffering
ob_start();

// Database connection details
$server = "localhost";
$user = "root";
$pass = "";
$db = "miniprjct";

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Establishing the database connection
$conn = new mysqli($server, $user, $pass, $db);

// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === "POST") {
    // Get form input values
    $name = $_POST['name'];
    $dose = $_POST['dosage'];
    
    // Retrieve the last inserted medicine ID to generate the next one
    $result = $conn->query("SELECT mid FROM medicines ORDER BY mid DESC LIMIT 1");

    if ($result && $row = $result->fetch_assoc()) {
        $last_mid = $row['mid'];
        $numeric_part = (int) substr($last_mid, 1);
        $new_numeric_part = $numeric_part + 1;
        $med_id = 'M' . str_pad($new_numeric_part, 3, '0', STR_PAD_LEFT);
    } else {
        $med_id = 'M001';
    }

    // Prepare an SQL query to insert the medicine details
    $sql = "INSERT INTO medicines (mid, mname, dosage) 
            VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("sss", $med_id, $name, $dose);
        try {
            if ($stmt->execute()) {
                // Success alert with SweetAlert
                echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                      Swal.fire({
                          icon: 'success',
                          title: 'Medicine Added!',
                          text: 'New medicine added successfully!',
                          confirmButtonText: 'OK'
                      }).then(() => {
                          window.location.href = 'new_med.php';
                      });
                       });
                      </script>";
            }
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() === 1062) { // Duplicate entry error code
                echo "<script>
                 document.addEventListener('DOMContentLoaded', function() {
                      Swal.fire({
                          icon: 'warning',
                          title: 'Duplicate Entry',
                          text: 'This medicine already exists with this dosage!',
                          confirmButtonText: 'Go Back'
                      }).then(() => {
                          window.history.back();
                      });
                      });
                      </script>";
            } else {
                echo "<script>
                 document.addEventListener('DOMContentLoaded', function() {
                      Swal.fire({
                          icon: 'error',
                          title: 'Error',
                          text: 'An error occurred: " . addslashes($stmt->error) . "',
                      });
                      });
                      </script>";
            }
        }
        $stmt->close();
    } else {
        echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
              Swal.fire({
                  icon: 'error',
                  title: 'Error',
                  text: 'Error preparing statement: " . addslashes($conn->error) . "',
              });
              });
              </script>";
    }
}

$conn->close();

// Flush the output buffer and disable it
ob_end_flush();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Medicine</title>

    <!-- Preconnect to Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400..700&display=swap" rel="stylesheet">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="/minipro/css/add_med.css">
    
    <!-- SweetAlert2 JS (place at the end of body) -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
</head>
<body>
    <div class="container-fluid mt-5">
        <h1 class="text-center mb-4" style="font-family: 'Dancing Script', cursive; margin-bottom: 150px;">
            New Medicine
        </h1>

        <form method="POST" action="">
            <div class="row mb-3">
                <label for="name" class="col-sm-3 col-form-label">Medicine Name:</label>
                <div class="col-sm-9">
                    <input type="text" id="name" name="name" class="form-control" required>
                </div>
            </div>

            <div class="row mb-3">
                <label for="dosage" class="col-sm-3 col-form-label">Dosage:</label>
                <div class="col-sm-9">
                    <input type="text" id="dosage" name="dosage" class="form-control" required>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-50" style="margin-left: 250px; margin-top: 150px;">
                Save Medicine
            </button>
        </form>
    </div>
</body>
</html>

<?php
session_start(); 

$server = "localhost";
$user = "root";
$password = "";
$db = "miniprjct";
// Connect to the database
$con = mysqli_connect($server, $user, $password, $db);
if (!$con) {
    die("Connection failed: " . mysqli_connect_error());
}

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    echo "User not logged in!!";
    exit();
}

// Get the username from session
$uname = $_SESSION['username'];

// First, get the pid from the patient table using the username
$pid_query = "SELECT pid FROM reg WHERE username = ?";
$stmt = $con->prepare($pid_query);
$stmt->bind_param("s", $uname);
$stmt->execute();
$pid_result = $stmt->get_result();

if ($pid_result->num_rows == 0) {
    echo "Patient not found!";
    exit();
}

$pid = $pid_result->fetch_assoc()['pid'];

// Get selected month and year (default to current month if not specified)
$month = isset($_GET['month']) ? $_GET['month'] : date('m');
$year = isset($_GET['year']) ? $_GET['year'] : date('Y');

// Query to get consultation history for the selected month using pid
$query = "SELECT date, follow
          FROM inv
          WHERE pid = ? 
          AND MONTH(date) = ? 
          AND YEAR(date) = ?
          ORDER BY date DESC";

$stmt = $con->prepare($query);
$stmt->bind_param("sii", $pid, $month, $year);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Consultation History</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Added Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400..900;1,400..900&display=swap" rel="stylesheet">
    <style>
        .month-selector {
            margin: 20px 0;
        }
        .table-container {
            margin: 20px 0;
        }
        /* Added new styles */
        h2 {
            font-family: 'Dancing Script', cursive;
            font-size: 50px;
            font-weight: 700;
            color: #34557a;
        }
        .table thead th {
            background-color: #34557a !important; /* Dark blue color */
            color: #ffffff !important;
            font-family: 'Playfair Display', serif;
            font-weight: 600;
        }
        .table tbody td {
            font-family: 'Playfair Display', serif;
        }
        /* Style for the form elements */
        .form-select, .btn-primary {
            font-family: 'Playfair Display', serif;
        }
        .btn-primary {
            background-color: #2c3e50;
            border-color: #2c3e50;
        }
        .btn-primary:hover {
            background-color: #34495e;
            border-color: #34495e;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="text-center mt-4">Consultation History</h2>
        
        <!-- Month and Year Selector -->
        <div class="month-selector">
            <form method="GET" class="row g-3 justify-content-center">
                <div class="col-auto">
                    <select name="month" class="form-select">
                        <?php
                        for ($m = 1; $m <= 12; $m++) {
                            $selected = ($m == $month) ? 'selected' : '';
                            echo "<option value='$m' $selected>" . date('F', mktime(0, 0, 0, $m, 1)) . "</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-auto">
                    <select name="year" class="form-select">
                        <?php
                        $currentYear = date('Y');
                        for ($y = $currentYear; $y >= $currentYear - 5; $y--) {
                            $selected = ($y == $year) ? 'selected' : '';
                            echo "<option value='$y' $selected>$y</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary">Search</button>
                </div>
            </form>
        </div>

        <!-- Consultation History Table -->
        <div class="table-container">
            <table class="table table-striped table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th>Visited Date</th>
                        <th>Follow-up Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . date('d-m-Y', strtotime($row['date'])) . "</td>";
                            echo "<td>" . ($row['follow'] ? date('d-m-Y', strtotime($row['follow'])) : 'No follow-up scheduled') . "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='2' class='text-center'>No consultations found for selected month</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
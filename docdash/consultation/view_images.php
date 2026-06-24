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

// Ensure default values for year and month
$selected_year = isset($_GET['year']) ? $_GET['year'] : date('Y');
$selected_month = isset($_GET['month']) ? $_GET['month'] : '';

// Get patient name
$patient_query = "SELECT name FROM reg WHERE pid = ?";
$stmt = $conn->prepare($patient_query);
$stmt->bind_param("s", $pid);
$stmt->execute();
$patient_result = $stmt->get_result();
$patient_name = $patient_result->fetch_assoc()['name'];

// Get all visited months for this patient in the selected year
$visited_months_query = "
    SELECT DISTINCT MONTH(upload_date) AS month
    FROM patient_images
    WHERE pid = ? AND YEAR(upload_date) = ?
    ORDER BY MONTH(upload_date) ASC
";
$stmt = $conn->prepare($visited_months_query);
$stmt->bind_param("si", $pid, $selected_year);
$stmt->execute();
$visited_months_result = $stmt->get_result();

$visited_months = [];
while ($row = $visited_months_result->fetch_assoc()) {
    $visited_months[] = $row['month'];
}

// Get all files for this patient grouped by date (for selected month)
$files_by_date = [];
if ($selected_month) {
    $sql = "SELECT * FROM patient_images 
            WHERE pid = ? 
            AND YEAR(upload_date) = ? 
            AND MONTH(upload_date) = ?
            ORDER BY upload_date DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sii", $pid, $selected_year, $selected_month);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $date = $row['upload_date'];
        if (!isset($files_by_date[$date])) {
            $files_by_date[$date] = [];
        }
        $files_by_date[$date][] = $row;
    }
}

// Mapping for months
$months_map = [
    '01' => 'January', '02' => 'February', '03' => 'March', '04' => 'April',
    '05' => 'May', '06' => 'June', '07' => 'July', '08' => 'August',
    '09' => 'September', '10' => 'October', '11' => 'November', '12' => 'December'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Files</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        /* Your styles here */
    </style>
</head>
<body>
    <div class="container mt-4">
        <h2>Patient Files</h2>
        <p>Patient: <?php echo htmlspecialchars($patient_name); ?> (<?php echo htmlspecialchars($pid); ?>)</p>
        <form method="GET" class="mb-4" id="searchForm">
            <input type="hidden" name="pid" value="<?php echo $pid; ?>">
            <div class="row align-items-end">
                <div class="col-md-4">
                    <label for="year">Select Year:</label>
                    <select id="year" name="year" class="form-select" onchange="updateMonths()">
                        <option value="">Select Year</option>
                        <?php
                        $currentYear = date('Y');
                        for ($year = $currentYear; $year >= 2000; $year--) {
                            echo "<option value='$year' " . ($year == $selected_year ? 'selected' : '') . ">$year</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="month">Select Month:</label>
                    <select id="month" name="month" class="form-select" <?php echo $selected_year ? '' : 'disabled'; ?>>
                        <option value="">Select Month</option>
                        <?php 
                        if ($selected_year) {
                            // Show only the visited months and their names
                            foreach ($visited_months as $month_num) {
                                $month_num_str = str_pad($month_num, 2, '0', STR_PAD_LEFT);
                                echo "<option value='$month_num_str' " . ($month_num_str == $selected_month ? 'selected' : '') . ">{$months_map[$month_num_str]}</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
                 
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary">Search</button>
                </div>
            </div>
        </form>

        <script>
            function updateMonths() {
                var year = document.getElementById("year").value;
                var monthSelect = document.getElementById("month");
                monthSelect.disabled = !year;  // Disable if no year selected

                if (year) {
                    // Send AJAX request to fetch months for selected year
                    $.get('fetch_months.php', { year: year, pid: '<?php echo $pid; ?>' }, function(data) {
                        var months = JSON.parse(data);
                        monthSelect.innerHTML = '<option value="">Select Month</option>';
                        months.forEach(function(month) {
                            monthSelect.innerHTML += `<option value="${month}" ${month == '<?php echo $selected_month; ?>' ? 'selected' : ''}>${month}</option>`;
                        });
                    });
                }
            }

            // Call updateMonths on page load to enable/disable months based on selected year
            window.onload = updateMonths;

            // Function to handle the delete action
            function confirmDelete(id, imagePath) {
                Swal.fire({
                    title: 'Are you sure?',
                    text: 'You won\'t be able to revert this!',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Send AJAX request to delete the file
                        $.post('delete_file.php', { id: id, image_path: imagePath }, function(response) {
                            if (response == 'success') {
                                Swal.fire('Deleted!', 'Your file has been deleted.', 'success');
                                location.reload();  // Reload the page to reflect the changes
                            } else {
                                Swal.fire('Error!', 'There was an issue deleting the file.', 'error');
                            }
                        });
                    }
                });
            }
        </script>

        <h5>Selected Year: <?php echo htmlspecialchars($selected_year); ?> | Selected Month: <?php echo $selected_month ? $months_map[$selected_month] : 'N/A'; ?></h5>

        <!-- Display files by date -->
        <?php if (count($files_by_date) > 0): ?>
            <?php foreach ($files_by_date as $date => $files): ?>
                <div class="date-section">
                    <h4><?php echo date('d F Y', strtotime($date)); ?></h4>
                    <div class="row">
                        <?php foreach ($files as $file): ?>
                            <div class="col-md-3">
                                <div class="card">
                                    <!-- Display the image -->
                                    <?php if (pathinfo($file['image_path'], PATHINFO_EXTENSION) == 'jpg' || pathinfo($file['image_path'], PATHINFO_EXTENSION) == 'jpeg' || pathinfo($file['image_path'], PATHINFO_EXTENSION) == 'png'): ?>
                                        <img src="../uploads/<?php echo htmlspecialchars($file['image_path']); ?>" alt="Patient File" class="card-img-top">
                                        <a href="../uploads/<?php echo htmlspecialchars($file['image_path']); ?>" target="_blank" class="btn btn-info">Open Image</a>
                                    <?php endif; ?>
                                    
                                    <!-- Display PDF Open option if file is PDF -->
                                    <?php if (pathinfo($file['image_path'], PATHINFO_EXTENSION) == 'pdf'): ?>
                                        <a href="../uploads/<?php echo htmlspecialchars($file['image_path']); ?>" target="_blank" class="btn btn-success">Open PDF</a>
                                    <?php endif; ?>

                                    <button class="btn btn-danger" onclick="confirmDelete(<?php echo $file['id']; ?>, '<?php echo $file['image_path']; ?>')">Delete</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No files found for the selected month.</p>
        <?php endif; ?>
    </div>
</body>
</html>

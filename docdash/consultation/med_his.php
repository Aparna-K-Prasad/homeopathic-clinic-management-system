<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "miniprjct";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$pid = $_GET['pid'];
$selected_month = isset($_GET['month']) ? $_GET['month'] : date('Y-m'); // Default to current month
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical History</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>

<div class="container mt-4">
    <h3>Medical History</h3>
    
    <form id="searchForm" class="mb-4">
    <input type="hidden" name="pid" value="<?php echo $pid; ?>">
    <div class="row align-items-end">
        <div class="col-md-4">
            <label for="year">Select Year:</label>
            <select id="year" name="year" class="form-control">
                <option value="">Select Year</option>
                <?php
                $currentYear = date('Y');
                for ($year = $currentYear; $year >= 2000; $year--) {
                    echo "<option value='$year'>$year</option>";
                }
                ?>
            </select>
        </div>
        <div class="col-md-4">
            <label for="month">Select Month:</label>
            <select id="month" name="month" class="form-control" disabled>
                <option value="">Select Month</option>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary">Search</button>
        </div>
    </div>
</form>


    <div class="mb-4">
        <a href="view_images.php?pid=<?php echo $pid; ?>&date=<?php echo urlencode($pid); ?>" class="btn btn-primary">View Images & Reports</a>
    </div>

    <!-- Visit History Table -->
    <div class="table-responsive">
        <table class="table table-bordered table-hover">
            <thead class="table-light">
                <tr>
                    <th>Visit Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $start_date = date('Y-m-01', strtotime($selected_month));
                $end_date = date('Y-m-t', strtotime($selected_month));
                
                $sql = "SELECT DISTINCT i.date 
                        FROM inv i 
                        WHERE i.pid = ? 
                        AND i.date BETWEEN ? AND ?
                        ORDER BY i.date DESC";
                
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sss", $pid, $start_date, $end_date);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
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
            </tbody>
        </table>
    </div>

    <!-- Modal for showing details -->
    <div class="modal fade" id="detailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Visit Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="modalContent">
                    <!-- Content will be loaded here -->
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('year').addEventListener('change', function () {
    const year = this.value;
    const pid = document.querySelector('input[name="pid"]').value;
    const monthSelect = document.getElementById('month');
    monthSelect.innerHTML = '<option value="">Select Month</option>'; // Reset month dropdown
    monthSelect.disabled = true;

    if (year) {
        // Fetch months dynamically
        fetch(`get_months.php?pid=${pid}&year=${year}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.months.length > 0) {
                    data.months.forEach(month => {
                        const option = document.createElement('option');
                        option.value = month.value; // Numeric month value (1-12)
                        option.textContent = month.name; // Month name (e.g., January)
                        monthSelect.appendChild(option);
                    });
                    monthSelect.disabled = false; // Enable the month dropdown
                } else {
                    console.warn('No months found for the selected year.');
                    monthSelect.disabled = false; // Enable dropdown, but with no options
                }
            })
            .catch(error => {
                console.error('Error fetching months:', error);
            });
    }
});

document.getElementById('searchForm').addEventListener('submit', function (e) {
    e.preventDefault(); // Prevent the default form submission

    const formData = new FormData(this);
    const pid = formData.get('pid');
    const year = formData.get('year');
    const month = formData.get('month');

    if (!year || !month) {
        alert('Please select both year and month.');
        return;
    }

    // Fetch visit data dynamically
    fetch('get_visits.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.text())
        .then(data => {
            document.querySelector('tbody').innerHTML = data; // Update the table with new data
        })
        .catch(error => {
            console.error('Error:', error);
        });
});


</script>

</body>
</html>

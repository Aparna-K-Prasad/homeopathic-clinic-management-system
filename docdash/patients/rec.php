<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "miniprjct";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get search parameters
$search = isset($_GET['search']) ? $_GET['search'] : '';

// If search is provided, fetch patient details
if ($search) {
    $stmt = $conn->prepare("SELECT pid, name, age FROM reg WHERE pid = ? OR name LIKE ?");
    $searchLike = "%$search%";
    $stmt->bind_param("ss", $search, $searchLike);
    $stmt->execute();
    $searchResult = $stmt->get_result();
}

// Only fetch visits if pid is provided
$pid = isset($_GET['pid']) ? $_GET['pid'] : '';
$selected_month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
$visits = null;
if ($pid) {
    // Fetch patient details
    $stmt = $conn->prepare("SELECT name, age FROM reg WHERE pid = ?");
    $stmt->bind_param("s", $pid);
    $stmt->execute();
    $patient = $stmt->get_result()->fetch_assoc();

    // Fetch visit dates for selected month
    $stmt = $conn->prepare("SELECT i.i_id, i.date, i.symptoms, i.diagnosis, i.follow,i.pay 
                           FROM inv i 
                           WHERE i.pid = ? AND DATE_FORMAT(i.date, '%Y-%m') = ?
                           ORDER BY i.date DESC");
    $stmt->bind_param("ss", $pid, $selected_month);
    $stmt->execute();
    $visits = $stmt->get_result();
}

/*$payment_sql = "SELECT pay FROM inv WHERE i_id = ?";
$payment_stmt = $conn->prepare($payment_sql);
$payment_stmt->bind_param("s", $i_id);
$payment_stmt->execute();
$payment_result = $payment_stmt->get_result();
$payment_row = $payment_result->fetch_assoc();

// Get the payment amount
$amount_paid = $payment_row['pay'] ?? 0; // Default to 0 if not found*/
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Visit History</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .visit-card {
            transition: transform 0.2s;
        }
        .visit-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h2>Patient Visit History</h2>
        
        <!-- Search Form -->
        <div class="row mb-4">
            <div class="col-md-6">
                <form method="GET" class="d-flex gap-2">
                    <input type="text" name="search" 
                           class="form-control" 
                           placeholder="Search by Patient ID or Name"
                           value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn btn-primary">Search</button>
                </form>
            </div>
        </div>

        <?php if (isset($searchResult) && $searchResult->num_rows > 0): ?>
            <!-- Show search results -->
            <div class="mb-4">
                <h4>Search Results</h4>
                <div class="list-group">
                    <?php while($row = $searchResult->fetch_assoc()): ?>
                        <a href="?pid=<?php echo urlencode($row['pid']); ?>" 
                           class="list-group-item list-group-item-action">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong><?php echo htmlspecialchars($row['name']); ?></strong>
                                    <small class="text-muted ms-2">Age: <?php echo htmlspecialchars($row['age']); ?></small>
                                </div>
                                <small class="text-muted">ID: <?php echo htmlspecialchars($row['pid']); ?></small>
                            </div>
                        </a>
                    <?php endwhile; ?>
                </div>
            </div>
        <?php elseif (isset($searchResult)): ?>
            <div class="alert alert-info">No patients found matching your search.</div>
        <?php endif; ?>

        <?php if ($pid && isset($patient)): ?>
            <!-- Show patient visits -->
            <div class="card mb-4">
                <div class="card-body">
                    <h4>Patient Details</h4>
                    <p class="mb-0">
                        <strong>Name:</strong> <?php echo htmlspecialchars($patient['name']); ?> |
                        <strong>Age:</strong> <?php echo htmlspecialchars($patient['age']); ?> |
                        <strong>ID:</strong> <?php echo htmlspecialchars($pid); ?>
                    </p>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-4">
                    <form method="GET" class="d-flex gap-2">
                        <input type="hidden" name="pid" value="<?php echo htmlspecialchars($pid); ?>">
                        <input type="month" name="month" 
                               value="<?php echo htmlspecialchars($selected_month); ?>" 
                               class="form-control">
                        <button type="submit" class="btn btn-primary">Show Visits</button>
                    </form>
                </div>
            </div>

            <?php if($visits->num_rows > 0): ?>
                <div class="row row-cols-1 row-cols-md-2 g-4">
                    <?php while($visit = $visits->fetch_assoc()): ?>
                        <div class="col">
                            <div class="card visit-card h-100">
                                <div class="card-body">
                                    <h5 class="card-title">Visit Date: <?php echo date('d-m-Y', strtotime($visit['date'])); ?></h5>
                                    <p class="card-text"><strong>Symptoms:</strong> <?php echo htmlspecialchars($visit['symptoms']); ?></p>
                                    <p class="card-text"><strong>Diagnosis:</strong> <?php echo htmlspecialchars($visit['diagnosis']); ?></p>
                                    <p class="card-text"><strong>Amount Paid:</strong> ₹<?php echo htmlspecialchars($visit['pay']); ?></p>
                                    <?php 
                                    // Check if follow_up exists in the $visit array
                                    if (isset($visit['follow']) && $visit['follow']): 
                                    ?>
                                        <div class="alert alert-info mt-3">
                                            <strong>Follow-up Date:</strong> 
                                            <?php echo date('d-m-Y', strtotime($visit['follow'])); ?>
                                        </div>
                                    <?php endif; ?>
                                    <a href="view_prescription.php?i_id=<?php echo $visit['i_id']; ?>" 
                                       class="btn btn-sm btn-primary">View Prescription</a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    No visits found for the selected month.
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
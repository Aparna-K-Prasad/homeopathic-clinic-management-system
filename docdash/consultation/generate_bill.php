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

// Fetch investigation and patient details
$sql = "SELECT i.*, r.name, r.age, r.gender 
        FROM inv i 
        JOIN reg r ON i.pid = r.pid 
        WHERE i.i_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $i_id);
$stmt->execute();
$result = $stmt->get_result();
$investigation = $result->fetch_assoc();

// Fetch prescribed medicines with dosage
$sql = "SELECT m.mname, m.dosage, m.price, p.frequency, p.duration, p.quantity 
        FROM prescription p 
        JOIN medicines m ON p.mid = m.mid 
        WHERE p.i_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $i_id);
$stmt->execute();
$medicines = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Function to calculate times per day from frequency
function getTimesPerDay($frequency) {
    $parts = explode('-', $frequency);
    return array_sum(array_map('intval', $parts));
}

// Modified function to update stock considering dosage
function updateMedicineStock($conn, $medicine_name, $dosage, $quantity) {
    $sql = "UPDATE medicines 
            SET stock = stock - ? 
            WHERE mname = ? AND dosage = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("dsi", $quantity, $medicine_name, $dosage);
    return $stmt->execute();
}

// Process stock updates
if (!isset($_SESSION['bill_processed_' . $i_id])) {
    session_start();
    $conn->begin_transaction();
    
    try {
        foreach ($medicines as $medicine) {
            $times_per_day = getTimesPerDay($medicine['frequency']);
            $total_quantity = floatval($medicine['quantity']) * $times_per_day * $medicine['duration'];
            
            // Update stock considering both medicine name and dosage
            if (!updateMedicineStock($conn, $medicine['mname'], $medicine['dosage'], $total_quantity)) {
                throw new Exception("Failed to update stock for " . $medicine['mname'] . " " . $medicine['dosage'] . "mg");
            }
        }
        
        $conn->commit();
        $_SESSION['bill_processed_' . $i_id] = true;
        
    } catch (Exception $e) {
        $conn->rollback();
        die("Error updating stock: " . $e->getMessage());
    }
}

// Initialize grand total
$grand_total = 0;

// Calculate total based on medicines
foreach ($medicines as $medicine) {
    $times_per_day = getTimesPerDay($medicine['frequency']);
    $total_quantity = floatval($medicine['quantity']) * $times_per_day * $medicine['duration'];
    $total = $total_quantity * $medicine['price'];
    $grand_total += $total; // Accumulate the total
}



// Update the inv table with the total payment
$update_pay_sql = "UPDATE inv SET pay = ? WHERE i_id = ?";
$update_stmt = $conn->prepare($update_pay_sql);

// Check if the statement was prepared successfully
if ($update_stmt === false) {
    die("Error preparing update statement: " . $conn->error);
}

// Bind parameters
$update_stmt->bind_param("ds", $grand_total, $i_id); // Assuming grand_total is a double and i_id is a string

// Execute the update
if ($update_stmt->execute()) {
    //Payment updated successfully 
}else{
    die("Error updating payment: " . $update_stmt->error);
    }


// Close the statement
$update_stmt->close();

?>
<!DOCTYPE html>
<html>
<head>
    <title>Medical Bill</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f4f4f4;
            margin: 0;
            padding: 20px;
        }

        .print-container {
            background: white;
            width: 210mm; /* A4 width */
            min-height: 297mm; /* A4 height */
            margin: 0 auto;
            padding: 20mm;
            position: relative;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        .bill-header {
            text-align: center;
            margin-bottom: 20px;
        }

        .bill-header h2 {
            margin-bottom: 10px;
            font-size: 24px;
        }

        .bill-header h4 {
            margin-bottom: 8px;
            font-size: 18px;
        }

        .patient-details {
            margin-bottom: 20px;
            font-size: 14px;
        }

        .bill-table {
            margin-bottom: 20px;
        }

        .table {
            font-size: 14px;
        }

        .table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }

        .frequency-note {
            font-size: 12px;
            color: #666;
        }

        .total-section {
            text-align: right;
            margin-top: 20px;
            font-size: 14px;
        }

        .signature-section {
            margin-top: 30px;
            text-align: right;
        }

        @media print {
            body {
                margin: 0;
                padding: 0;
                background: white;
            }

            .print-container {
                width: 100%;
                min-height: auto;
                padding: 20mm;
                box-shadow: none;
                margin: 0;
            }

            .no-print {
                display: none !important;
            }

            .table {
                page-break-inside: auto;
            }

            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }

            thead {
                display: table-header-group;
            }

            tfoot {
                display: table-footer-group;
            }
        }

        /* Button container styles */
        .button-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            background: white;
            padding: 10px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
    
.button-container button {
            margin-left: 10px;
        }
    </style>
</head>
<body>
    <!-- Move buttons outside the print container -->
    <div class="button-container no-print">
    <button onclick="window.print()" class="btn btn-primary">Print</button>
    <button onclick="window.location.href='pat.php'" class="btn btn-secondary">Back</button>
</div>

    <div class="print-container">
        <div class="bill-header">
            <h2>Medical Prescription & Bill</h2>
            <h4>Dr.Roopa</h4>
            <p> Roopa's Homeo Clinic,Near Devamatha Church,<br>
                        Rajakumari, Kerala, India<br>
            Phone:+91 87794 10334</p>
        </div>

        <div class="patient-details">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Patient Name:</strong> <?php echo htmlspecialchars($investigation['name']); ?></p>
                    <p><strong>Age/Gender:</strong> <?php echo htmlspecialchars($investigation['age']); ?>/<?php echo htmlspecialchars($investigation['gender']); ?></p>
                    <p><strong>Patient ID:</strong> <?php echo htmlspecialchars($investigation['pid']); ?></p>
                </div>
                <div class="col-md-6 text-end">
                    <p><strong>Date:</strong> <?php echo date('d-m-Y', strtotime($investigation['date'])); ?></p>
                    <p><strong>Bill No:</strong> <?php echo htmlspecialchars($i_id); ?></p>
                </div>
            </div>
        </div>

      
        <div class="bill-table">
            <h5>Prescribed Medicines</h5>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Medicine Name</th>
                        <th>Dosage</th>
                        <th>Frequency</th>
                        <th>Duration</th>
                        <th>Quantity per time</th>
                        <th>Total Quantity</th>
                        <th>Price per unit</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $grand_total = 0;
                    foreach ($medicines as $medicine): 
                        $times_per_day = getTimesPerDay($medicine['frequency']);
                        $total_quantity = floatval( $medicine['quantity']) * $times_per_day * $medicine['duration'];
                        $total = $total_quantity * $medicine['price'];
                        $grand_total += $total;

                        $freq_map = [
                            '1-0-0' => 'Once daily (Morning)',
                            '1-0-1' => 'Twice daily (M,N)',
                            '1-1-1' => 'Thrice daily (M,A,N)',
                            '0-0-1' => 'Once daily (Night)',
                            '1-1-0' => 'Twice daily (M,A)'
                        ];
                        $freq_text = $freq_map[$medicine['frequency']] ?? $medicine['frequency'];
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($medicine['mname']); ?></td>
                        <td><?php echo htmlspecialchars($medicine['dosage']); ?> mg</td>
                        <td><?php echo htmlspecialchars($freq_text); ?></td>
                        <td><?php echo htmlspecialchars($medicine['duration']); ?> days</td>
                        <td><?php echo htmlspecialchars($medicine['quantity']); ?></td>
                        <td>
                            <?php echo htmlspecialchars($total_quantity); ?>
                            <div class="frequency-note">
                                (<?php echo $medicine['quantity']; ?> × 
                                <?php echo $times_per_day; ?> times × 
                                <?php echo $medicine['duration']; ?> days)
                            </div>
                        </td>
                        <td>₹<?php echo htmlspecialchars($medicine['price']); ?></td>
                        <td>₹<?php echo htmlspecialchars($total); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="7" class="text-end"><strong>Grand Total:</strong></td>
                        <td><strong>₹<?php echo htmlspecialchars($grand_total); ?></strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="total-section">
            <p>Amount in words: 
                <?php 
                require_once 'NumberToWords.php'; // You'll need to create this file
                $converter = new NumberToWords();
                echo $converter->convert($grand_total) . ' Rupees Only'; 
                ?>
            </p>
        </div>

        <div class="signature-section">
            <p>Doctor's Signature</p>
            <div style="border-top: 1px solid #000; width: 200px; margin-left: auto;"></div>
         </div>
    </div>

    <!-- Keep your existing scripts -->
    <script>
        // ... (your existing JavaScript)
    </script>
</body>
</html>
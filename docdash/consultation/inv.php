<?php
$servername = "localhost";
$username = "root"; // your database username
$password = ""; // your database password
$dbname = "miniprjct"; // your database name

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Retrieve the patient ID (pid) from the URL
$pid = isset($_GET['pid']) ? $_GET['pid'] : null;
if (!$pid) {
    echo '<p>Error: Patient ID not provided.</p>';
    exit;
}

// Fetch username based on pid
$sql = "SELECT name FROM reg WHERE pid = ?";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("s", $pid);
if (!$stmt->execute()) {
    die("Execute failed: " . $stmt->error);
}

$stmt->bind_result($name);
$stmt->fetch();
$stmt->close();

if (!isset($name)) {
    echo '<p>Error: No user found with this Patient ID.</p>';
    exit;
}

// Check if the form is submitted
// Assuming your previous connection and form data retrieval code is already in place

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Start transaction
        $conn->begin_transaction();

        // Get form data
        $pid = $_POST['pid'];
        $symptoms = $_POST['symptoms'];
        $investigation = $_POST['investigation'];
        $diagnosis = $_POST['diagnosis'];
        $follow = !empty($_POST['follow']) ? $_POST['follow'] : null;  // Handle empty follow-up date

        // 1. First insert into inv table (updated to include follow)
        $stmt = $conn->prepare("INSERT INTO inv (i_id, pid, symptoms, investigation, diagnosis, date, follow) 
                              VALUES (?, ?, ?, ?, ?, CURRENT_DATE, ?)");
        if ($stmt === false) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        // Generate new i_id
        $last_id_stmt = $conn->prepare("SELECT i_id FROM inv ORDER BY i_id DESC LIMIT 1");
        $last_id_stmt->execute();
        $last_id_stmt->bind_result($last_id);
        $last_id_stmt->fetch();
        $last_id_stmt->close();

        // Generate new i_id
        $new_id_number = 1;
        if ($last_id) {
            $last_id_number = (int)substr($last_id, 1);
            $new_id_number = $last_id_number + 1;
        }
        $i_id = 'i' . str_pad($new_id_number, 2, '0', STR_PAD_LEFT);

        // Insert into inv table with follow-up date
        $stmt->bind_param("ssssss", $i_id, $pid, $symptoms, $investigation, $diagnosis, $follow);
        if (!$stmt->execute()) {
            throw new Exception("Error inserting into inv table: " . $stmt->error);
        }
        $stmt->close();

        // 2. Then insert into prescription table for each medicine
        $stmt = $conn->prepare("INSERT INTO prescription (mid, i_id, frequency, duration, quantity) VALUES (?, ?, ?, ?, ?)");
        
        // Get the arrays from the form
        $medicines = $_POST['medicine'] ?? [];
        $frequencies = $_POST['frequency'] ?? [];
        $durations = $_POST['duration'] ?? [];
        $quantities = $_POST['quantity'] ?? [];

        // Insert each prescription
        for ($i = 0; $i < count($medicines); $i++) {
            if (empty($medicines[$i])) continue; // Skip empty entries

            // Get mid from medicines table based on medicine name and dosage
            $mid_stmt = $conn->prepare("SELECT mid FROM medicines WHERE mname = ? AND dosage = ? LIMIT 1");
            $mid_stmt->bind_param("si", $medicines[$i], $_POST['dosage'][$i]);
            $mid_stmt->execute();
            $result = $mid_stmt->get_result();
            $medicine_data = $result->fetch_assoc();
            $mid = $medicine_data['mid'];
            $mid_stmt->close();

            if (!$mid) {
                throw new Exception("Medicine not found: " . $medicines[$i] . " with dosage " . $_POST['dosage'][$i]);
            }

            // Prepare and execute the prescription insert
            if (!$stmt->bind_param("sssid", 
                $mid,
                $i_id,
                $frequencies[$i],
                $durations[$i],
                $quantities[$i]
            )) {
                throw new Exception("Binding parameters failed: " . $stmt->error);
            }

            if (!$stmt->execute()) {
                throw new Exception("Error inserting prescription: " . $stmt->error);
            }
        }

        $stmt->close();

        // If everything is successful, commit the transaction
        if ($conn->commit()) {
            // Redirect to bill page with the investigation ID
            echo "<script>
                    window.location.href = 'generate_bill.php?i_id=" . $i_id . "';
                  </script>";
        } else {
            // ... error handling ...
        }

    } catch (Exception $e) {
        // If there's an error, rollback the transaction
        $conn->rollback();
        
        echo "<script>
                alert('Error: " . addslashes($e->getMessage()) . "');
                window.history.back();
              </script>";
    }
}   
?>
<html>
<body>
<h3>Investigation</h3>
<form action="inv.php?pid=<?php echo $pid; ?>" method="POST">
    <input type="hidden" name="pid" value="<?php echo htmlspecialchars($pid); ?>">
    <div class="form-group">
        <label for="symptoms">Symptoms:</label>
        <input type="text" id="symptoms" name="symptoms" placeholder="Enter symptoms" required>
    </div>
    <div class="form-group">
        <label for="investigation">Investigation:</label>
        <input type="text" id="investigation" name="investigation" placeholder="Enter investigation" required>
    </div>
    <div class="form-group">
        <label for="diagnosis">Diagnosis:</label>
        <input type="text" id="diagnosis" name="diagnosis" placeholder="Enter diagnosis" required>
    </div>
    
    <!-- Prescription Section -->
    <div class="form-group">
        <label>Prescription:</label>
        <div id="prescriptionFields">
            <!-- Initial prescription entry -->
            <div class="prescription-entry mb-3">
                <div class="row">
                    <div class="col-md-3 position-relative">
                        <input type="text" name="medicine[]" class="form-control medicine-name" placeholder="Medicine Name" required autocomplete="off">
                        <div class="suggestions-box" style="display: none;"></div>
                    </div>
                    <div class="col-md-2">
                        <input type="text" name="dosage[]" class="form-control" placeholder="Dosage (mg)" required>
                    </div>
                    <div class="col-md-2">
                        <input type="number" name="quantity[]" class="form-control" placeholder="Quantity" required required step="0.01" min="0">
                        </div>
                    <div class="col-md-2">
                        <select name="frequency[]" class="form-control" required>
                            <option value="">Select Frequency</option>
                            <option value="1-0-0">Once daily (Morning)</option>
                            <option value="1-0-1">Twice daily (M,N)</option>
                            <option value="1-1-1">Thrice daily (M,A,N)</option>
                            <option value="0-0-1">Once daily (Night)</option>
                            <option value="1-1-0">Twice daily (M,A)</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="number" name="duration[]" class="form-control" placeholder="Days" required>
                    </div>
                   
                    <div class="col-md-1">
                        <button type="button" class="btn btn-danger btn-sm remove-prescription">✕</button>
                    </div>
                </div>
            </div>
        </div>
        <!-- Add Medicine Button -->
        <button type="button" class="btn btn-secondary btn-sm mt-2" id="addPrescription">+ Add Medicine</button>
    </div>

    <div class="form-group">
        <label for="follow">Follow Up Date:</label>
        <input type="date" id="follow" name="follow">
    </div>
    <button type="submit" class="btn btn-primary">Save</button>
</form>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Function to set up medicine input
    function setupMedicineInput(input) {
        const suggestionsBox = input.parentElement.querySelector('.suggestions-box');
        const row = input.closest('.row');
        const quantityInput = row.querySelector('input[name="quantity[]"]');
        const frequencySelect = row.querySelector('select[name="frequency[]"]');
        const durationInput = row.querySelector('input[name="duration[]"]');

        // Function to calculate total quantity needed
        function calculateTotalQuantity() {
            const quantity = parseFloat(quantityInput.value) || 0;
            const frequency = frequencySelect.value;
            const duration = parseInt(durationInput.value) || 0;
            
            let timesPerDay = 0;
            if (frequency) {
                timesPerDay = frequency.split('-').reduce((a, b) => parseInt(a) + parseInt(b), 0);
            }
            
            return quantity * timesPerDay * duration;
        }

        // Function to check stock availability
        function checkStockAvailability(medicineName, requiredQuantity) {
            return fetch(`check_stock.php?medicine=${encodeURIComponent(medicineName)}&quantity=${requiredQuantity}`)
                .then(response => response.json());
        }

        // Function to update stock status
        function updateStockStatus() {
            const medicineName = input.value;
            if (medicineName) {
                const totalQuantity = calculateTotalQuantity();
                if (totalQuantity > 0) {
                    checkStockAvailability(medicineName, totalQuantity)
                        .then(response => {
                            const stockWarning = row.querySelector('.stock-warning') || 
                                               document.createElement('div');
                            stockWarning.className = 'stock-warning text-danger mt-1';
                            
                            if (!response.available) {
                                stockWarning.textContent = `Insufficient stock! Available: ${response.available_stock}`;
                                input.closest('.prescription-entry').querySelector('button[type="submit"]')
                                    .disabled = true;
                            } else {
                                stockWarning.textContent = '';
                                input.closest('.prescription-entry').querySelector('button[type="submit"]')
                                    .disabled = false;
                            }
                            
                            if (!row.querySelector('.stock-warning')) {
                                input.parentElement.appendChild(stockWarning);
                            }
                        });
                }
            }
        }

        function showSuggestions(query = '') {
            fetch(`fetch_medicines.php?query=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    suggestionsBox.innerHTML = '';
                    if (data.length > 0) {
                        suggestionsBox.style.display = 'block';
                        data.forEach(item => {
                            const div = document.createElement('div');
                            div.className = 'suggestion-item';
                            if (item.stock <= 0) {
                                div.className += ' out-of-stock';
                                div.textContent = `${item.mname}( ${item.dosage}mg) (Out of Stock)`;
                                div.style.color = 'red';
                                // Disable selection for out of stock items
                                div.style.cursor = 'not-allowed';
                            } 
                            else if (item.stock > 0 && item.stock <= 50) {
                                div.className += ' low-stock';
                                div.textContent = `${item.mname}( ${item.dosage}mg) (Low Stock: ${item.stock})`;
                                div.style.color = 'orange';
                                div.style.cursor = 'pointer';

                                div.addEventListener('click', () => {
                                    input.value = item.mname;
                                    const dosageInput = row.querySelector('input[name="dosage[]"]');
                                    if (dosageInput && item.dosage) {
                                        dosageInput.value = item.dosage;
                                    }
                                    suggestionsBox.style.display = 'none';
                                });
                            } 
                            else {
                                div.textContent = `${item.mname} (${item.dosage}mg)`;
                                div.addEventListener('click', () => {
                                    input.value = item.mname;
                                    const dosageInput = row.querySelector('input[name="dosage[]"]');
                                    if (dosageInput && item.dosage) {
                                        dosageInput.value = item.dosage;
                                    }
                                    suggestionsBox.style.display = 'none';
                                });
                            }
                            suggestionsBox.appendChild(div);
                        });
                    } else {
                        suggestionsBox.style.display = 'none';
                    }
                });
        }

        // Add event listeners for quantity, frequency, and duration
        [quantityInput, frequencySelect, durationInput].forEach(element => {
            element.addEventListener('change', updateStockStatus);
            element.addEventListener('input', updateStockStatus);
        });

        input.addEventListener('click', () => showSuggestions());
        input.addEventListener('input', () => {
            const query = input.value.trim();
            if (query.length > 0) showSuggestions(query);
            else suggestionsBox.style.display = 'none';
        });
    }

    // Add Medicine button click handler
    document.getElementById('addPrescription').addEventListener('click', function() {
        const template = document.querySelector('.prescription-entry').cloneNode(true);
        
        // Clear all input values in the cloned template
        template.querySelectorAll('input').forEach(input => input.value = '');
        template.querySelector('select').selectedIndex = 0;
        
        // Setup medicine input for the new row
        const medicineInput = template.querySelector('.medicine-name');
        setupMedicineInput(medicineInput);
        
        // Add remove button functionality
        const removeButton = template.querySelector('.remove-prescription');
        removeButton.addEventListener('click', function() {
            template.remove();
        });
        
        // Add the new row
        document.getElementById('prescriptionFields').appendChild(template);
    });

    // Initialize existing medicine inputs
    document.querySelectorAll('.medicine-name').forEach(input => {
        setupMedicineInput(input);
    });

    // Setup initial remove buttons
    document.querySelectorAll('.remove-prescription').forEach(button => {
        button.addEventListener('click', function() {
            button.closest('.prescription-entry').remove();
        });
    });

    // Close suggestions when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.classList.contains('medicine-name')) {
            document.querySelectorAll('.suggestions-box').forEach(box => {
                box.style.display = 'none';
            });
        }
    });
});
</script>

<style>
.position-relative {
    position: relative !important;
}

.suggestions-box {
    position: absolute !important;
    top: 100% !important;
    left: 0 !important;
    right: 0 !important;
    background: white !important;
    border: 1px solid #ddd !important;
    max-height: 200px !important;
    overflow-y: auto !important;
    z-index: 1000 !important;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1) !important;
}

.suggestion-item {
    padding: 8px 12px !important;
    cursor: pointer !important;
    border-bottom: 1px solid #eee !important;
}

.suggestion-item:not(.out-of-stock):hover {
    background-color: #f5f5f5 !important;
}

.out-of-stock {
    background-color: #ffebee;
    opacity: 0.7;
}

.remove-prescription {
    padding: 0.25rem 0.5rem !important;
    font-size: 0.875rem !important;
    line-height: 1.5 !important;
    border-radius: 0.2rem !important;
}
</style>
</body>
</html>
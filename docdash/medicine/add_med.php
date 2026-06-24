<?php
// Database connection details
$server = "localhost";
$user = "root";
$pass = "";
$db = "miniprjct";

// Establish the database connection
$conn = new mysqli($server, $user, $pass, $db);

// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === "POST") {
    $med_id = $_POST['mid'];
    $qty = (int)$_POST['quantity'];
    $price = isset($_POST['price']) && $_POST['price'] !== '' ? (float)$_POST['price'] : null; // Make price optional

    // Check if the medicine already exists in the database
    $check_sql = "SELECT stock, price FROM medicines WHERE mid = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $med_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        // Medicine exists: Fetch the current stock value
        $row = $check_result->fetch_assoc();
        $current_stock = (int)$row['stock'];

        // Update the stock
        $new_stock = $current_stock + $qty; // Add new quantity to existing stock
        $update_sql = "UPDATE medicines SET stock = ?" . ($price !== null ? ", price = ?" : "") . " WHERE mid = ?"; // Update price only if provided
        $update_stmt = $conn->prepare($update_sql);

        // Bind the parameters for the update
        if ($price !== null) {
            $update_stmt->bind_param("ids", $new_stock, $price, $med_id);
        } else {
            $update_stmt->bind_param("is", $new_stock, $med_id); // Bind without price
        }

        if ($update_stmt->execute()) {
            echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: 'Medicine updated successfully!'
                }).then(() => {
                    window.location.href = 'add_med.php';
                });
            });
            </script>";
        } else {
            echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'Failed to update medicine. Please try again.'
                });
            });
            </script>";
        }
        $update_stmt->close();
    } else {
        echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'warning',
                title: 'Not Found!',
                text: 'Medicine not found!'
            }).then(() => {
                window.location.href = 'add_med.php';
            });
        });
        </script>";
    }

    // Close the check statement
    $check_stmt->close();
}

// Close the database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Medicine</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400..700&display=swap" rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        .suggestions-box {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .suggestion-item {
            padding: 8px 12px;
            cursor: pointer;
            border-bottom: 1px solid #eee;
        }

        .suggestion-item:hover {
            background-color: #f5f5f5;
        }

        .position-relative {
            position: relative;
        }
    </style>
</head>
<body>
    <div class="container-fluid mt-5">
        <h1 class="text-center mb-4" style="font-family: 'Dancing Script', cursive;">
            Add Medicine
        </h1>

        <form method="POST" action="">
            <div class="row mb-3">
                <label for="medicine-name" class="col-sm-3 col-form-label">Medicine Name:</label>
                <div class="col-sm-9 position-relative">
                    <input type="text" id="medicine-name" name="name" 
                           class="form-control" required autocomplete="off"
                           onkeyup="fetchSuggestions(this.value)" 
                           onclick="fetchSuggestions(this.value)"> 
                    <div id="suggestions" class="suggestions-box"></div> <!-- Suggestions box -->
                </div>
            </div>
            
            <!-- Dosage Input -->
            <div class="row mb-3">
                <label for="dosage" class="col-sm-3 col-form-label">Dosage (mg):</label>
                <div class="col-sm-9">
                    <input type="text" id="dosage" name="dosage" class="form-control" required readonly>
                </div>
            </div>
            
            <!-- Quantity Input -->
            <div class="row mb-3">
                <label for="quantity" class="col-sm-3 col-form-label">Quantity:</label>
                <div class="col-sm-9">
                    <input type="number" id="quantity" name="quantity" class="form-control" required>
                </div>
            </div>

            <!-- Price Input -->
            <div class="row mb-3">
                <label for="price" class="col-sm-3 col-form-label">Price (per unit, optional):</label>
                <div class="col-sm-9">
                    <input type="number" id="price" name="price" step="0.01" class="form-control">
                </div>
            </div>

            <!-- Hidden MID Field -->
            <input type="hidden" name="mid" id="mid"> 

            <!-- Submit Button -->
            <button type="submit" class="btn btn-primary w-100 mt-5">
                Add Medicine
            </button>
        </form>
    </div>

    <!-- JavaScript for Auto-Suggestion -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
    const medicineInput = document.getElementById('medicine-name');
    const dosageInput = document.getElementById('dosage');
    const quantityInput = document.getElementById('quantity');
    const priceInput = document.getElementById('price');
    const suggestionsBox = document.getElementById('suggestions');
    const midInput = document.getElementById('mid');
    const form = document.querySelector('form');
    let medicineExists = false;
    let sweetAlertShown = false; // Flag to check if SweetAlert was shown

    // Function to fetch suggestions from the database
    function showSuggestions(query) {
        if (!query) {
            suggestionsBox.innerHTML = '';
            suggestionsBox.style.display = 'none';
            return;
        }

        fetch(`fetch_medicines.php?query=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(data => {
                suggestionsBox.innerHTML = '';
                medicineExists = false; // Reset for each query

                if (data.length > 0) {
                    suggestionsBox.style.display = 'block';
                    data.forEach(item => {
                        const div = document.createElement('div');
                        div.className = 'suggestion-item';
                        div.textContent = `${item.mname} (${item.dosage}mg)`;

                        // When a suggestion is clicked
                        div.onclick = function() {
                            medicineInput.value = item.mname;
                            midInput.value = item.mid;
                            dosageInput.value = item.dosage;
                            dosageInput.readOnly = true;
                            medicineExists = true; // Set to true when a valid suggestion is selected
                            suggestionsBox.style.display = 'none';
                        };

                        suggestionsBox.appendChild(div);
                    });
                } else {
                    suggestionsBox.style.display = 'none';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                suggestionsBox.style.display = 'none';
            });
    }

    // Show suggestions as the user types
    medicineInput.addEventListener('input', function() {
        const query = medicineInput.value.trim();
        showSuggestions(query);

        if (query === '') {
            dosageInput.value = '';
            dosageInput.readOnly = false;
            medicineExists = false; // Reset when input is empty
        }
    });

    // Close suggestions if clicked outside the input or suggestion box
    document.addEventListener('click', (e) => {
        if (!medicineInput.contains(e.target) && !suggestionsBox.contains(e.target)) {
            suggestionsBox.style.display = 'none';
        }
    });

    // Listen for focus events on other fields
    [dosageInput, quantityInput, priceInput].forEach(field => {
        field.addEventListener('focus', function(e) {
            // If medicine doesn't exist and user tries to focus on another field
            if (!medicineExists && !sweetAlertShown) {
                e.preventDefault(); // Prevent focus on the field
                sweetAlertShown = true; // Set flag to prevent re-triggering the alert
                Swal.fire({
                    title: 'Medicine Not Found!',
                    text: 'This medicine does not exist in the database. Would you like to add a new medicine?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, add new medicine'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Redirect to add new medicine page if confirmed
                        window.location.href = `new_med.php?name=${encodeURIComponent(medicineInput.value.trim())}`;
                    }
                });
            }
        });
    });

    // Handle form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault(); // Prevent form from submitting immediately

        const medicineName = medicineInput.value.trim();

        // Check if the medicine exists in the database or if it's selected from the suggestions
        if (!medicineExists && !sweetAlertShown) {
            // Trigger SweetAlert if the medicine is not found in the database
            sweetAlertShown = true; // Set flag to prevent re-triggering
            Swal.fire({
                title: 'Medicine Not Found!',
                text: 'This medicine does not exist in the database. Would you like to add a new medicine?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, add new medicine'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Redirect to add new medicine page if confirmed
                    window.location.href = `new_med.php?name=${encodeURIComponent(medicineName)}`;
                }
            });
        } else {
            // Proceed with form submission if the medicine exists
            this.submit();
        }
    });
});

    </script>
</body>
</html>

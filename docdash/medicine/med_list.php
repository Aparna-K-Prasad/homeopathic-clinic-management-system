<?php
// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database connection
$server = "localhost";
$user = "root";
$password = "";
$db = "miniprjct";

$con = mysqli_connect($server, $user, $password, $db);
if (!$con) {
    die("Connection failed: " . mysqli_connect_error());
}

// Function to fetch medicines based on search term
function fetchMeds($con, $searchTerm) {
    $searchTerm = mysqli_real_escape_string($con, $searchTerm);
    $sql = "SELECT * FROM medicines
            WHERE (mname LIKE '%$searchTerm%' 
              OR dosage LIKE '%$searchTerm%')
              ";
    return mysqli_query($con, $sql);
}

$sqlq=mysqli_query($con, "SELECT * FROM medicines WHERE stock >= 50 ORDER BY stock ASC");
$outOfStockMeds = mysqli_query($con, "SELECT * FROM medicines WHERE stock < 0");

// Fetch medicines with stock less than 50
$lowStockMeds = mysqli_query($con, "SELECT * FROM medicines WHERE stock < 50 AND stock > 0 ORDER BY stock ASC");

$meds = fetchMeds($con, ''); // Default fetch for all medicines
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medicine List</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400..700&display=swap" rel="stylesheet">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/minipro/css/med_list.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<div class="container">
    <h1 class="text-center">Medicine List</h1>
    <div class="mb-4 text-end">
        <span class="edit-bin" id="editSelected" title="Edit Selected">✏️</span>
        <span class="trash-bin" id="deleteSelected" title="Delete Selected">🗑️</span>
    </div>

    <!-- Search Form -->
    <div class="mb-4">
        <input type="text" id="searchTerm" class="form-control" placeholder="Search by Name, Dosage.">
    </div>
    <h2>Out of Stock Medicines</h2>
    <div class="table-responsive"> 
        <table class="table table-striped" id="outOfStockTable">
            <thead>
                <tr>
                    <th>Sl. No.</th>
                    <th>Medicine Name</th>
                    <th>Dosage</th>
                    <th>Price(per unit)</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $slno=1;
                if (mysqli_num_rows($outOfStockMeds) > 0) {
                    while ($row = mysqli_fetch_assoc($outOfStockMeds)) {
                        echo "<tr data-mid='" . $row['mid'] . "'>";
                        echo "<td class='text-center'>" . $slno++ . "</td>";
                        echo "<td>" . htmlspecialchars($row['mname']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['dosage']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['price']) . "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='3' class='text-center'>No Out of Stock Medicines</td></tr>";
                }
                ?>
            </tbody>
        </table><br>
    </div>

    <!-- Add this section to display low stock medicines -->
    <h2>Low Stock Medicines (Less than 50)</h2>
    <div class="table-responsive"> 
        <table class="table table-striped" id="lowStockTable">
            <thead>
                <tr>
                <th>Sl. No.</th>
                    <th>Medicine Name</th>
                    <th>Dosage</th>
                    <th>Price(per unit)</th>
                    <th>Stock</th>
                </tr>
            </thead>
            <tbody>
                <?php
                 $slno = 1;
                if (mysqli_num_rows($lowStockMeds) > 0) {
                    while ($row = mysqli_fetch_assoc($lowStockMeds)) {
                        echo "<tr data-mid='" . $row['mid'] . "'>";
                        echo "<td class='text-center'>" . $slno++ . "</td>";
                        echo "<td>" . htmlspecialchars($row['mname']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['dosage']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['price']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['stock']) . "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='4' class='text-center'>No Low Stock Medicines</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>


    <h2>Medicines in Stock</h2>
        <div class="table-responsive"> 
        <table class="table table-striped" id="medTable">
            <thead>
                <tr>
                    <th>Sl. No.</th>
                    <th>Medicine Name</th>
                    <th>Dosage</th>
                    <th>Price(per unit)</th>
                    <th>Stock</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $slno = 1;
                if (mysqli_num_rows($sqlq) > 0) {
                    while ($row = mysqli_fetch_assoc($sqlq)) {
                        echo "<tr data-mid='" . $row['mid'] . "'>";
                        echo "<td class='text-center'>" . $slno++ . "</td>";
                        echo "<td>" . htmlspecialchars($row['mname']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['dosage']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['price']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['stock']) . "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='5' class='text-center'>No Medicines Found</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

            </div>
   

<!-- The Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2 style="margin-bottom: 50px;" >Edit Medicine Details</h2>
        <form id="editForm">
            <input type="hidden" id="mid" name="mid">
            <div class="mb-3">
                <label for="mname" class="form-label">Medicine Name</label>
                <input type="text" id="mname" name="mname" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="dosage" class="form-label">Dosage</label>
                <input type="text" id="dosage" name="dosage" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="price" class="form-label">Price(per unit)</label>
                <input type="number" id="price" name="price" class="form-control" step="0.01" required>
            </div>
            <div class="mb-3">
                <label for="stock" class="form-label">Stock</label>
                <input type="number" id="stock" name="stock" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary w-100 mt-5">Save Changes</button>
        </form>
    </div>
</div>

<script>
    $(document).ready(function() {
        // Highlight row on click
        $("#medTable tbody").on("click", "tr", function() {
            $(this).toggleClass("highlight"); // Toggle highlight class
        });
        $("#lowStockTable tbody").on("click", "tr", function() {
            $(this).toggleClass("highlight"); // Toggle highlight class
        });
        $("#outOfStockTable tbody").on("click", "tr", function() {
            $(this).toggleClass("highlight"); // Toggle highlight class
        });
        
        // Handle search input
        $("#searchTerm").on("input", function() {
            let searchTerm = $(this).val();

            $.ajax({
                url: "fetch_meds.php", 
                type: "POST",
                data: { searchTerm: searchTerm },
                success: function(data) {
                    // Parse the JSON response
                    const results = JSON.parse(data);

                    // Clear existing rows in all tables
                    $("#medTable tbody").empty();
                    $("#lowStockTable tbody").empty();
                    $("#outOfStockTable tbody").empty();

                    // Populate Medicines Table
                    results.medicines.forEach(function(medicine, index) {
                        $("#medTable tbody").append(`
                            <tr data-mid="${medicine.mid}">
                                <td>${index + 1}</td> <!-- Serial number -->
                                <td>${medicine.mname}</td>
                                <td>${medicine.dosage}</td>
                                <td>${medicine.price}</td>
                                <td>${medicine.stock}</td>
                            </tr>
                        `);
                    });

                    // Populate Low Stock Table
                    results.lowStock.forEach(function(medicine,index) {
                        $("#lowStockTable tbody").append(`
                            <tr data-mid="${medicine.mid}">
                                <td>${index + 1}</td> <!-- Serial number -->
                                <td>${medicine.mname}</td>
                                <td>${medicine.dosage}</td>
                                <td>${medicine.price}</td>
                                <td>${medicine.stock}</td>
                            </tr>
                        `);
                    });

                    // Populate Out of Stock Table
                    results.outOfStock.forEach(function(medicine,index) {
                        $("#outOfStockTable tbody").append(`
                            <tr data-mid="${medicine.mid}">
                            <td>${index + 1}</td> <!-- Serial number -->
                                <td>${medicine.mname}</td>
                                <td>${medicine.dosage}</td>
                                <td>${medicine.price}</td>
                               
                            </tr>
                        `);
                    });
                },
                error: function(xhr, status, error) {
                    console.error("Search error: ", error); // Log any errors
                }
            });
        });

        // Delete selected medicines on trash bin icon click
        $("#deleteSelected").click(function() {
            let selectedRows = $("#medTable tbody tr.highlight, #outOfStockTable tbody tr.highlight, #lowStockTable tbody tr.highlight");

            if (selectedRows.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'No selection',
                    text: 'Please select a medicine to delete.',
                });
                return;
            }

            // Collect IDs of selected rows
            let idsToDelete = [];
            selectedRows.each(function() {
                let mid = $(this).data("mid"); // Assuming each row has a data attribute 'data-mid'
                if (mid) {
                    idsToDelete.push(mid);
                }
            });

            Swal.fire({
                title: 'Are you sure?',
                text: 'This will permanently delete the selected medicines.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Delete'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Send AJAX request to delete the selected medicines
                    $.ajax({
                        url: "delete_meds.php", // URL to your delete endpoint
                        type: "POST",
                        data: { mids: idsToDelete }, // Send the array of IDs
                        success: function(response) {
                            Swal.fire(
                                'Deleted!',
                                'Medicines deleted successfully.',
                                'success'
                            );
                            location.reload(); // Reload the page after deletion
                        },
                        error: function(xhr, status, error) {
                            console.error("Error deleting medicines: ", error);
                            Swal.fire(
                                'Failed!',
                                'Failed to delete medicines.',
                                'error'
                            );
                        }
                    });
                }
            });
        });

        // Open edit modal on edit bin icon click
        $("#editSelected").click(function() {
            let selectedRow = $("#medTable tbody tr.highlight, #outOfStockTable tbody tr.highlight, #lowStockTable tbody tr.highlight");

            if (selectedRow.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'No selection',
                    text: 'Please select a medicine to edit.',
                });
                return;
            } else if (selectedRow.length > 1) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Too many selections',
                    text: 'Please select only one medicine to edit.',
                });
                return;
            }

            // Retrieve data from selected row
            let mid = selectedRow.data("mid");
            let mname = selectedRow.find("td:eq(1)").text().trim(); // Medicine Name
            let dosage = selectedRow.find("td:eq(2)").text().trim(); // Dosage
            let price = selectedRow.find("td:eq(3)").text().trim(); // Price
            let stock = selectedRow.find("td:eq(4)").text().trim(); // Stock

            // Set the data in the modal
            $("#mid").val(mid);
            $("#mname").val(mname);
            $("#dosage").val(dosage);
            $("#price").val(price);
            $("#stock").val(stock);

            // Show the modal
            $("#editModal").fadeIn();
        });

        // Handle form submission for editing
        $("#editForm").submit(function(e) {
            e.preventDefault(); // Prevent the form from submitting the default way

            $.ajax({
                url: "edit_meds.php", // URL to update medicine details
                type: "POST",
                data: $(this).serialize(),
                success: function(response) {
                    Swal.fire({
                        title: 'Success!',
                        text: 'Medicine details updated successfully.',
                        icon: 'success',
                    });
                    $("#editModal").fadeOut(); // Hide the modal
                    location.reload(); // Reload the page to see updated data
                },
                error: function() {
                    Swal.fire({
                        title: 'Error!',
                        text: 'There was an error updating the medicine details.',
                        icon: 'error',
                    });
                }
            });
        });

        // Close the modal on close button click
        $(".close").click(function() {
            $("#editModal").fadeOut();
        });
    });
</script>
<style>
    .highlight {
        background-color: #f0f8ff; /* Light blue background for selected rows */
    }
    #editModal {
        display: none; /* Hidden by default */
        position: fixed; 
        z-index: 999; 
        left: 0;
        top: 0;
        width: 100%; 
        height: 100%; 
        overflow: auto; 
        background-color: rgba(0,0,0,0.4); 
    }
    .modal-content {
        background-color: #fefefe;
        margin: 15% auto; 
        padding: 20px;
        border: 1px solid #888;
        width: 50%; 
    }
    .close {
        color: #aaa;
        float: right;
        font-size: 28px;
        font-weight: bold;
    }
    .close:hover,
    .close:focus {
        color: black;
        text-decoration: none;
        cursor: pointer;
    }
</style>
</body>
</html>

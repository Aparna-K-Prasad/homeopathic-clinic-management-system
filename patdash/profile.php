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

// Initialize variables
$dob = null;

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    echo "User not logged in!!";
    exit();
}

// Get the username from session
$uname = $_SESSION['username'];

// Check if the profile already exists
$existingProfileQuery = mysqli_query($con, "SELECT * FROM reg WHERE username='$uname'");
$existingProfile = mysqli_fetch_assoc($existingProfileQuery);
if ($existingProfile) {
    // Check if age is available
    $dob = new DateTime($existingProfile['dob']);
    $today = new DateTime();
    
    // Calculate age
     $diff = $today->diff($dob);

    // Set age based on available time differences
    if ($diff->y > 0) {
        $age = $diff->y . ' years';
    } elseif ($diff->m > 0) {
        $age = $diff->m . ' months';
    } elseif ($diff->d > 0) {
        $age = $diff->d . ' days';
    } else {
        $age = 'No age';
    }
    
    if ($existingProfile['age'] !== null && $existingProfile['age'] !== '') {
        $age = $existingProfile['age'];
    }
} else {
    $age = 'No age';
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve form data
    $name = mysqli_real_escape_string($con, $_POST['name']);
    $dob = mysqli_real_escape_string($con, $_POST['dob']);
    $age = mysqli_real_escape_string($con, $_POST['age']);
    $gender = mysqli_real_escape_string($con, $_POST['gender']);
    $mar = mysqli_real_escape_string($con, $_POST['marital_status']);
    $phone = mysqli_real_escape_string($con, $_POST['phone']);
    $email = mysqli_real_escape_string($con, $_POST['email']);
    $blood = mysqli_real_escape_string($con, $_POST['blood_group']);
    $address = mysqli_real_escape_string($con, $_POST['address']);

    if ($existingProfile) {
        $updateQuery = "UPDATE reg SET name='$name', dob='$dob', gender='$gender', marital='$mar', phone='$phone', email='$email', blood='$blood', address='$address' WHERE username='$uname'";
        if (mysqli_query($con, $updateQuery)) {
            $_SESSION['alert_message'] = "Profile updated successfully!";
            $_SESSION['alert_type'] = "success";
        } else {
            $_SESSION['alert_message'] = "Error updating profile: " . mysqli_error($con);
            $_SESSION['alert_type'] = "error";
        }
    } else { 
        $result = mysqli_query($con, "SELECT pid FROM reg ORDER BY pid DESC LIMIT 1");
        $row = mysqli_fetch_assoc($result);
        
        if ($row) {
            $last_pid = $row['pid'];
            $numeric_part = (int)substr($last_pid, 3);
            $new_numeric_part = $numeric_part + 1;
            $patient_id = 'PID' . str_pad($new_numeric_part, 3, '0', STR_PAD_LEFT);
        } else {
            $patient_id = 'PID001';
        }

        $sql = "INSERT INTO reg (pid, username, name, dob, age, phone, email, gender, marital, blood, address) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $con->prepare($sql);
        $stmt->bind_param("sssssssssss", $patient_id, $uname, $name, $dob, $age, $phone, $email, $gender, $mar, $blood, $address);

        if ($stmt->execute()) {
            $_SESSION['alert_message'] = "Profile created successfully!";
            $_SESSION['alert_type'] = "success";
        } else {
            $_SESSION['alert_message'] = "Error creating profile: " . $stmt->error;
            $_SESSION['alert_type'] = "error";
        }
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Get alert message from session and clear it
$alertMessage = $_SESSION['alert_message'] ?? '';
$alertType = $_SESSION['alert_type'] ?? '';
unset($_SESSION['alert_message'], $_SESSION['alert_type']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400..700&display=swap" rel="stylesheet">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/boxicons@2.1.1/css/boxicons.min.css'>
    <link rel="stylesheet" href="/minipro/css/profile.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <?php if ($alertMessage): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: '<?php echo $alertType; ?>',
                title: '<?php echo $alertType === "success" ? "Success" : "Error"; ?>',
                text: '<?php echo addslashes($alertMessage); ?>',
                confirmButtonText: 'OK'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.reload();
                }
            });
        });
    </script>
    <?php endif; ?>

    <div class="container">
        <div class="card mt-4">
            <div class="card-header" style="display: flex; align-items: center; justify-content: space-between;">
                <h2 style="margin-top: 50px; margin-bottom: 30px; margin-left:375px;">Your Profile Details</h2>
                <i class='bx bx-pencil' style="font-size: 24px; cursor: pointer;margin-top: 50px; margin-bottom: 30px;" data-bs-toggle="modal" data-bs-target="#editProfileModal"></i>
            </div>

            <div class="card-body">
            <?php if (empty($existingProfile)): ?>
                <div class="alert alert-warning" role="alert">
                    You are not registered. Please register.
                    <i class='bx bx-pencil' style="font-size: 24px; cursor: pointer; margin-left: 10px;" data-bs-toggle="modal" data-bs-target="#editProfileModal"></i>
                </div>
            <?php else: ?>
                <p><strong>Patient Id:</strong> <?php echo htmlspecialchars($existingProfile['pid'] ?? 'Not Registered'); ?></p>
                <p><strong>Name:</strong> <?php echo htmlspecialchars($existingProfile['name'] ?? 'Not Registered'); ?></p>
                <p><strong>DOB:</strong> <?php echo isset($dob) ? htmlspecialchars($dob->format('d-m-Y')) : ''; ?></p>
                <p><strong>Age:</strong> <?php echo htmlspecialchars($age); ?></p>
                <p><strong>Gender:</strong> <?php echo htmlspecialchars($existingProfile['gender'] ?? ''); ?></p>
                <p><strong>Marital Status:</strong> <?php echo htmlspecialchars($existingProfile['marital'] ?? ''); ?></p>
                <p><strong>Phone:</strong> <?php echo htmlspecialchars($existingProfile['phone'] ?? ''); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($existingProfile['email'] ?? ''); ?></p>
                <p><strong>Blood Group:</strong> <?php echo htmlspecialchars($existingProfile['blood'] ?? ''); ?></p>
                <p><strong>Address:</strong> <?php echo htmlspecialchars($existingProfile['address'] ?? ''); ?></p>
            <?php endif; ?>
            </div>

            <!-- Edit Profile Modal -->
            <div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 style="font-family: 'Dancing Script', cursive;font-size: 2.5rem; color: #224d7a;text-align: center;" class="modal-title" id="editProfileModalLabel">Edit Profile</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form method="POST" id="profileForm">
                                <input type="hidden" name="username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                                <div class="mb-3">
                                    <label for="modal-name" class="form-label">Name:</label>
                                    <input type="text" id="modal-name" name="name" class="form-control" required value="<?php echo $existingProfile['name'] ?? ''; ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="modal-dob" class="form-label">Date of Birth:</label>
                                    <input type="date" id="modal-dob" name="dob" class="form-control" required value="<?php echo $existingProfile['dob'] ?? ''; ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="modal-age" class="form-label">Age:</label>
                                    <input type="text" id="modal-age" name="age" class="form-control" readonly value="<?php echo $age; ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="modal-gender" class="form-label">Gender:</label>
                                    <select id="modal-gender" name="gender" class="form-select" required>
                                        <option value="" disabled>Select gender</option>
                                        <option value="Male" <?php echo (isset($existingProfile) && $existingProfile['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                                        <option value="Female" <?php echo (isset($existingProfile) && $existingProfile['gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                                        <option value="Other" <?php echo (isset($existingProfile) && $existingProfile['gender'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="modal-marital_status" class="form-label">Marital Status:</label>
                                    <select id="modal-marital_status" name="marital_status" class="form-select" required>
                                        <option value="Single" <?php echo (isset($existingProfile) && $existingProfile['marital'] == 'Single') ? 'selected' : ''; ?>>Single</option>
                                        <option value="Married" <?php echo (isset($existingProfile) && $existingProfile['marital'] == 'Married') ? 'selected' : ''; ?>>Married</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="modal-phone" class="form-label">Phone:</label>
                                    <input type="tel" id="modal-phone" name="phone" class="form-control" required value="<?php echo $existingProfile['phone'] ?? ''; ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="modal-email" class="form-label">Email:</label>
                                    <input type="email" id="modal-email" name="email" class="form-control" required value="<?php echo $existingProfile['email'] ?? ''; ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="modal-blood_group" class="form-label">Blood-Group:</label>
                                    <select id="modal-blood_group" name="blood_group" class="form-select" required>
                                        <option value="" disabled>Select blood-group</option>
                                        <option value="A+" <?php echo (isset($existingProfile) && $existingProfile['blood'] == 'A+') ? 'selected' : ''; ?>>A+</option>
                                        <option value="A-" <?php echo (isset($existingProfile) && $existingProfile['blood'] == 'A-') ? 'selected' : ''; ?>>A-</option>
                                        <option value="B+" <?php echo (isset($existingProfile) && $existingProfile['blood'] == 'B+') ? 'selected' : ''; ?>>B+</option>
                                        <option value="B-" <?php echo (isset($existingProfile) && $existingProfile['blood'] == 'B-') ? 'selected' : ''; ?>>B-</option>
                                        <option value="AB+" <?php echo (isset($existingProfile) && $existingProfile['blood'] == 'AB+') ? 'selected' : ''; ?>>AB+</option>
                                        <option value="AB-" <?php echo (isset($existingProfile) && $existingProfile['blood'] == 'AB-') ? 'selected' : ''; ?>>AB-</option>
                                        <option value="O+" <?php echo (isset($existingProfile) && $existingProfile['blood'] == 'O+') ? 'selected' : ''; ?>>O+</option>
                                        <option value="O-" <?php echo (isset($existingProfile) && $existingProfile['blood'] == 'O-') ? 'selected' : ''; ?>>O-</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="modal-address" class="form-label">Address:</label>
                                    <input type="text" id="modal-address" name="address" class="form-control" required value="<?php echo $existingProfile['address'] ?? ''; ?>">
                                </div>
                                <button type="submit" class="btn btn-primary" style="display: block; margin: 0 auto;">Save</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const dobInput = document.getElementById('modal-dob');
            const ageInput = document.getElementById('modal-age');

            dobInput.addEventListener('change', function() {
                const dob = new Date(dobInput.value);
                const today = new Date();
                dob.setHours(0, 0, 0, 0);
                today.setHours(0, 0, 0, 0);

                let age = today.getFullYear() - dob.getFullYear();
                const m = today.getMonth() - dob.getMonth();
                const d = today.getDate() - dob.getDate();

                if (m < 0 || (m === 0 && d < 0)) {
                    age--;
                }

                if (age > 0) {
                    ageInput.value = age + ' years';
                } else if (m > 0) {
                    ageInput.value = m + ' months';
                } else if (d > 0) {
                    ageInput.value = d + ' days';
                } else {
                    ageInput.value = 'No age';
                }
            });
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
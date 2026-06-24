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
    header("Location: /minipro/signlog/sign.php");
    exit();
}

// Get the username from the session
$uname = $_SESSION['username'];

// Check if the username is already registered
$username_check_query = "SELECT * FROM reg WHERE username = ?";
$username_stmt = $con->prepare($username_check_query);
$username_stmt->bind_param("s", $uname);
$username_stmt->execute();
$username_result = $username_stmt->get_result();
if ($username_result->num_rows > 0) {
    $_SESSION['username'] = $uname; // Store the username in the session
        header("Location: /minipro/patdash/pdash.html"); // Redirect to the patient dashboard
        exit();
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve and sanitize form data
    $name = mysqli_real_escape_string($con, $_POST['name']);
    $dob = mysqli_real_escape_string($con, $_POST['dob']);
    $age = mysqli_real_escape_string($con, $_POST['age']);
    $gender = mysqli_real_escape_string($con, $_POST['gender']);
    $marital_status = mysqli_real_escape_string($con, $_POST['marital-status']);
    $phone = mysqli_real_escape_string($con, $_POST['phone']);
    $email = mysqli_real_escape_string($con, $_POST['email']);
    $blood_group = mysqli_real_escape_string($con, $_POST['blood-group']);
    $address = mysqli_real_escape_string($con, $_POST['address']);

    // Validate phone number
    if (!is_numeric($phone) || strlen($phone) != 10) {
        echo "<script>
            Swal.fire({
                icon: 'error',
                title: 'Invalid Phone Number',
                text: 'Please enter a valid 10-digit phone number.',
            });
        </script>";
        exit();
    }

    // Get the latest patient ID
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

    // Insert data into the database
    $sql = "INSERT INTO `reg` (`pid`, `username`, `name`, `dob`, `age`, `phone`, `email`, `gender`, `marital`, `blood`, `address`) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("sssssssssss", $patient_id, $uname, $name, $dob, $age, $phone, $email, $gender, $marital_status, $blood_group, $address);

    if ($stmt->execute()) {
        $_SESSION['patient_id'] = $patient_id;
    
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'success',
                    title: 'Registration Successful!',
                    html: `<p>Your Patient ID: <strong>$patient_id</strong></p>
                           <p>Your Username: <strong>$uname</strong></p>
                           <p>Please keep this information safe.</p>`,
                    confirmButtonText: 'Go to Login',
                }).then(() => {
                    window.location.href = '/minipro/signlog/log.html';
                });
            });
        </script>";
    } else {
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Registration Failed',
                    text: 'There was an error: " . $stmt->error . "',
                });
            });
        </script>";
    }
    
    
    $stmt->close();
    $con->close();
}

?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Form</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400..700&display=swap" rel="stylesheet">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/boxicons/css/boxicons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/minipro/css/reg.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* General Error Styling */
        .error {
            font-size: 1.2em;
        }
    
        /* Red color for errors in the left section */
        .left-section .error {
            color: rgb(176, 62, 62); /* Red */
        }
    
        /* White color for errors in the right section */
        .right-section .error {
            color: rgb(203, 191, 191);
        }
    </style>
    
</head>
<body>
    <header>
        <div class="header-container">
            <h1>Roopa's Homeo Clinic</h1>
            <nav>
                <ul>
                    <li><a href='/minipro/home/home.html'>Home</a></li>
                    <li><a href='/minipro/signlog/logout.php'>Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>
    <div class="container">
        <section class="left-section">
            <h2>General Information</h2>
            <form id="general-info-form" method="POST" action="" >
            <input type="hidden" name="uname" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                <label for="name"><i class='bx bxs-user'></i> Name*</label>
                <div class="nameFields">
                    <input type="text" id="name" name="name" placeholder="Name" required>
                    <div class="error" id="name-error"></div>
                </div>
        
                <label for="dob"><i class='bx bxs-calendar'></i> DOB*</label>
                <div class="dobFields">
                    <input type="date" id="dob" name="dob" placeholder="DOB" required>
                    <div class="error" id="dob-error"></div>
                </div>
        
                <label for="age"><i class='bx bxs-hourglass'></i> Age</label>
                <input type="text" id="age" name="age" placeholder="Age" readonly>
        
                <label for="gender"><i class='bx bxs-user'></i> Gender*</label>
<select id="gender" name="gender" required>
    <option value="">Select Gender</option>
    <option value="Male">Male</option>
    <option value="Female">Female</option>
    <option value="Others">Others</option>
</select>
<div class="error" id="gender-error"></div>

        
                <label for="marital-status"><i class='bx bx-heart'></i> Marital Status</label>
                <select id="marital-status" name="marital-status" >
                    <option value="">Select</option>
                    <option value="Single">Single</option>
                    <option value="Married">Married</option>
                </select>
                <div class="error" id="marital-status-error"></div>
        
                <label for="blood-group"><i class='bx bx-droplet'></i> Blood Group*</label>
                <select id="blood-group" name="blood-group" required>
                    <option value="">Select</option>
                    <option value="A+">A+</option>
                    <option value="A-">A-</option>
                    <option value="B+">B+</option>
                    <option value="B-">B-</option>
                    <option value="O+">O+</option>
                    <option value="O-">O-</option>
                    <option value="AB+">AB+</option>
                    <option value="AB-">AB-</option>
                </select>
                <div class="error" id="blood-group-error"></div>
    </section>
        

        <section class="right-section">
            <h2>Contact Details</h2>
               <label for="phone">Phone Number*</label>
                <input type="tel" id="phone" name="phone" placeholder="Your Phone Number" required>
                <div class="error" id="phone-error"></div>
        
                <label for="email">Your Email</label>
                <input type="email" id="email" name="email" placeholder="Your Email" >
                <div class="error" id="email-error"></div>

                <label for="address"><i class='bx bxs-map'></i> Address*</label>
                <textarea id="address" name="address" placeholder="Address" rows="8" required></textarea>
                <div class="error" id="address-error"></div>
                
                <button type="submit" class="btn">Register</button>
            </form>
    </section>
        
    </div>

    <script src="/minipro/js/reg.js">
    </script>
</body>
</html>

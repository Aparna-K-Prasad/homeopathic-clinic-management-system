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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve and sanitize form data
    $name = mysqli_real_escape_string($con, $_POST['name']);
    $dob = mysqli_real_escape_string($con, $_POST['dob']);
    $age = mysqli_real_escape_string($con, $_POST['age']);
    $gender = mysqli_real_escape_string($con, $_POST['gender']);
    $marital_status = mysqli_real_escape_string($con, $_POST['marital-status']);
    $phone = mysqli_real_escape_string($con, $_POST['phone']);
    
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


    $email = mysqli_real_escape_string($con, $_POST['email']);
    $blood_group = mysqli_real_escape_string($con, $_POST['blood-group']);
    $address = mysqli_real_escape_string($con, $_POST['address']);

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

    // Check for existing email in the signup table
    $stmt = $con->prepare("SELECT username FROM signup WHERE username = ? OR username = ?");
    $stmt->bind_param("ss", $email, $phone);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Username already exists, check which one
        $existing_usernames = [];
        while ($row = $result->fetch_assoc()) {
            $existing_usernames[] = $row['username'];
        }

        // Assign a username based on the rules
        if (in_array($email, $existing_usernames)) {
            // Email exists, check phone
            if (in_array($phone, $existing_usernames)) {
                // Both exist, create a unique username
                $username = strtolower(substr($name, 0, 3));
                $random_number = rand(100, 999); // 3-digit random number
                $username = $username . $random_number;

                // Ensure the username is unique
                while (in_array($username, $existing_usernames)) {
                    $random_number = rand(100, 999);
                    $username = strtolower(substr($name, 0, 3)) . $random_number;
                }
            } else {
                // Only email exists, use phone as username
                $username = $phone;
            }
        } else {
            // Email doesn't exist, use it as the username
            $username = $email;
        }
    } else {
        // Neither email nor phone exists, use email
        $username = $email;
    }

    $plain_password = $name . '@123'; 
    $hashed_password = password_hash($plain_password, PASSWORD_DEFAULT);
    $sql = "INSERT INTO `signup` (`username`, `password`) VALUES (?, ?)";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("ss", $username, $hashed_password);

    if ($stmt->execute()) {
        $sql = "INSERT INTO `reg` (`pid`, `username`, `name`, `dob`, `age`, `phone`, `email`, `gender`, `marital`, `blood`, `address`) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $con->prepare($sql);
        $stmt->bind_param("sssssssssss", $patient_id, $username, $name, $dob, $age, $phone, $email, $gender, $marital_status, $blood_group, $address);
        
        if ($stmt->execute()) {
            $_SESSION['patient_id'] = $patient_id;
            $_SESSION['new_username'] = $username;
            $_SESSION['new_password'] = $plain_password; 
            
            echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'success',
                    title: 'Registration Successful!',
                    html: `<p>Patient ID : <strong style='color: #34557a;'>". htmlspecialchars($patient_id) ."</strong>.</p>
            <p>Username : <strong style='color: #34557a;'>". htmlspecialchars($_SESSION['new_username']) ."</strong>.<br>
            Password : <strong style='color: #34557a;'>". htmlspecialchars($_SESSION['new_password']) ."</strong>.</p>
                               <p>Please keep this information safe.</p>`,
                    confirmButtonText: 'OK',
                }).then(() => {
                    window.history.back();
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
}
}
mysqli_close($con);
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="/minipro/css/dreg.css">
    <style>
        .error {
            font-size: 1.2em;
        }
    </style>
</head>
<body>
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
                <input type="tel" id="phone" name="phone" placeholder="Phone Number" required>
                <div class="error" id="phone-error"></div>
        
                <label for="email">Your Email</label>
                <input type="email" id="email" name="email" placeholder="Email">
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

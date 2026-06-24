<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400..700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/minipro/css/contact.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <header>
        <div class="header-container">
            <h1>Roopa's Homeo Clinic</h1>
            <nav>
                <ul>
                    <li><a href='/minipro/home/home.html'>Home</a></li>
                    <li><a href='/minipro/signlog/log.html'>Login</a></li>
                </ul>
            </nav>
        </div>
    </header>
    <h1 class="page-heading">Contact Us</h1>
    <div class="image-section">
        <img src="https://www.nwch.co.uk/wp-content/uploads/2022/12/Flowers-and-Remedy-Bottle-Cover_2560w.jpg" alt="Contact Us Image">
    </div>
    <div class="container">
        <div class="contact-section">
            <form id="contactForm" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="form-group mb-3">
                    <input type="text" id="name" name="cname" class="line-input form-control" placeholder=" " required>
                    <label for="name">Full Name</label>
                </div>
                <div class="form-group mb-3">
                    <input type="text" id="email" name="email" class="line-input form-control" placeholder=" " required>
                    <label for="email">E-mail / Phone</label>
                </div>
                <div class="form-group mb-3">
                    <textarea id="message" name="message" class="line-input form-control" placeholder=" " required></textarea>
                    <label for="message">Message</label>
                </div>
                <button type="submit" class="btn btn-primary">Contact Us</button>
            </form>
        </div>
        <div class="contact-info mt-5">
            <p><b>Contact</b></p>
            <p>contact@homeoclinic.com</p>
            <p><b>Based in</b></p>
            <p>Rajakumari, Idukki</p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Database connection settings
    $server = "localhost";
    $user = "root";
    $pass = "";
    $db = "miniprjct";
    
    // Create connection
    $conn = new mysqli($server, $user, $pass, $db);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $result = $conn->query("SELECT MAX(cid) AS max_cid FROM contacts");
    $row = $result->fetch_assoc();
    $cid = $row['max_cid'] ? $row['max_cid'] + 1 : 1; 
    
    $name = $_POST['cname'];
    $email = $_POST['email'];
    $message = $_POST['message'];

    // Prepare and bind
    $stmt = $conn->prepare("INSERT INTO contacts (cid, cname, email, message, reg_date) VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("isss", $cid, $name, $email, $message);

    // Set parameters and execute
    if ($stmt->execute()) {
        // Return SweetAlert message
        echo "<script>
            Swal.fire({
                title: 'Message Sent Successfully!',
                text: 'Thank you for contacting us. We will get back to you shortly!',
                icon: 'success',
                confirmButtonText: 'Okay',
                allowOutsideClick: false
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '/minipro/home/home.html'; // Redirect to home page
                }
            });
        </script>";
    } else {
        echo "Error: " . $stmt->error;
    }

    // Close statement and connection
    $stmt->close();
    $conn->close();
}
?>

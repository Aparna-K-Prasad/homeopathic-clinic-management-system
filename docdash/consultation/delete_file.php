<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "miniprjct";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$id = $_POST['id'];
$image_path = $_POST['image_path'];

// Delete the record from the database
$sql = "DELETE FROM patient_images WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();

// Delete the file from the server
if (file_exists("../uploads/$image_path")) {
    unlink("../uploads/$image_path");
}

echo 'success';
?>

<?php
session_start();
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "miniprjct";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if(isset($_POST['image_id']) && isset($_POST['pid'])) {
    $image_id = $_POST['image_id'];
    $pid = $_POST['pid'];
    
    // First get the image path
    $sql = "SELECT image_path FROM patient_images WHERE id = ? AND pid = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $image_id, $pid);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($row = $result->fetch_assoc()) {
        $image_path = $row['image_path'];
        
        // Delete from database
        $delete_sql = "DELETE FROM patient_images WHERE id = ? AND pid = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("is", $image_id, $pid);
        
        if($delete_stmt->execute()) {
            // Delete file from uploads folder
            $file_path = "../uploads/" . $image_path;
            if(file_exists($file_path)) {
                unlink($file_path);
            }
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Image not found']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?> 
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

$pid = $_GET['pid'];
$upload_success = false;
$upload_error = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_FILES['files'])) {
        $upload_dir = "../uploads/";
        
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $files = $_FILES['files'];
        $description = $_POST['description'];
        $success_count = 0;
        $error_count = 0;
        
        for ($i = 0; $i < count($files['name']); $i++) {
            $file_name = $files['name'][$i];
            $file_tmp = $files['tmp_name'][$i];
            
            $unique_name = date('YmdHis') . '_' . uniqid() . '_' . $file_name;
            $upload_path = $upload_dir . $unique_name;
            
            if (move_uploaded_file($file_tmp, $upload_path)) {
                $sql = "INSERT INTO patient_images (pid, image_path, description, upload_date) 
                        VALUES (?, ?, ?, CURRENT_DATE)";
                $stmt = $conn->prepare($sql);
                
                if ($stmt === false) {
                    die("Error preparing statement: " . $conn->error);
                }
                
                $stmt->bind_param("sss", $pid, $unique_name, $description);
                
                if ($stmt->execute()) {
                    $success_count++;
                } else {
                    $error_count++;
                }
            } else {
                $error_count++;
            }
        }
        
        if ($success_count > 0) {
            $upload_success = true;
        }
        if ($error_count > 0) {
            $upload_error = true;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Files</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .preview-images {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }
        .preview-images img {
            max-width: 200px;
            max-height: 200px;
            object-fit: cover;
        }
    </style>
</head>
<body>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Upload Patient Files</h2>
        <a href="view_images.php?pid=<?php echo $pid; ?>" class="btn btn-secondary">Back</a>
    </div>

    <form method="POST" enctype="multipart/form-data" id="uploadForm">
        <div class="mb-3">
            <label for="files" class="form-label">Select Files</label>
            <input type="file" class="form-control" id="files" name="files[]" multiple required>
        </div>

        <div class="mb-3">
            <label for="description" class="form-label">Description</label>
            <textarea class="form-control" id="description" name="description" rows="3" placeholder="Enter description for the files" required></textarea>
        </div>

        <div class="preview-images" id="imagePreview"></div>

        <button type="submit" class="btn btn-primary mt-3">Upload Files</button>
    </form>
</div>

<script>
function previewImages(input) {
    const preview = document.getElementById('imagePreview');
    preview.innerHTML = '';

    if (input.files) {
        Array.from(input.files).forEach(file => {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                const img = document.createElement('img');
                img.src = e.target.result;
                img.className = 'img-thumbnail';
                preview.appendChild(img);
            }
            
            reader.readAsDataURL(file);
        });
    }
}

<?php if($upload_success && !$upload_error): ?>
    Swal.fire({
        title: 'Success!',
        text: 'Files uploaded successfully',
        icon: 'success',
        confirmButtonColor: '#3085d6'
    }).then((result) => {
        window.location.href = 'view_images.php?pid=<?php echo $pid; ?>';
    });
<?php elseif($upload_success && $upload_error): ?>
    Swal.fire({
        title: 'Partial Success',
        text: 'Some files were uploaded successfully, but others failed',
        icon: 'warning',
        confirmButtonColor: '#3085d6'
    });
<?php elseif($upload_error): ?>
    Swal.fire({
        title: 'Error!',
        text: 'Failed to upload files',
        icon: 'error',
        confirmButtonColor: '#3085d6'
    });
<?php endif; ?>

document.getElementById('uploadForm').onsubmit = function(e) {
    const fileInput = document.getElementById('files');
    const description = document.getElementById('description');
    
    if (fileInput.files.length === 0) {
        e.preventDefault();
        Swal.fire({
            title: 'Error!',
            text: 'Please select at least one file',
            icon: 'error',
            confirmButtonColor: '#3085d6'
        });
        return false;
    }
    
    if (!description.value.trim()) {
        e.preventDefault();
        Swal.fire({
            title: 'Error!',
            text: 'Please enter a description',
            icon: 'error',
            confirmButtonColor: '#3085d6'
        });
        return false;
    }
    
    return true;
};
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 
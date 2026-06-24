<?php

$server = "localhost";
$user = "root";
$pass = "";
$db = "miniprjct";

$conn = new mysqli($server, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$deleteSuccess = false;
$deleteError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_selected'])) {
    if (isset($_POST['delete_ids'])) {
        $delete_ids = $_POST['delete_ids'];
        $ids = implode(",", array_map('intval', $delete_ids));
        $delete_sql = "DELETE FROM contacts WHERE cid IN ($ids)";
        if ($conn->query($delete_sql) === TRUE) {
            $deleteSuccess = true;
        } else {
            $deleteError = $conn->error;
        }
    }
}

$sql = "SELECT cid, cname, email, message, reg_date FROM contacts WHERE (
            reg_date > CURDATE() 
        ) ORDER BY reg_date ASC";

$result = $conn->query($sql);
if ($result === false) {
    die("SQL Error: " . $conn->error);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400..700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/minipro/css/messages.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .selected td {
            background-color: #e6f3ff !important; /* Light blue background */
        }
        .message-row {
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .message-row:hover td {
            background-color: #f5f5f5;
        }
        .selected:hover td {
            background-color: #d9ecff !important; /* Slightly darker blue on hover */
        }
    </style>
</head>
<body>
    
   <div class="messages-container">
    <div class="header-container">
     <h1>Messages</h1>
    <div class="btn-container">
            <button id="deleteButton" class="btn-delete" title="Delete Selected">
                🗑️ <!-- Unicode for trash bin -->
            </button>
        </div>
    </div>

    <form id="messagesForm" action="" method="POST">
        <input type="hidden" name="delete_selected" value="1"> <!-- Hidden input to indicate deletion -->
        <table>
            <thead>
                <tr>
                    <th>Sl. No.</th>
                    <th>Date</th>
                    <th>Name</th>
                    <th>Email/Phone</th>
                    <th>Message</th>
                   
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $formatted_date = date('d-m-Y', strtotime($row['reg_date']));
                        echo "<tr data-cid='{$row['cid']}' class='message-row'>
                                <td>{$row['cid']}</td>
                                <td>{$formatted_date}</td>
                                <td>{$row['cname']}</td>
                                <td>{$row['email']}</td>
                                <td>{$row['message']}</td>
                              
                              </tr>";
                    }
                } else {
                    echo "<tr><td colspan='5'>No messages found</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </form>
</div>

<script>
    const deleteButton = document.getElementById('deleteButton');
    const messagesForm = document.getElementById('messagesForm');
    const rows = document.querySelectorAll('.message-row');
    const selectedIds = new Set();

    <?php if($deleteSuccess): ?>
        Swal.fire({
            title: 'Success!',
            text: 'Selected messages have been deleted.',
            icon: 'success',
            confirmButtonColor: '#3085d6'
        }).then(() => {
            window.location.href = 'messages.php';
        });
    <?php endif; ?>
    
    <?php if($deleteError): ?>
        Swal.fire({
            title: 'Error!',
            text: 'Error deleting records: <?php echo $deleteError; ?>',
            icon: 'error',
            confirmButtonColor: '#3085d6'
        });
    <?php endif; ?>

    rows.forEach(row => {
        row.addEventListener('click', function() {
            const cid = this.getAttribute('data-cid');
            if (selectedIds.has(cid)) {
                selectedIds.delete(cid);
                this.classList.remove('selected');
            } else {
                selectedIds.add(cid);
                this.classList.add('selected');
            }
        });
    });

    deleteButton.addEventListener('click', function() {
        if (selectedIds.size > 0) {
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to recover these messages!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete them!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    const existingInputs = messagesForm.querySelectorAll('input[name="delete_ids[]"]');
                    existingInputs.forEach(input => input.remove());

                    selectedIds.forEach(id => {
                        const hiddenInput = document.createElement('input');
                        hiddenInput.type = 'hidden';
                        hiddenInput.name = 'delete_ids[]';
                        hiddenInput.value = id;
                        messagesForm.appendChild(hiddenInput);
                    });
                    messagesForm.submit();
                }
            });
        } else {
            Swal.fire({
                title: 'No Selection',
                text: 'Please select messages to delete.',
                icon: 'info',
                confirmButtonColor: '#3085d6'
            });
        }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            selectedIds.clear();
            rows.forEach(row => row.classList.remove('selected'));
        }
    });
</script>

</body>
</html>

<?php
$conn->close();
?>

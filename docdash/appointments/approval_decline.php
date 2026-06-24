<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "miniprjct";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to update appointment request status
function updateRequestStatus($conn, $requestId, $status) {
    $updateSql = "UPDATE appointment_request SET request_status = ? WHERE request_id = ?";
    $stmt = $conn->prepare($updateSql);
    $stmt->bind_param("si", $status, $requestId);
    if (!$stmt->execute()) {
        throw new mysqli_sql_exception("Failed to update request status: " . $stmt->error);
    }
    $stmt->close();
}

// Function to generate a token for appointments
function generateToken($requestedDate, $conn) {
    $tokenSql = "SELECT COUNT(*) AS count FROM appointments WHERE appt_date = ?";
    $tokenStmt = $conn->prepare($tokenSql);
    $tokenStmt->bind_param("s", $requestedDate);
    $tokenStmt->execute();
    $tokenResult = $tokenStmt->get_result();
    $tokenRow = $tokenResult->fetch_assoc();

    $newToken = $tokenRow['count'] + 1;

    if ($newToken > 12) {
        throw new Exception("Maximum token limit reached for this date.");
    }

    return $newToken;
}

// Function to show alert using SweetAlert
function showAlert($icon, $title, $text) {
    echo "
    <!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <title>Alert</title>
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    </head>
    <body>
        <script>
            Swal.fire({
                icon: '$icon',
                title: '$title',
                text: '$text',
                confirmButtonText: 'Go Back'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = document.referrer || 'index.php';
                }
            });
        </script>
    </body>
    </html>";
    exit();
}

// Handle POST requests
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $requestId = $_POST['request_id'] ?? null;
    $action = $_POST['action'] ?? null;

    if (!$requestId || !$action) {
        showAlert('error', 'Error', 'Invalid request.');
    }

    // Approve Appointment Request
    if ($action === 'approve') {
        $fetchSql = "SELECT requested_date, requested_time, pid FROM appointment_request WHERE request_id = ?";
        $fetchStmt = $conn->prepare($fetchSql);
        $fetchStmt->bind_param("i", $requestId);
        $fetchStmt->execute();
        $fetchStmt->bind_result($requestedDate, $requestedTime, $pid);
        $fetchStmt->fetch();
        $fetchStmt->close();

        // Check for past dates and conflicts
        $currentDateTime = date("Y-m-d H:i:s");
        $requestedDateTime = $requestedDate . ' ' . $requestedTime;

        $conflictSql = "SELECT COUNT(*) AS count FROM appointments WHERE appt_date = ? AND appt_time = ? AND status = 'Approved'";
        $conflictStmt = $conn->prepare($conflictSql);
        $conflictStmt->bind_param("ss", $requestedDate, $requestedTime);
        $conflictStmt->execute();
        $conflictRow = $conflictStmt->get_result()->fetch_assoc();

        if ($requestedDateTime < $currentDateTime || $conflictRow['count'] > 0) {
            updateRequestStatus($conn, $requestId, 'Declined');
            $reason = $requestedDateTime < $currentDateTime ? 'Past date/time.' : 'Time slot conflict.';
            showAlert('error', 'Request Declined', "Appointment declined due to $reason");
        }

        try {
            $token = generateToken($requestedDate, $conn);
            $conn->begin_transaction();

            // Insert appointment
            $insertSql = "INSERT INTO appointments (token, pid, appt_date, appt_time, status) VALUES (?, ?, ?, ?, 'Approved')";
            $insertStmt = $conn->prepare($insertSql);
            $insertStmt->bind_param("ssss", $token, $pid, $requestedDate, $requestedTime);
            $insertStmt->execute();

            updateRequestStatus($conn, $requestId, 'Approved');
            $conn->commit();
            
            // Add header before output
            header('Content-Type: text/html');
            
            // Output complete HTML
            echo "<!DOCTYPE html>
            <html>
            <head>
                <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
            </head>
            <body>
                <script>
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: 'Appointment approved successfully.',
                        confirmButtonText: 'Go Back'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = 'request.php';
                        }
                    });
                </script>
            </body>
            </html>";
            exit;

        } catch (Exception $e) {
            $conn->rollback();
            header('Content-Type: text/html');
            echo "<!DOCTYPE html>
            <html>
            <head>
                <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
            </head>
            <body>
                <script>
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to approve appointment.',
                        confirmButtonText: 'Go Back'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = 'request.php';
                        }
                    });
                </script>
            </body>
            </html>";
            exit;
        }
    }

    // Reschedule Appointment
    if ($action === 'reschedule') {
        try {
            if (!isset($_POST['request_id']) || !isset($_POST['re_date']) || !isset($_POST['re_time'])) {
                throw new Exception("Missing required data");
            }

            $requestId = $_POST['request_id'];
            $newDate = $_POST['re_date'];
            $newTime = $_POST['re_time'];

            // 1. Fetch original appointment details
            $fetchSql = "SELECT requested_date, requested_time, pid FROM appointment_request WHERE request_id = ?";
            $fetchStmt = $conn->prepare($fetchSql);
            $fetchStmt->bind_param("i", $requestId);
            $fetchStmt->execute();
            $result = $fetchStmt->get_result();
            $row = $result->fetch_assoc();
            
            if (!$row) {
                throw new Exception("Original appointment not found");
            }

            $originalDate = $row['requested_date'];
            $originalTime = $row['requested_time'];
            $pid = $row['pid'];
            $fetchStmt->close();

            $conn->begin_transaction();

            // 2. Insert into reschedule table FIRST
            $insertRescheduleSql = "INSERT INTO reschedule (request_id, requested_date, requested_time, new_date, new_time) 
                                  VALUES (?, ?, ?, ?, ?)";
            $rescheduleStmt = $conn->prepare($insertRescheduleSql);
            $rescheduleStmt->bind_param("issss", $requestId, $originalDate, $originalTime, $newDate, $newTime);
            $rescheduleStmt->execute();
            $rescheduleStmt->close();

            // 3. Generate token and insert new appointment
            $token = generateToken($newDate, $conn);
            $insertAppointmentSql = "INSERT INTO appointments (token, pid, appt_date, appt_time, status) 
                                   VALUES (?, ?, ?, ?, 'Approved')";
            $appointmentStmt = $conn->prepare($insertAppointmentSql);
            $appointmentStmt->bind_param("isss", $token, $pid, $newDate, $newTime);
            $appointmentStmt->execute();
            $appointmentStmt->close();

            // 4. Update the status in appointment_request instead of deleting
            $updateSql = "UPDATE appointment_request SET request_status = 'Rescheduled' WHERE request_id = ?";
            $updateStmt = $conn->prepare($updateSql);
            $updateStmt->bind_param("i", $requestId);
            $updateStmt->execute();
            $updateStmt->close();

            $conn->commit();
            
            // Clear any output buffers
            ob_clean();
            
            // Set proper content type
            header('Content-Type: text/html; charset=utf-8');
            
            // Output the HTML
            echo "<!DOCTYPE html>
            <html>
            <head>
                <meta charset='UTF-8'>
                <title>Success</title>
                <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
            </head>
            <body>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: 'Appointment rescheduled successfully.',
                            confirmButtonText: 'Go Back'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location.href = 'request.php';
                            }
                        });
                    });
                </script>
            </body>
            </html>";
            exit;

        } catch (Exception $e) {
            $conn->rollback();
            
            ob_clean();
            header('Content-Type: text/html; charset=utf-8');
            
            echo "<!DOCTYPE html>
            <html>
            <head>
                <meta charset='UTF-8'>
                <title>Error</title>
                <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
            </head>
            <body>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Already have an appointment for this date.',
                            confirmButtonText: 'Go Back'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location.href = 'request.php';
                            }
                        });
                    });
                </script>
            </body>
            </html>";
            exit;
        }
    }

    // Decline Appointment Request
    if ($action === 'decline') {
        updateRequestStatus($conn, $requestId, 'Declined');
        $conn->commit();
            
        // Add header before output
        header('Content-Type: text/html');
        
        // Output complete HTML
        echo "<!DOCTYPE html>
        <html>
        <head>
            <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        </head>
        <body>
            <script>
                Swal.fire({
                    icon: 'info',
                    title: 'Declined',
                    text: 'Appointment request has been declined.',
                    confirmButtonText: 'Go Back'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = 'request.php';
                    }
                });
            </script>
        </body>
        </html>";
        exit;

    }
}

$conn->close();
?> 
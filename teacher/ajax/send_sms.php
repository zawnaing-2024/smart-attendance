<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Check if user is logged in and is a teacher
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'teacher') {
    http_response_code(403);
    exit('Unauthorized');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = $_POST['phone'];
    $message = $_POST['message'];
    
    if (send_sms($phone, $message)) {
        echo json_encode(['success' => true, 'message' => 'SMS sent successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to send SMS']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>

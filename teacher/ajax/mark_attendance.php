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
    $student_id = (int)$_POST['student_id'];
    $status = $_POST['status'];
    $date = $_POST['date'];
    
    try {
        // Check if attendance record exists
        $stmt = $pdo->prepare("SELECT id FROM attendance WHERE student_id = ? AND attendance_date = ?");
        $stmt->execute([$student_id, $date]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            // Update existing record
            $stmt = $pdo->prepare("UPDATE attendance SET status = ?, check_in_time = ? WHERE student_id = ? AND attendance_date = ?");
            $stmt->execute([$status, date('H:i:s'), $student_id, $date]);
        } else {
            // Create new record
            $stmt = $pdo->prepare("INSERT INTO attendance (student_id, attendance_date, check_in_time, status) VALUES (?, ?, ?, ?)");
            $stmt->execute([$student_id, $date, date('H:i:s'), $status]);
        }
        
        echo json_encode(['success' => true, 'message' => 'Attendance updated successfully']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>

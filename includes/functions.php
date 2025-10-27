<?php
// Common functions for the Smart Attendance System

function sanitize_input($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function generate_roll_number($grade_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM students WHERE grade_id = ?");
    $stmt->execute([$grade_id]);
    $result = $stmt->fetch();
    $count = $result['count'] + 1;
    return sprintf("GR%02d%03d", $grade_id, $count);
}

function send_sms($phone_number, $message) {
    global $pdo;
    
    // Get SMS settings
    $stmt = $pdo->prepare("SELECT * FROM sms_settings WHERE is_active = 1 LIMIT 1");
    $stmt->execute();
    $sms_settings = $stmt->fetch();
    
    if (!$sms_settings) {
        return false;
    }
    
    // Here you would integrate with your SMS provider API
    // For now, we'll just log the SMS
    $stmt = $pdo->prepare("INSERT INTO sms_logs (phone_number, message, sms_type, status) VALUES (?, ?, ?, ?)");
    $stmt->execute([$phone_number, $message, 'check_in', 'sent']);
    
    return true;
}

function log_attendance($student_id, $camera_id, $type = 'check_in') {
    global $pdo;
    
    $today = date('Y-m-d');
    $current_time = date('H:i:s');
    
    // Check if attendance record exists for today
    $stmt = $pdo->prepare("SELECT * FROM attendance WHERE student_id = ? AND attendance_date = ?");
    $stmt->execute([$student_id, $today]);
    $attendance = $stmt->fetch();
    
    if ($attendance) {
        // Update existing record
        if ($type === 'check_out') {
            $stmt = $pdo->prepare("UPDATE attendance SET check_out_time = ?, camera_id = ? WHERE student_id = ? AND attendance_date = ?");
            $stmt->execute([$current_time, $camera_id, $student_id, $today]);
        }
    } else {
        // Create new record
        $stmt = $pdo->prepare("INSERT INTO attendance (student_id, camera_id, attendance_date, check_in_time, status) VALUES (?, ?, ?, ?, 'present')");
        $stmt->execute([$student_id, $camera_id, $today, $current_time]);
    }
    
    // Send SMS notification
    $stmt = $pdo->prepare("SELECT parent_phone1, parent_phone2, student_name FROM students WHERE id = ?");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch();
    
    if ($student) {
        $message = $type === 'check_in' 
            ? "Your child {$student['student_name']} has arrived at school at {$current_time}"
            : "Your child {$student['student_name']} has left school at {$current_time}";
            
        send_sms($student['parent_phone1'], $message);
        
        if ($student['parent_phone2']) {
            send_sms($student['parent_phone2'], $message);
        }
    }
}

function get_attendance_stats($student_id = null, $grade_id = null, $date = null) {
    global $pdo;
    
    $where_conditions = [];
    $params = [];
    
    if ($student_id) {
        $where_conditions[] = "a.student_id = ?";
        $params[] = $student_id;
    }
    
    if ($grade_id) {
        $where_conditions[] = "s.grade_id = ?";
        $params[] = $grade_id;
    }
    
    if ($date) {
        $where_conditions[] = "a.attendance_date = ?";
        $params[] = $date;
    } else {
        $where_conditions[] = "a.attendance_date = CURDATE()";
    }
    
    $where_clause = $where_conditions ? "WHERE " . implode(" AND ", $where_conditions) : "";
    
    $sql = "SELECT 
                COUNT(*) as total_students,
                SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present_count,
                SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as absent_count,
                SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) as late_count
            FROM students s
            LEFT JOIN attendance a ON s.id = a.student_id AND a.attendance_date = " . ($date ? "?" : "CURDATE()") . "
            " . ($grade_id ? "WHERE s.grade_id = ?" : "");
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    return $stmt->fetch();
}

function upload_face_image($file, $student_id) {
    $upload_dir = "uploads/faces/";
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed_extensions = ['jpg', 'jpeg', 'png'];
    
    if (!in_array($file_extension, $allowed_extensions)) {
        return false;
    }
    
    $filename = "student_{$student_id}_" . time() . ".{$file_extension}";
    $filepath = $upload_dir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return $filepath;
    }
    
    return false;
}

function check_permission($required_role) {
    if (!isset($_SESSION['user_role'])) {
        header('Location: ../login.php');
        exit();
    }
    
    if ($required_role === 'admin' && $_SESSION['user_role'] !== 'admin') {
        header('Location: ../index.php');
        exit();
    }
}
?>

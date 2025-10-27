<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in and is a teacher
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'teacher') {
    header('Location: ../login.php');
    exit();
}

$page_title = "Teacher Dashboard";
include 'includes/header.php';

// Get teacher information
$stmt = $pdo->prepare("
    SELECT t.*, g.grade_name 
    FROM teachers t 
    LEFT JOIN grades g ON t.grade_id = g.id 
    WHERE t.user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$teacher = $stmt->fetch();

if (!$teacher) {
    header('Location: ../login.php');
    exit();
}

// Get teacher's students
$students = $pdo->prepare("
    SELECT s.*, g.grade_name 
    FROM students s 
    LEFT JOIN grades g ON s.grade_id = g.id 
    WHERE s.grade_id = ? AND s.is_active = 1
    ORDER BY s.roll_number
");
$students->execute([$teacher['grade_id']]);

// Get today's attendance for teacher's grade
$today_stats = get_attendance_stats(null, $teacher['grade_id']);

// Get recent attendance
$recent_attendance = $pdo->prepare("
    SELECT a.*, s.student_name, s.roll_number 
    FROM attendance a 
    JOIN students s ON a.student_id = s.id 
    WHERE s.grade_id = ? AND a.attendance_date = CURDATE() 
    ORDER BY a.check_in_time DESC 
    LIMIT 10
");
$recent_attendance->execute([$teacher['grade_id']]);
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h1 class="h3 mb-4">Teacher Dashboard</h1>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                Welcome, <?php echo htmlspecialchars($teacher['teacher_name']); ?>! 
                You are teaching <?php echo htmlspecialchars($teacher['grade_name'] ?? 'No Grade Assigned'); ?>.
            </div>
        </div>
    </div>
    
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Students</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $today_stats['total_students']; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Present Today</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $today_stats['present_count']; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Absent Today</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $today_stats['absent_count']; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-times-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Attendance %</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php 
                                $percentage = $today_stats['total_students'] > 0 ? 
                                    round(($today_stats['present_count'] / $today_stats['total_students']) * 100, 1) : 0;
                                echo $percentage . '%';
                                ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-percentage fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Quick Actions</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <a href="students.php" class="btn btn-primary btn-block">
                                <i class="fas fa-users me-2"></i>My Students
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="attendance.php" class="btn btn-success btn-block">
                                <i class="fas fa-calendar-check me-2"></i>Attendance List
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="reports.php" class="btn btn-info btn-block">
                                <i class="fas fa-chart-bar me-2"></i>Reports
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="../live/live.php" class="btn btn-warning btn-block">
                                <i class="fas fa-eye me-2"></i>Live Portal
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Attendance -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Today's Attendance</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="attendanceTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Student Name</th>
                                    <th>Roll Number</th>
                                    <th>Check In</th>
                                    <th>Check Out</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_attendance as $attendance): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($attendance['student_name']); ?></td>
                                    <td><?php echo htmlspecialchars($attendance['roll_number']); ?></td>
                                    <td><?php echo $attendance['check_in_time'] ? date('h:i A', strtotime($attendance['check_in_time'])) : '-'; ?></td>
                                    <td><?php echo $attendance['check_out_time'] ? date('h:i A', strtotime($attendance['check_out_time'])) : '-'; ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $attendance['status'] === 'present' ? 'success' : ($attendance['status'] === 'late' ? 'warning' : 'danger'); ?>">
                                            <?php echo ucfirst($attendance['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Student List</h6>
                </div>
                <div class="card-body">
                    <div style="max-height: 400px; overflow-y: auto;">
                        <?php foreach ($students as $student): ?>
                            <div class="d-flex justify-content-between align-items-center mb-2 p-2 bg-light rounded">
                                <div>
                                    <div class="fw-bold"><?php echo htmlspecialchars($student['student_name']); ?></div>
                                    <small class="text-muted"><?php echo htmlspecialchars($student['roll_number']); ?></small>
                                </div>
                                <div>
                                    <?php if ($student['face_image_path']): ?>
                                        <img src="../<?php echo $student['face_image_path']; ?>" 
                                             alt="Face" class="img-thumbnail" style="width: 40px; height: 40px;">
                                    <?php else: ?>
                                        <i class="fas fa-user-circle fa-2x text-muted"></i>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

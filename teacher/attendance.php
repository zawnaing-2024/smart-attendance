<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in and is a teacher
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'teacher') {
    header('Location: ../login.php');
    exit();
}

$page_title = "Attendance Management";
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

// Handle date filter
$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$date_filter = $selected_date;

// Get attendance for selected date
$attendance = $pdo->prepare("
    SELECT a.*, s.student_name, s.roll_number, s.parent_phone1, s.parent_phone2
    FROM attendance a 
    JOIN students s ON a.student_id = s.id 
    WHERE s.grade_id = ? AND a.attendance_date = ?
    ORDER BY a.check_in_time ASC
");
$attendance->execute([$teacher['grade_id'], $date_filter]);

// Get all students for the grade (to show absent students)
$all_students = $pdo->prepare("
    SELECT s.*, a.check_in_time, a.check_out_time, a.status
    FROM students s 
    LEFT JOIN attendance a ON s.id = a.student_id AND a.attendance_date = ?
    WHERE s.grade_id = ? AND s.is_active = 1
    ORDER BY s.roll_number
");
$all_students->execute([$date_filter, $teacher['grade_id']]);

// Get attendance statistics for the selected date
$stats = get_attendance_stats(null, $teacher['grade_id'], $date_filter);
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Attendance Management - <?php echo htmlspecialchars($teacher['grade_name']); ?></h1>
                <div class="d-flex gap-2">
                    <input type="date" class="form-control" id="dateFilter" value="<?php echo $date_filter; ?>" 
                           onchange="filterByDate()" style="width: auto;">
                    <button class="btn btn-primary" onclick="exportAttendance()">
                        <i class="fas fa-download me-2"></i>Export
                    </button>
                </div>
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
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['total_students']; ?></div>
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
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Present</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['present_count']; ?></div>
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
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Absent</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['absent_count']; ?></div>
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
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Late</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['late_count']; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Attendance List -->
    <div class="card shadow">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                Attendance for <?php echo date('F d, Y', strtotime($date_filter)); ?>
            </h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="attendanceTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Roll Number</th>
                            <th>Student Name</th>
                            <th>Check In Time</th>
                            <th>Check Out Time</th>
                            <th>Status</th>
                            <th>Parent Contact</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_students as $student): ?>
                        <tr class="<?php echo $student['status'] === 'absent' || !$student['check_in_time'] ? 'table-danger' : ''; ?>">
                            <td><?php echo htmlspecialchars($student['roll_number']); ?></td>
                            <td><?php echo htmlspecialchars($student['student_name']); ?></td>
                            <td>
                                <?php if ($student['check_in_time']): ?>
                                    <?php echo date('h:i A', strtotime($student['check_in_time'])); ?>
                                <?php else: ?>
                                    <span class="text-danger">Absent</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($student['check_out_time']): ?>
                                    <?php echo date('h:i A', strtotime($student['check_out_time'])); ?>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($student['status']): ?>
                                    <span class="badge badge-<?php echo $student['status'] === 'present' ? 'success' : ($student['status'] === 'late' ? 'warning' : 'danger'); ?>">
                                        <?php echo ucfirst($student['status']); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Absent</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <button class="btn btn-sm btn-outline-primary" onclick="callParent('<?php echo $student['parent_phone1']; ?>')">
                                        <i class="fas fa-phone"></i> Call
                                    </button>
                                    <button class="btn btn-sm btn-outline-success" onclick="sendSMS('<?php echo $student['parent_phone1']; ?>', '<?php echo $student['student_name']; ?>')">
                                        <i class="fas fa-sms"></i> SMS
                                    </button>
                                </div>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <button class="btn btn-sm btn-outline-info" onclick="markPresent(<?php echo $student['id']; ?>)">
                                        <i class="fas fa-check"></i> Present
                                    </button>
                                    <button class="btn btn-sm btn-outline-warning" onclick="markLate(<?php echo $student['id']; ?>)">
                                        <i class="fas fa-clock"></i> Late
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card shadow">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Quick Actions</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <button class="btn btn-success btn-block" onclick="markAllPresent()">
                            <i class="fas fa-check-double me-2"></i>Mark All Present
                        </button>
                    </div>
                    <div class="col-md-3 mb-3">
                        <button class="btn btn-warning btn-block" onclick="markAllLate()">
                            <i class="fas fa-clock me-2"></i>Mark All Late
                        </button>
                    </div>
                    <div class="col-md-3 mb-3">
                        <button class="btn btn-info btn-block" onclick="sendBulkSMS()">
                            <i class="fas fa-sms me-2"></i>Send Bulk SMS
                        </button>
                    </div>
                    <div class="col-md-3 mb-3">
                        <button class="btn btn-primary btn-block" onclick="generateReport()">
                            <i class="fas fa-file-alt me-2"></i>Generate Report
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function filterByDate() {
    const date = document.getElementById('dateFilter').value;
    window.location.href = `?date=${date}`;
}

function markPresent(studentId) {
    if (confirm('Mark this student as present?')) {
        fetch('ajax/mark_attendance.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `student_id=${studentId}&status=present&date=<?php echo $date_filter; ?>`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        });
    }
}

function markLate(studentId) {
    if (confirm('Mark this student as late?')) {
        fetch('ajax/mark_attendance.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `student_id=${studentId}&status=late&date=<?php echo $date_filter; ?>`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        });
    }
}

function callParent(phoneNumber) {
    window.open(`tel:${phoneNumber}`);
}

function sendSMS(phoneNumber, studentName) {
    const message = prompt(`Enter SMS message for ${studentName}'s parent:`);
    if (message) {
        fetch('ajax/send_sms.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `phone=${phoneNumber}&message=${encodeURIComponent(message)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('SMS sent successfully!');
            } else {
                alert('Error: ' + data.message);
            }
        });
    }
}

function markAllPresent() {
    if (confirm('Mark all students as present?')) {
        // Implementation for marking all students present
        alert('Feature coming soon!');
    }
}

function markAllLate() {
    if (confirm('Mark all students as late?')) {
        // Implementation for marking all students late
        alert('Feature coming soon!');
    }
}

function sendBulkSMS() {
    // Implementation for bulk SMS
    alert('Feature coming soon!');
}

function generateReport() {
    // Implementation for generating attendance report
    window.open(`ajax/generate_attendance_report.php?date=<?php echo $date_filter; ?>`, '_blank');
}

function exportAttendance() {
    window.open(`ajax/export_attendance.php?date=<?php echo $date_filter; ?>`, '_blank');
}
</script>

<?php include 'includes/footer.php'; ?>

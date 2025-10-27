<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in and is a teacher
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'teacher') {
    header('Location: ../login.php');
    exit();
}

$page_title = "My Students";
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
    SELECT s.*, g.grade_name,
           (SELECT COUNT(*) FROM attendance a WHERE a.student_id = s.id AND a.attendance_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)) as attendance_count,
           (SELECT COUNT(*) FROM attendance a WHERE a.student_id = s.id AND a.status = 'present' AND a.attendance_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)) as present_count
    FROM students s 
    LEFT JOIN grades g ON s.grade_id = g.id 
    WHERE s.grade_id = ? AND s.is_active = 1
    ORDER BY s.roll_number
");
$students->execute([$teacher['grade_id']]);
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">My Students - <?php echo htmlspecialchars($teacher['grade_name']); ?></h1>
                <div class="btn-group">
                    <button class="btn btn-primary" onclick="exportStudents()">
                        <i class="fas fa-download me-2"></i>Export List
                    </button>
                    <button class="btn btn-success" onclick="printStudents()">
                        <i class="fas fa-print me-2"></i>Print
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="studentsTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Photo</th>
                            <th>Roll Number</th>
                            <th>Student Name</th>
                            <th>Grade</th>
                            <th>Parent Phone 1</th>
                            <th>Parent Phone 2</th>
                            <th>30-Day Attendance</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                        <tr>
                            <td>
                                <?php if ($student['face_image_path']): ?>
                                    <img src="../<?php echo $student['face_image_path']; ?>" 
                                         alt="Face" class="img-thumbnail" style="width: 50px; height: 50px;">
                                <?php else: ?>
                                    <i class="fas fa-user-circle fa-3x text-muted"></i>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($student['roll_number']); ?></td>
                            <td><?php echo htmlspecialchars($student['student_name']); ?></td>
                            <td><?php echo htmlspecialchars($student['grade_name']); ?></td>
                            <td><?php echo htmlspecialchars($student['parent_phone1']); ?></td>
                            <td><?php echo htmlspecialchars($student['parent_phone2'] ?? 'N/A'); ?></td>
                            <td>
                                <?php 
                                $attendance_percentage = $student['attendance_count'] > 0 ? 
                                    round(($student['present_count'] / $student['attendance_count']) * 100, 1) : 0;
                                ?>
                                <div class="progress" style="height: 20px;">
                                    <div class="progress-bar <?php echo $attendance_percentage >= 80 ? 'bg-success' : ($attendance_percentage >= 60 ? 'bg-warning' : 'bg-danger'); ?>" 
                                         style="width: <?php echo $attendance_percentage; ?>%">
                                        <?php echo $attendance_percentage; ?>%
                                    </div>
                                </div>
                                <small class="text-muted">
                                    <?php echo $student['present_count']; ?>/<?php echo $student['attendance_count']; ?> days
                                </small>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <button class="btn btn-sm btn-outline-primary" onclick="viewStudentDetails(<?php echo $student['id']; ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-info" onclick="viewAttendanceHistory(<?php echo $student['id']; ?>)">
                                        <i class="fas fa-history"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-success" onclick="contactParent(<?php echo $student['id']; ?>)">
                                        <i class="fas fa-phone"></i>
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

<!-- Student Details Modal -->
<div class="modal fade" id="studentDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Student Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="studentDetailsContent">
                <!-- Content will be loaded via AJAX -->
            </div>
        </div>
    </div>
</div>

<!-- Attendance History Modal -->
<div class="modal fade" id="attendanceHistoryModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Attendance History</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="attendanceHistoryContent">
                <!-- Content will be loaded via AJAX -->
            </div>
        </div>
    </div>
</div>

<!-- Contact Parent Modal -->
<div class="modal fade" id="contactParentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Contact Parent</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="contactParentContent">
                <!-- Content will be loaded via AJAX -->
            </div>
        </div>
    </div>
</div>

<script>
function viewStudentDetails(studentId) {
    // Load student details via AJAX
    fetch(`ajax/get_student_details.php?id=${studentId}`)
        .then(response => response.text())
        .then(data => {
            document.getElementById('studentDetailsContent').innerHTML = data;
            new bootstrap.Modal(document.getElementById('studentDetailsModal')).show();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading student details');
        });
}

function viewAttendanceHistory(studentId) {
    // Load attendance history via AJAX
    fetch(`ajax/get_attendance_history.php?id=${studentId}`)
        .then(response => response.text())
        .then(data => {
            document.getElementById('attendanceHistoryContent').innerHTML = data;
            new bootstrap.Modal(document.getElementById('attendanceHistoryModal')).show();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading attendance history');
        });
}

function contactParent(studentId) {
    // Load parent contact info via AJAX
    fetch(`ajax/get_parent_contact.php?id=${studentId}`)
        .then(response => response.text())
        .then(data => {
            document.getElementById('contactParentContent').innerHTML = data;
            new bootstrap.Modal(document.getElementById('contactParentModal')).show();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading parent contact information');
        });
}

function exportStudents() {
    // Export students list to CSV
    window.open('ajax/export_students.php', '_blank');
}

function printStudents() {
    // Print students list
    window.print();
}
</script>

<?php include 'includes/footer.php'; ?>

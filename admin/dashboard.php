<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

check_permission('admin');

$page_title = "Admin Dashboard";
include 'includes/header.php';

// Get statistics
$stats = [
    'total_students' => $pdo->query("SELECT COUNT(*) as count FROM students WHERE is_active = 1")->fetch()['count'],
    'total_teachers' => $pdo->query("SELECT COUNT(*) as count FROM teachers")->fetch()['count'],
    'total_grades' => $pdo->query("SELECT COUNT(*) as count FROM grades")->fetch()['count'],
    'total_cameras' => $pdo->query("SELECT COUNT(*) as count FROM cameras WHERE is_active = 1")->fetch()['count'],
    'today_attendance' => get_attendance_stats(),
    'recent_attendance' => $pdo->query("SELECT a.*, s.student_name, s.roll_number FROM attendance a JOIN students s ON a.student_id = s.id ORDER BY a.created_at DESC LIMIT 10")->fetchAll()
];
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h1 class="h3 mb-4">Admin Dashboard</h1>
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
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Teachers</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['total_teachers']; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chalkboard-teacher fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Active Cameras</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['total_cameras']; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-video fa-2x text-gray-300"></i>
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
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Today's Attendance</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo $stats['today_attendance']['present_count']; ?>/<?php echo $stats['today_attendance']['total_students']; ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar-check fa-2x text-gray-300"></i>
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
                            <a href="cameras.php" class="btn btn-primary btn-block">
                                <i class="fas fa-video me-2"></i>Manage Cameras
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="students.php" class="btn btn-success btn-block">
                                <i class="fas fa-users me-2"></i>Manage Students
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="teachers.php" class="btn btn-info btn-block">
                                <i class="fas fa-chalkboard-teacher me-2"></i>Manage Teachers
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="grades.php" class="btn btn-warning btn-block">
                                <i class="fas fa-layer-group me-2"></i>Manage Grades
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Attendance -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Recent Attendance</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Student Name</th>
                                    <th>Roll Number</th>
                                    <th>Date</th>
                                    <th>Check In</th>
                                    <th>Check Out</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($stats['recent_attendance'] as $attendance): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($attendance['student_name']); ?></td>
                                    <td><?php echo htmlspecialchars($attendance['roll_number']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($attendance['attendance_date'])); ?></td>
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
    </div>
</div>

<?php include 'includes/footer.php'; ?>

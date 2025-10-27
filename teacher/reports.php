<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in and is a teacher
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'teacher') {
    header('Location: ../login.php');
    exit();
}

$page_title = "Reports & Analytics";
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

// Handle date range filter
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Get attendance statistics for the date range
$stats = $pdo->prepare("
    SELECT 
        COUNT(DISTINCT s.id) as total_students,
        COUNT(DISTINCT a.student_id) as students_with_attendance,
        SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present_count,
        SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as absent_count,
        SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) as late_count,
        COUNT(DISTINCT a.attendance_date) as total_days
    FROM students s
    LEFT JOIN attendance a ON s.id = a.student_id 
        AND a.attendance_date BETWEEN ? AND ?
    WHERE s.grade_id = ? AND s.is_active = 1
");
$stats->execute([$start_date, $end_date, $teacher['grade_id']]);
$overall_stats = $stats->fetch();

// Get daily attendance trend
$daily_trend = $pdo->prepare("
    SELECT 
        a.attendance_date,
        COUNT(DISTINCT s.id) as total_students,
        SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present_count,
        SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) as late_count,
        SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as absent_count
    FROM students s
    LEFT JOIN attendance a ON s.id = a.student_id 
        AND a.attendance_date BETWEEN ? AND ?
    WHERE s.grade_id = ? AND s.is_active = 1
    GROUP BY a.attendance_date
    ORDER BY a.attendance_date ASC
");
$daily_trend->execute([$start_date, $end_date, $teacher['grade_id']]);

// Get student-wise attendance
$student_attendance = $pdo->prepare("
    SELECT 
        s.id,
        s.student_name,
        s.roll_number,
        COUNT(a.id) as total_days,
        SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present_days,
        SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) as late_days,
        SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as absent_days,
        ROUND(
            (SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) / COUNT(a.id)) * 100, 1
        ) as attendance_percentage
    FROM students s
    LEFT JOIN attendance a ON s.id = a.student_id 
        AND a.attendance_date BETWEEN ? AND ?
    WHERE s.grade_id = ? AND s.is_active = 1
    GROUP BY s.id, s.student_name, s.roll_number
    ORDER BY attendance_percentage DESC
");
$student_attendance->execute([$start_date, $end_date, $teacher['grade_id']]);

// Get monthly attendance summary
$monthly_summary = $pdo->prepare("
    SELECT 
        DATE_FORMAT(a.attendance_date, '%Y-%m') as month,
        COUNT(DISTINCT s.id) as total_students,
        SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present_count,
        SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) as late_count,
        SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as absent_count,
        ROUND(
            (SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) / COUNT(a.id)) * 100, 1
        ) as attendance_percentage
    FROM students s
    LEFT JOIN attendance a ON s.id = a.student_id 
        AND a.attendance_date BETWEEN ? AND ?
    WHERE s.grade_id = ? AND s.is_active = 1
    GROUP BY DATE_FORMAT(a.attendance_date, '%Y-%m')
    ORDER BY month ASC
");
$monthly_summary->execute([$start_date, $end_date, $teacher['grade_id']]);
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Reports & Analytics - <?php echo htmlspecialchars($teacher['grade_name']); ?></h1>
                <div class="d-flex gap-2">
                    <input type="date" class="form-control" id="startDate" value="<?php echo $start_date; ?>" style="width: auto;">
                    <input type="date" class="form-control" id="endDate" value="<?php echo $end_date; ?>" style="width: auto;">
                    <button class="btn btn-primary" onclick="filterReports()">
                        <i class="fas fa-filter me-2"></i>Filter
                    </button>
                    <button class="btn btn-success" onclick="exportReport()">
                        <i class="fas fa-download me-2"></i>Export
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Students</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $overall_stats['total_students']; ?></div>
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
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Present Days</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $overall_stats['present_count']; ?></div>
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
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Absent Days</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $overall_stats['absent_count']; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-times-circle fa-2x text-gray-300"></i>
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
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Days</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $overall_stats['total_days']; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Charts Row -->
    <div class="row mb-4">
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Daily Attendance Trend</h6>
                </div>
                <div class="card-body">
                    <canvas id="dailyTrendChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Attendance Distribution</h6>
                </div>
                <div class="card-body">
                    <canvas id="attendancePieChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Student-wise Attendance -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Student-wise Attendance Report</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="reportsTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Roll Number</th>
                                    <th>Student Name</th>
                                    <th>Total Days</th>
                                    <th>Present</th>
                                    <th>Late</th>
                                    <th>Absent</th>
                                    <th>Attendance %</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($student_attendance as $student): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($student['roll_number']); ?></td>
                                    <td><?php echo htmlspecialchars($student['student_name']); ?></td>
                                    <td><?php echo $student['total_days']; ?></td>
                                    <td><?php echo $student['present_days']; ?></td>
                                    <td><?php echo $student['late_days']; ?></td>
                                    <td><?php echo $student['absent_days']; ?></td>
                                    <td>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar <?php echo $student['attendance_percentage'] >= 80 ? 'bg-success' : ($student['attendance_percentage'] >= 60 ? 'bg-warning' : 'bg-danger'); ?>" 
                                                 style="width: <?php echo $student['attendance_percentage']; ?>%">
                                                <?php echo $student['attendance_percentage']; ?>%
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($student['attendance_percentage'] >= 80): ?>
                                            <span class="badge badge-success">Excellent</span>
                                        <?php elseif ($student['attendance_percentage'] >= 60): ?>
                                            <span class="badge badge-warning">Good</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">Poor</span>
                                        <?php endif; ?>
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

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// Daily Trend Chart
const dailyTrendCtx = document.getElementById('dailyTrendChart').getContext('2d');
const dailyTrendData = {
    labels: [
        <?php foreach ($daily_trend as $day): ?>
            '<?php echo date('M d', strtotime($day['attendance_date'])); ?>',
        <?php endforeach; ?>
    ],
    datasets: [{
        label: 'Present',
        data: [
            <?php foreach ($daily_trend as $day): ?>
                <?php echo $day['present_count']; ?>,
            <?php endforeach; ?>
        ],
        borderColor: 'rgb(75, 192, 192)',
        backgroundColor: 'rgba(75, 192, 192, 0.2)',
        tension: 0.1
    }, {
        label: 'Late',
        data: [
            <?php foreach ($daily_trend as $day): ?>
                <?php echo $day['late_count']; ?>,
            <?php endforeach; ?>
        ],
        borderColor: 'rgb(255, 205, 86)',
        backgroundColor: 'rgba(255, 205, 86, 0.2)',
        tension: 0.1
    }, {
        label: 'Absent',
        data: [
            <?php foreach ($daily_trend as $day): ?>
                <?php echo $day['absent_count']; ?>,
            <?php endforeach; ?>
        ],
        borderColor: 'rgb(255, 99, 132)',
        backgroundColor: 'rgba(255, 99, 132, 0.2)',
        tension: 0.1
    }]
};

new Chart(dailyTrendCtx, {
    type: 'line',
    data: dailyTrendData,
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// Attendance Pie Chart
const pieCtx = document.getElementById('attendancePieChart').getContext('2d');
const pieData = {
    labels: ['Present', 'Late', 'Absent'],
    datasets: [{
        data: [
            <?php echo $overall_stats['present_count']; ?>,
            <?php echo $overall_stats['late_count']; ?>,
            <?php echo $overall_stats['absent_count']; ?>
        ],
        backgroundColor: [
            'rgba(75, 192, 192, 0.8)',
            'rgba(255, 205, 86, 0.8)',
            'rgba(255, 99, 132, 0.8)'
        ],
        borderColor: [
            'rgb(75, 192, 192)',
            'rgb(255, 205, 86)',
            'rgb(255, 99, 132)'
        ],
        borderWidth: 1
    }]
};

new Chart(pieCtx, {
    type: 'pie',
    data: pieData,
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom',
            }
        }
    }
});

function filterReports() {
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;
    window.location.href = `?start_date=${startDate}&end_date=${endDate}`;
}

function exportReport() {
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;
    window.open(`ajax/export_reports.php?start_date=${startDate}&end_date=${endDate}`, '_blank');
}
</script>

<?php include 'includes/footer.php'; ?>

<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_role = $_SESSION['user_role'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Attendance System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-graduation-cap me-2"></i>Smart Attendance
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <?php if ($user_role === 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="admin/dashboard.php">
                                <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin/cameras.php">
                                <i class="fas fa-video me-1"></i>Cameras
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin/students.php">
                                <i class="fas fa-users me-1"></i>Students
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin/grades.php">
                                <i class="fas fa-layer-group me-1"></i>Grades
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin/teachers.php">
                                <i class="fas fa-chalkboard-teacher me-1"></i>Teachers
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin/sms.php">
                                <i class="fas fa-sms me-1"></i>SMS Settings
                            </a>
                        </li>
                    <?php elseif ($user_role === 'teacher'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="teacher/dashboard.php">
                                <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="teacher/students.php">
                                <i class="fas fa-users me-1"></i>My Students
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="teacher/attendance.php">
                                <i class="fas fa-calendar-check me-1"></i>Attendance
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="teacher/reports.php">
                                <i class="fas fa-chart-bar me-1"></i>Reports
                            </a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="live/live.php">
                            <i class="fas fa-eye me-1"></i>Live Portal
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($_SESSION['username']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-home me-2"></i>Welcome to Smart Attendance System
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="card bg-primary text-white">
                                    <div class="card-body text-center">
                                        <i class="fas fa-video fa-3x mb-3"></i>
                                        <h5>CCTV Monitoring</h5>
                                        <p>Real-time face detection and attendance tracking</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-success text-white">
                                    <div class="card-body text-center">
                                        <i class="fas fa-users fa-3x mb-3"></i>
                                        <h5>Student Management</h5>
                                        <p>Comprehensive student information and tracking</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-info text-white">
                                    <div class="card-body text-center">
                                        <i class="fas fa-sms fa-3x mb-3"></i>
                                        <h5>Parent Notifications</h5>
                                        <p>Automatic SMS alerts for attendance</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mt-4">
                            <div class="col-12">
                                <h4>Quick Actions</h4>
                                <div class="d-grid gap-2 d-md-flex">
                                    <a href="live/live.php" class="btn btn-primary">
                                        <i class="fas fa-eye me-2"></i>View Live Portal
                                    </a>
                                    <?php if ($user_role === 'admin'): ?>
                                        <a href="admin/dashboard.php" class="btn btn-success">
                                            <i class="fas fa-cog me-2"></i>Admin Panel
                                        </a>
                                    <?php elseif ($user_role === 'teacher'): ?>
                                        <a href="teacher/dashboard.php" class="btn btn-success">
                                            <i class="fas fa-chalkboard-teacher me-2"></i>Teacher Panel
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

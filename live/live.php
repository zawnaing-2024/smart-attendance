<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$page_title = "Live Portal";
$camera_id = isset($_GET['camera']) ? (int)$_GET['camera'] : null;

// Get cameras
$cameras = $pdo->query("SELECT * FROM cameras WHERE is_active = 1 ORDER BY camera_name")->fetchAll();

// Get selected camera
$selected_camera = null;
if ($camera_id) {
    $stmt = $pdo->prepare("SELECT * FROM cameras WHERE id = ? AND is_active = 1");
    $stmt->execute([$camera_id]);
    $selected_camera = $stmt->fetch();
}

// Get recent attendance for today
$today_attendance = $pdo->query("
    SELECT a.*, s.student_name, s.roll_number, g.grade_name 
    FROM attendance a 
    JOIN students s ON a.student_id = s.id 
    LEFT JOIN grades g ON s.grade_id = g.id 
    WHERE a.attendance_date = CURDATE() 
    ORDER BY a.check_in_time DESC 
    LIMIT 20
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Smart Attendance System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .video-container {
            position: relative;
            background: #000;
            border-radius: 10px;
            overflow: hidden;
        }
        .video-stream {
            width: 100%;
            height: auto;
            display: block;
        }
        .face-detection-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
        }
        .face-box {
            position: absolute;
            border: 2px solid #00ff00;
            background: rgba(0, 255, 0, 0.1);
            border-radius: 5px;
        }
        .face-label {
            position: absolute;
            top: -25px;
            left: 0;
            background: rgba(0, 255, 0, 0.8);
            color: white;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
        }
        .attendance-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 15px;
        }
        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .camera-selector {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
    </style>
</head>
<body style="background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); min-height: 100vh;">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="../index.php">
                <i class="fas fa-graduation-cap me-2"></i>Smart Attendance
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="../index.php">
                    <i class="fas fa-home me-1"></i>Home
                </a>
                <a class="nav-link" href="../logout.php">
                    <i class="fas fa-sign-out-alt me-1"></i>Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <div class="row">
            <!-- Left Sidebar -->
            <div class="col-lg-3">
                <!-- Camera Selector -->
                <div class="camera-selector">
                    <h5 class="mb-3">
                        <i class="fas fa-video me-2"></i>Select Camera
                    </h5>
                    <div class="list-group">
                        <?php foreach ($cameras as $camera): ?>
                            <a href="?camera=<?php echo $camera['id']; ?>" 
                               class="list-group-item list-group-item-action <?php echo $selected_camera && $selected_camera['id'] == $camera['id'] ? 'active' : ''; ?>">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($camera['camera_name']); ?></h6>
                                    <small><?php echo htmlspecialchars($camera['location']); ?></small>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Today's Stats -->
                <div class="stats-card">
                    <h5 class="mb-3">
                        <i class="fas fa-chart-bar me-2"></i>Today's Stats
                    </h5>
                    <?php
                    $stats = get_attendance_stats();
                    $attendance_percentage = $stats['total_students'] > 0 ? round(($stats['present_count'] / $stats['total_students']) * 100, 1) : 0;
                    ?>
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="h3 text-primary"><?php echo $stats['present_count']; ?></div>
                            <div class="text-muted small">Present</div>
                        </div>
                        <div class="col-6">
                            <div class="h3 text-danger"><?php echo $stats['absent_count']; ?></div>
                            <div class="text-muted small">Absent</div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="progress">
                            <div class="progress-bar bg-success" style="width: <?php echo $attendance_percentage; ?>%"></div>
                        </div>
                        <small class="text-muted"><?php echo $attendance_percentage; ?>% Attendance</small>
                    </div>
                </div>

                <!-- Recent Attendance -->
                <div class="stats-card">
                    <h5 class="mb-3">
                        <i class="fas fa-clock me-2"></i>Recent Activity
                    </h5>
                    <div style="max-height: 300px; overflow-y: auto;">
                        <?php foreach ($today_attendance as $attendance): ?>
                            <div class="d-flex justify-content-between align-items-center mb-2 p-2 bg-light rounded">
                                <div>
                                    <div class="fw-bold"><?php echo htmlspecialchars($attendance['student_name']); ?></div>
                                    <small class="text-muted"><?php echo htmlspecialchars($attendance['roll_number']); ?></small>
                                </div>
                                <div class="text-end">
                                    <div class="small"><?php echo $attendance['check_in_time'] ? date('H:i', strtotime($attendance['check_in_time'])) : '-'; ?></div>
                                    <span class="badge bg-success">In</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-lg-9">
                <?php if ($selected_camera): ?>
                    <div class="attendance-card">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h4 class="mb-2">
                                    <i class="fas fa-eye me-2"></i>Live Face Detection
                                </h4>
                                <p class="mb-0">Camera: <?php echo htmlspecialchars($selected_camera['camera_name']); ?> - <?php echo htmlspecialchars($selected_camera['location']); ?></p>
                            </div>
                            <div class="col-md-4 text-end">
                                <div class="d-flex justify-content-end gap-2">
                                    <button class="btn btn-light btn-sm" onclick="startDetection()">
                                        <i class="fas fa-play me-1"></i>Start Detection
                                    </button>
                                    <button class="btn btn-light btn-sm" onclick="stopDetection()">
                                        <i class="fas fa-stop me-1"></i>Stop Detection
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="video-container">
                        <img id="videoStream" src="<?php echo htmlspecialchars($selected_camera['camera_url']); ?>" 
                             class="video-stream" alt="Live Feed">
                        <canvas id="faceCanvas" class="face-detection-overlay"></canvas>
                    </div>

                    <div class="mt-3">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Face Detection Status:</strong> <span id="detectionStatus">Ready</span>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-video fa-5x text-muted mb-4"></i>
                        <h3 class="text-muted">Select a Camera</h3>
                        <p class="text-muted">Choose a camera from the sidebar to start live face detection</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <?php if ($selected_camera): ?>
    <script>
        let detectionActive = false;
        let detectionInterval;
        
        function startDetection() {
            if (!detectionActive) {
                detectionActive = true;
                document.getElementById('detectionStatus').textContent = 'Detecting...';
                
                // Simulate face detection (in real implementation, this would connect to your face detection service)
                detectionInterval = setInterval(() => {
                    // This is a placeholder - in real implementation, you would:
                    // 1. Capture frame from video stream
                    // 2. Send to face detection API/service
                    // 3. Process results and update attendance
                    console.log('Face detection running...');
                }, 1000);
                
                // Show success message
                setTimeout(() => {
                    if (detectionActive) {
                        document.getElementById('detectionStatus').textContent = 'Detection Active';
                    }
                }, 2000);
            }
        }
        
        function stopDetection() {
            if (detectionActive) {
                detectionActive = false;
                clearInterval(detectionInterval);
                document.getElementById('detectionStatus').textContent = 'Stopped';
            }
        }
        
        // Auto-refresh attendance data every 30 seconds
        setInterval(() => {
            if (detectionActive) {
                // Refresh the page to show updated attendance
                // In a real implementation, you would use AJAX to update only the attendance list
                location.reload();
            }
        }, 30000);
        
        // Handle face detection results (placeholder)
        function processFaceDetection(faces) {
            const canvas = document.getElementById('faceCanvas');
            const ctx = canvas.getContext('2d');
            const video = document.getElementById('videoStream');
            
            // Clear previous drawings
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            
            // Set canvas size to match video
            canvas.width = video.offsetWidth;
            canvas.height = video.offsetHeight;
            
            // Draw face boxes
            faces.forEach(face => {
                const { x, y, width, height, name, confidence } = face;
                
                // Draw face box
                ctx.strokeStyle = '#00ff00';
                ctx.lineWidth = 2;
                ctx.strokeRect(x, y, width, height);
                
                // Draw label
                ctx.fillStyle = 'rgba(0, 255, 0, 0.8)';
                ctx.fillRect(x, y - 25, name.length * 8, 20);
                
                ctx.fillStyle = 'white';
                ctx.font = '12px Arial';
                ctx.fillText(`${name} (${Math.round(confidence * 100)}%)`, x + 5, y - 8);
            });
        }
        
        // Example of how to integrate with face detection API
        function detectFaces() {
            // This would be replaced with actual face detection API call
            // For now, we'll simulate some detection results
            const mockFaces = [
                { x: 100, y: 100, width: 80, height: 80, name: 'John Doe', confidence: 0.95 },
                { x: 300, y: 150, width: 70, height: 70, name: 'Jane Smith', confidence: 0.87 }
            ];
            
            processFaceDetection(mockFaces);
        }
    </script>
    <?php endif; ?>
</body>
</html>

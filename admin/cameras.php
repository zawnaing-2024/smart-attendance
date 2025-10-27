<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

check_permission('admin');

$page_title = "CCTV Cameras Management";
include 'includes/header.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_camera':
                $camera_name = sanitize_input($_POST['camera_name']);
                $camera_url = sanitize_input($_POST['camera_url']);
                $location = sanitize_input($_POST['location']);
                
                try {
                    $stmt = $pdo->prepare("INSERT INTO cameras (camera_name, camera_url, location) VALUES (?, ?, ?)");
                    $stmt->execute([$camera_name, $camera_url, $location]);
                    $success = "Camera added successfully!";
                } catch (PDOException $e) {
                    $error = "Error adding camera: " . $e->getMessage();
                }
                break;
                
            case 'update_camera':
                $camera_id = $_POST['camera_id'];
                $camera_name = sanitize_input($_POST['camera_name']);
                $camera_url = sanitize_input($_POST['camera_url']);
                $location = sanitize_input($_POST['location']);
                $is_active = isset($_POST['is_active']) ? 1 : 0;
                
                try {
                    $stmt = $pdo->prepare("UPDATE cameras SET camera_name = ?, camera_url = ?, location = ?, is_active = ? WHERE id = ?");
                    $stmt->execute([$camera_name, $camera_url, $location, $is_active, $camera_id]);
                    $success = "Camera updated successfully!";
                } catch (PDOException $e) {
                    $error = "Error updating camera: " . $e->getMessage();
                }
                break;
                
            case 'delete_camera':
                $camera_id = $_POST['camera_id'];
                try {
                    $stmt = $pdo->prepare("DELETE FROM cameras WHERE id = ?");
                    $stmt->execute([$camera_id]);
                    $success = "Camera deleted successfully!";
                } catch (PDOException $e) {
                    $error = "Error deleting camera: " . $e->getMessage();
                }
                break;
        }
    }
}

// Get all cameras
$cameras = $pdo->query("SELECT * FROM cameras ORDER BY created_at DESC")->fetchAll();
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">CCTV Cameras Management</h1>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCameraModal">
                    <i class="fas fa-plus me-2"></i>Add New Camera
                </button>
            </div>
        </div>
    </div>
    
    <?php if (isset($success)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <?php foreach ($cameras as $camera): ?>
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card shadow">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary"><?php echo htmlspecialchars($camera['camera_name']); ?></h6>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#" onclick="editCamera(<?php echo htmlspecialchars(json_encode($camera)); ?>)">
                                <i class="fas fa-edit me-2"></i>Edit
                            </a></li>
                            <li><a class="dropdown-item text-danger" href="#" onclick="deleteCamera(<?php echo $camera['id']; ?>)">
                                <i class="fas fa-trash me-2"></i>Delete
                            </a></li>
                        </ul>
                    </div>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>Location:</strong> <?php echo htmlspecialchars($camera['location']); ?>
                    </div>
                    <div class="mb-3">
                        <strong>URL:</strong> 
                        <code><?php echo htmlspecialchars($camera['camera_url']); ?></code>
                    </div>
                    <div class="mb-3">
                        <strong>Status:</strong>
                        <span class="badge badge-<?php echo $camera['is_active'] ? 'success' : 'danger'; ?>">
                            <?php echo $camera['is_active'] ? 'Active' : 'Inactive'; ?>
                        </span>
                    </div>
                    <div class="d-grid">
                        <a href="../live/live.php?camera=<?php echo $camera['id']; ?>" class="btn btn-primary btn-sm">
                            <i class="fas fa-eye me-2"></i>View Live Feed
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Add Camera Modal -->
<div class="modal fade" id="addCameraModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Camera</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_camera">
                    <div class="mb-3">
                        <label for="camera_name" class="form-label">Camera Name</label>
                        <input type="text" class="form-control" id="camera_name" name="camera_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="camera_url" class="form-label">Camera URL</label>
                        <input type="url" class="form-control" id="camera_url" name="camera_url" 
                               placeholder="http://192.168.1.100:8080/video" required>
                    </div>
                    <div class="mb-3">
                        <label for="location" class="form-label">Location</label>
                        <input type="text" class="form-control" id="location" name="location" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Camera</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Camera Modal -->
<div class="modal fade" id="editCameraModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Camera</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_camera">
                    <input type="hidden" name="camera_id" id="edit_camera_id">
                    <div class="mb-3">
                        <label for="edit_camera_name" class="form-label">Camera Name</label>
                        <input type="text" class="form-control" id="edit_camera_name" name="camera_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_camera_url" class="form-label">Camera URL</label>
                        <input type="url" class="form-control" id="edit_camera_url" name="camera_url" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_location" class="form-label">Location</label>
                        <input type="text" class="form-control" id="edit_location" name="location" required>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="edit_is_active" name="is_active">
                            <label class="form-check-label" for="edit_is_active">
                                Active
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Camera</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editCamera(camera) {
    document.getElementById('edit_camera_id').value = camera.id;
    document.getElementById('edit_camera_name').value = camera.camera_name;
    document.getElementById('edit_camera_url').value = camera.camera_url;
    document.getElementById('edit_location').value = camera.location;
    document.getElementById('edit_is_active').checked = camera.is_active == 1;
    
    new bootstrap.Modal(document.getElementById('editCameraModal')).show();
}

function deleteCamera(cameraId) {
    if (confirm('Are you sure you want to delete this camera?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete_camera">
            <input type="hidden" name="camera_id" value="${cameraId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php include 'includes/footer.php'; ?>

<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

check_permission('admin');

$page_title = "Grades Management";
include 'includes/header.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_grade':
                $grade_name = sanitize_input($_POST['grade_name']);
                $description = sanitize_input($_POST['description']);
                
                try {
                    $stmt = $pdo->prepare("INSERT INTO grades (grade_name, description) VALUES (?, ?)");
                    $stmt->execute([$grade_name, $description]);
                    $success = "Grade added successfully!";
                } catch (PDOException $e) {
                    $error = "Error adding grade: " . $e->getMessage();
                }
                break;
                
            case 'update_grade':
                $grade_id = $_POST['grade_id'];
                $grade_name = sanitize_input($_POST['grade_name']);
                $description = sanitize_input($_POST['description']);
                
                try {
                    $stmt = $pdo->prepare("UPDATE grades SET grade_name = ?, description = ? WHERE id = ?");
                    $stmt->execute([$grade_name, $description, $grade_id]);
                    $success = "Grade updated successfully!";
                } catch (PDOException $e) {
                    $error = "Error updating grade: " . $e->getMessage();
                }
                break;
                
            case 'delete_grade':
                $grade_id = $_POST['grade_id'];
                try {
                    // Check if grade has students
                    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM students WHERE grade_id = ?");
                    $stmt->execute([$grade_id]);
                    $result = $stmt->fetch();
                    
                    if ($result['count'] > 0) {
                        $error = "Cannot delete grade. It has " . $result['count'] . " students assigned.";
                    } else {
                        $stmt = $pdo->prepare("DELETE FROM grades WHERE id = ?");
                        $stmt->execute([$grade_id]);
                        $success = "Grade deleted successfully!";
                    }
                } catch (PDOException $e) {
                    $error = "Error deleting grade: " . $e->getMessage();
                }
                break;
        }
    }
}

// Get all grades
$grades = $pdo->query("SELECT g.*, COUNT(s.id) as student_count FROM grades g LEFT JOIN students s ON g.id = s.grade_id GROUP BY g.id ORDER BY g.grade_name")->fetchAll();
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Grades Management</h1>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addGradeModal">
                    <i class="fas fa-plus me-2"></i>Add New Grade
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
        <?php foreach ($grades as $grade): ?>
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card shadow">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary"><?php echo htmlspecialchars($grade['grade_name']); ?></h6>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#" onclick="editGrade(<?php echo htmlspecialchars(json_encode($grade)); ?>)">
                                <i class="fas fa-edit me-2"></i>Edit
                            </a></li>
                            <li><a class="dropdown-item text-danger" href="#" onclick="deleteGrade(<?php echo $grade['id']; ?>)">
                                <i class="fas fa-trash me-2"></i>Delete
                            </a></li>
                        </ul>
                    </div>
                </div>
                <div class="card-body">
                    <p class="card-text"><?php echo htmlspecialchars($grade['description']); ?></p>
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted">
                            <i class="fas fa-users me-1"></i><?php echo $grade['student_count']; ?> students
                        </small>
                        <small class="text-muted">
                            Created: <?php echo date('M d, Y', strtotime($grade['created_at'])); ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Add Grade Modal -->
<div class="modal fade" id="addGradeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Grade</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_grade">
                    <div class="mb-3">
                        <label for="grade_name" class="form-label">Grade Name</label>
                        <input type="text" class="form-control" id="grade_name" name="grade_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Grade</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Grade Modal -->
<div class="modal fade" id="editGradeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Grade</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_grade">
                    <input type="hidden" name="grade_id" id="edit_grade_id">
                    <div class="mb-3">
                        <label for="edit_grade_name" class="form-label">Grade Name</label>
                        <input type="text" class="form-control" id="edit_grade_name" name="grade_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Description</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Grade</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editGrade(grade) {
    document.getElementById('edit_grade_id').value = grade.id;
    document.getElementById('edit_grade_name').value = grade.grade_name;
    document.getElementById('edit_description').value = grade.description;
    
    new bootstrap.Modal(document.getElementById('editGradeModal')).show();
}

function deleteGrade(gradeId) {
    if (confirm('Are you sure you want to delete this grade?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete_grade">
            <input type="hidden" name="grade_id" value="${gradeId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php include 'includes/footer.php'; ?>

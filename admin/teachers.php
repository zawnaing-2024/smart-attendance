<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

check_permission('admin');

$page_title = "Teachers Management";
include 'includes/header.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_teacher':
                $teacher_name = sanitize_input($_POST['teacher_name']);
                $grade_id = $_POST['grade_id'];
                $position = sanitize_input($_POST['position']);
                $phone = sanitize_input($_POST['phone']);
                $email = sanitize_input($_POST['email']);
                $username = sanitize_input($_POST['username']);
                $password = $_POST['password'];
                
                try {
                    $pdo->beginTransaction();
                    
                    // Create user account
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO users (username, password, role, full_name, email, phone) VALUES (?, ?, 'teacher', ?, ?, ?)");
                    $stmt->execute([$username, $hashed_password, $teacher_name, $email, $phone]);
                    $user_id = $pdo->lastInsertId();
                    
                    // Create teacher record
                    $stmt = $pdo->prepare("INSERT INTO teachers (user_id, teacher_name, grade_id, position, phone, email) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$user_id, $teacher_name, $grade_id, $position, $phone, $email]);
                    
                    $pdo->commit();
                    $success = "Teacher added successfully!";
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    $error = "Error adding teacher: " . $e->getMessage();
                }
                break;
                
            case 'update_teacher':
                $teacher_id = $_POST['teacher_id'];
                $teacher_name = sanitize_input($_POST['teacher_name']);
                $grade_id = $_POST['grade_id'];
                $position = sanitize_input($_POST['position']);
                $phone = sanitize_input($_POST['phone']);
                $email = sanitize_input($_POST['email']);
                
                try {
                    $stmt = $pdo->prepare("UPDATE teachers SET teacher_name = ?, grade_id = ?, position = ?, phone = ?, email = ? WHERE id = ?");
                    $stmt->execute([$teacher_name, $grade_id, $position, $phone, $email, $teacher_id]);
                    $success = "Teacher updated successfully!";
                } catch (PDOException $e) {
                    $error = "Error updating teacher: " . $e->getMessage();
                }
                break;
                
            case 'delete_teacher':
                $teacher_id = $_POST['teacher_id'];
                try {
                    $pdo->beginTransaction();
                    
                    // Get user_id first
                    $stmt = $pdo->prepare("SELECT user_id FROM teachers WHERE id = ?");
                    $stmt->execute([$teacher_id]);
                    $teacher = $stmt->fetch();
                    
                    if ($teacher) {
                        // Delete teacher record
                        $stmt = $pdo->prepare("DELETE FROM teachers WHERE id = ?");
                        $stmt->execute([$teacher_id]);
                        
                        // Delete user account
                        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                        $stmt->execute([$teacher['user_id']]);
                    }
                    
                    $pdo->commit();
                    $success = "Teacher deleted successfully!";
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    $error = "Error deleting teacher: " . $e->getMessage();
                }
                break;
        }
    }
}

// Get all teachers with grade information
$teachers = $pdo->query("
    SELECT t.*, g.grade_name, u.username 
    FROM teachers t 
    LEFT JOIN grades g ON t.grade_id = g.id 
    LEFT JOIN users u ON t.user_id = u.id
    ORDER BY t.created_at DESC
")->fetchAll();

// Get all grades for dropdown
$grades = $pdo->query("SELECT * FROM grades ORDER BY grade_name")->fetchAll();
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Teachers Management</h1>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTeacherModal">
                    <i class="fas fa-plus me-2"></i>Add New Teacher
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
    
    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Teacher Name</th>
                            <th>Username</th>
                            <th>Grade</th>
                            <th>Position</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($teachers as $teacher): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($teacher['teacher_name']); ?></td>
                            <td><?php echo htmlspecialchars($teacher['username']); ?></td>
                            <td><?php echo htmlspecialchars($teacher['grade_name'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($teacher['position'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($teacher['phone'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($teacher['email'] ?? 'N/A'); ?></td>
                            <td>
                                <div class="btn-group" role="group">
                                    <button class="btn btn-sm btn-outline-primary" onclick="editTeacher(<?php echo htmlspecialchars(json_encode($teacher)); ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteTeacher(<?php echo $teacher['id']; ?>)">
                                        <i class="fas fa-trash"></i>
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

<!-- Add Teacher Modal -->
<div class="modal fade" id="addTeacherModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Teacher</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_teacher">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="teacher_name" class="form-label">Teacher Name</label>
                                <input type="text" class="form-control" id="teacher_name" name="teacher_name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="position" class="form-label">Position</label>
                                <input type="text" class="form-control" id="position" name="position">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="grade_id" class="form-label">Grade to Teach</label>
                                <select class="form-select" id="grade_id" name="grade_id">
                                    <option value="">Select Grade</option>
                                    <?php foreach ($grades as $grade): ?>
                                        <option value="<?php echo $grade['id']; ?>"><?php echo htmlspecialchars($grade['grade_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="tel" class="form-control" id="phone" name="phone">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Teacher</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Teacher Modal -->
<div class="modal fade" id="editTeacherModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Teacher</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_teacher">
                    <input type="hidden" name="teacher_id" id="edit_teacher_id">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_teacher_name" class="form-label">Teacher Name</label>
                                <input type="text" class="form-control" id="edit_teacher_name" name="teacher_name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_position" class="form-label">Position</label>
                                <input type="text" class="form-control" id="edit_position" name="position">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_grade_id" class="form-label">Grade to Teach</label>
                                <select class="form-select" id="edit_grade_id" name="grade_id">
                                    <option value="">Select Grade</option>
                                    <?php foreach ($grades as $grade): ?>
                                        <option value="<?php echo $grade['id']; ?>"><?php echo htmlspecialchars($grade['grade_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_phone" class="form-label">Phone</label>
                                <input type="tel" class="form-control" id="edit_phone" name="phone">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="edit_email" name="email">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Teacher</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editTeacher(teacher) {
    document.getElementById('edit_teacher_id').value = teacher.id;
    document.getElementById('edit_teacher_name').value = teacher.teacher_name;
    document.getElementById('edit_position').value = teacher.position || '';
    document.getElementById('edit_grade_id').value = teacher.grade_id || '';
    document.getElementById('edit_phone').value = teacher.phone || '';
    document.getElementById('edit_email').value = teacher.email || '';
    
    new bootstrap.Modal(document.getElementById('editTeacherModal')).show();
}

function deleteTeacher(teacherId) {
    if (confirm('Are you sure you want to delete this teacher?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete_teacher">
            <input type="hidden" name="teacher_id" value="${teacherId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php include 'includes/footer.php'; ?>

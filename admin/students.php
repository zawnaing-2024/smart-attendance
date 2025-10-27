<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

check_permission('admin');

$page_title = "Students Management";
include 'includes/header.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_student':
                $roll_number = sanitize_input($_POST['roll_number']);
                $student_name = sanitize_input($_POST['student_name']);
                $grade_id = $_POST['grade_id'];
                $parent_phone1 = sanitize_input($_POST['parent_phone1']);
                $parent_phone2 = sanitize_input($_POST['parent_phone2']);
                
                try {
                    $face_image_path = null;
                    if (isset($_FILES['face_image']) && $_FILES['face_image']['error'] === UPLOAD_ERR_OK) {
                        $face_image_path = upload_face_image($_FILES['face_image'], 0);
                    }
                    
                    $stmt = $pdo->prepare("INSERT INTO students (roll_number, student_name, grade_id, parent_phone1, parent_phone2, face_image_path) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$roll_number, $student_name, $grade_id, $parent_phone1, $parent_phone2, $face_image_path]);
                    $success = "Student added successfully!";
                } catch (PDOException $e) {
                    $error = "Error adding student: " . $e->getMessage();
                }
                break;
                
            case 'update_student':
                $student_id = $_POST['student_id'];
                $roll_number = sanitize_input($_POST['roll_number']);
                $student_name = sanitize_input($_POST['student_name']);
                $grade_id = $_POST['grade_id'];
                $parent_phone1 = sanitize_input($_POST['parent_phone1']);
                $parent_phone2 = sanitize_input($_POST['parent_phone2']);
                $is_active = isset($_POST['is_active']) ? 1 : 0;
                
                try {
                    $face_image_path = null;
                    if (isset($_FILES['face_image']) && $_FILES['face_image']['error'] === UPLOAD_ERR_OK) {
                        $face_image_path = upload_face_image($_FILES['face_image'], $student_id);
                    }
                    
                    $sql = "UPDATE students SET roll_number = ?, student_name = ?, grade_id = ?, parent_phone1 = ?, parent_phone2 = ?, is_active = ?";
                    $params = [$roll_number, $student_name, $grade_id, $parent_phone1, $parent_phone2, $is_active];
                    
                    if ($face_image_path) {
                        $sql .= ", face_image_path = ?";
                        $params[] = $face_image_path;
                    }
                    
                    $sql .= " WHERE id = ?";
                    $params[] = $student_id;
                    
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
                    $success = "Student updated successfully!";
                } catch (PDOException $e) {
                    $error = "Error updating student: " . $e->getMessage();
                }
                break;
                
            case 'delete_student':
                $student_id = $_POST['student_id'];
                try {
                    $stmt = $pdo->prepare("DELETE FROM students WHERE id = ?");
                    $stmt->execute([$student_id]);
                    $success = "Student deleted successfully!";
                } catch (PDOException $e) {
                    $error = "Error deleting student: " . $e->getMessage();
                }
                break;
        }
    }
}

// Get all students with grade information
$students = $pdo->query("
    SELECT s.*, g.grade_name 
    FROM students s 
    LEFT JOIN grades g ON s.grade_id = g.id 
    ORDER BY s.created_at DESC
")->fetchAll();

// Get all grades for dropdown
$grades = $pdo->query("SELECT * FROM grades ORDER BY grade_name")->fetchAll();
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Students Management</h1>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                    <i class="fas fa-plus me-2"></i>Add New Student
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
                            <th>Roll Number</th>
                            <th>Student Name</th>
                            <th>Grade</th>
                            <th>Parent Phone 1</th>
                            <th>Parent Phone 2</th>
                            <th>Face Image</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($student['roll_number']); ?></td>
                            <td><?php echo htmlspecialchars($student['student_name']); ?></td>
                            <td><?php echo htmlspecialchars($student['grade_name'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($student['parent_phone1']); ?></td>
                            <td><?php echo htmlspecialchars($student['parent_phone2'] ?? 'N/A'); ?></td>
                            <td>
                                <?php if ($student['face_image_path']): ?>
                                    <img src="../<?php echo $student['face_image_path']; ?>" alt="Face" class="img-thumbnail" style="width: 50px; height: 50px;">
                                <?php else: ?>
                                    <span class="text-muted">No image</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge badge-<?php echo $student['is_active'] ? 'success' : 'danger'; ?>">
                                    <?php echo $student['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <button class="btn btn-sm btn-outline-primary" onclick="editStudent(<?php echo htmlspecialchars(json_encode($student)); ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteStudent(<?php echo $student['id']; ?>)">
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

<!-- Add Student Modal -->
<div class="modal fade" id="addStudentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Student</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_student">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="roll_number" class="form-label">Roll Number</label>
                                <input type="text" class="form-control" id="roll_number" name="roll_number" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="student_name" class="form-label">Student Name</label>
                                <input type="text" class="form-control" id="student_name" name="student_name" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="grade_id" class="form-label">Grade</label>
                                <select class="form-select" id="grade_id" name="grade_id" required>
                                    <option value="">Select Grade</option>
                                    <?php foreach ($grades as $grade): ?>
                                        <option value="<?php echo $grade['id']; ?>"><?php echo htmlspecialchars($grade['grade_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="face_image" class="form-label">Face Image</label>
                                <input type="file" class="form-control" id="face_image" name="face_image" accept="image/*">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="parent_phone1" class="form-label">Parent Phone 1</label>
                                <input type="tel" class="form-control" id="parent_phone1" name="parent_phone1" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="parent_phone2" class="form-label">Parent Phone 2</label>
                                <input type="tel" class="form-control" id="parent_phone2" name="parent_phone2">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Student</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Student Modal -->
<div class="modal fade" id="editStudentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Student</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_student">
                    <input type="hidden" name="student_id" id="edit_student_id">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_roll_number" class="form-label">Roll Number</label>
                                <input type="text" class="form-control" id="edit_roll_number" name="roll_number" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_student_name" class="form-label">Student Name</label>
                                <input type="text" class="form-control" id="edit_student_name" name="student_name" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_grade_id" class="form-label">Grade</label>
                                <select class="form-select" id="edit_grade_id" name="grade_id" required>
                                    <option value="">Select Grade</option>
                                    <?php foreach ($grades as $grade): ?>
                                        <option value="<?php echo $grade['id']; ?>"><?php echo htmlspecialchars($grade['grade_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_face_image" class="form-label">Face Image</label>
                                <input type="file" class="form-control" id="edit_face_image" name="face_image" accept="image/*">
                                <div id="current_face_image" class="mt-2"></div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_parent_phone1" class="form-label">Parent Phone 1</label>
                                <input type="tel" class="form-control" id="edit_parent_phone1" name="parent_phone1" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_parent_phone2" class="form-label">Parent Phone 2</label>
                                <input type="tel" class="form-control" id="edit_parent_phone2" name="parent_phone2">
                            </div>
                        </div>
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
                    <button type="submit" class="btn btn-primary">Update Student</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editStudent(student) {
    document.getElementById('edit_student_id').value = student.id;
    document.getElementById('edit_roll_number').value = student.roll_number;
    document.getElementById('edit_student_name').value = student.student_name;
    document.getElementById('edit_grade_id').value = student.grade_id;
    document.getElementById('edit_parent_phone1').value = student.parent_phone1;
    document.getElementById('edit_parent_phone2').value = student.parent_phone2 || '';
    document.getElementById('edit_is_active').checked = student.is_active == 1;
    
    // Show current face image
    const currentImageDiv = document.getElementById('current_face_image');
    if (student.face_image_path) {
        currentImageDiv.innerHTML = `
            <small class="text-muted">Current image:</small><br>
            <img src="../${student.face_image_path}" alt="Current face" class="img-thumbnail" style="width: 100px; height: 100px;">
        `;
    } else {
        currentImageDiv.innerHTML = '<small class="text-muted">No current image</small>';
    }
    
    new bootstrap.Modal(document.getElementById('editStudentModal')).show();
}

function deleteStudent(studentId) {
    if (confirm('Are you sure you want to delete this student?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete_student">
            <input type="hidden" name="student_id" value="${studentId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php include 'includes/footer.php'; ?>

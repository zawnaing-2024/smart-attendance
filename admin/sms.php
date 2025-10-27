<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

check_permission('admin');

$page_title = "SMS Settings";
include 'includes/header.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_sms_settings':
                $api_key = sanitize_input($_POST['api_key']);
                $api_secret = sanitize_input($_POST['api_secret']);
                $sender_id = sanitize_input($_POST['sender_id']);
                $is_active = isset($_POST['is_active']) ? 1 : 0;
                
                try {
                    // Check if settings exist
                    $stmt = $pdo->prepare("SELECT id FROM sms_settings LIMIT 1");
                    $stmt->execute();
                    $existing = $stmt->fetch();
                    
                    if ($existing) {
                        $stmt = $pdo->prepare("UPDATE sms_settings SET api_key = ?, api_secret = ?, sender_id = ?, is_active = ? WHERE id = ?");
                        $stmt->execute([$api_key, $api_secret, $sender_id, $is_active, $existing['id']]);
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO sms_settings (api_key, api_secret, sender_id, is_active) VALUES (?, ?, ?, ?)");
                        $stmt->execute([$api_key, $api_secret, $sender_id, $is_active]);
                    }
                    
                    $success = "SMS settings updated successfully!";
                } catch (PDOException $e) {
                    $error = "Error updating SMS settings: " . $e->getMessage();
                }
                break;
                
            case 'test_sms':
                $phone_number = sanitize_input($_POST['test_phone']);
                $message = "Test message from Smart Attendance System at " . date('Y-m-d H:i:s');
                
                if (send_sms($phone_number, $message)) {
                    $success = "Test SMS sent successfully!";
                } else {
                    $error = "Failed to send test SMS. Please check your SMS settings.";
                }
                break;
        }
    }
}

// Get current SMS settings
$sms_settings = $pdo->query("SELECT * FROM sms_settings LIMIT 1")->fetch();

// Get SMS logs
$sms_logs = $pdo->query("
    SELECT sl.*, s.student_name, s.roll_number 
    FROM sms_logs sl 
    LEFT JOIN students s ON sl.student_id = s.id 
    ORDER BY sl.sent_at DESC 
    LIMIT 50
")->fetchAll();
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h1 class="h3 mb-4">SMS Settings</h1>
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
        <!-- SMS Configuration -->
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">SMS Configuration</h6>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="update_sms_settings">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="api_key" class="form-label">API Key</label>
                                    <input type="text" class="form-control" id="api_key" name="api_key" 
                                           value="<?php echo htmlspecialchars($sms_settings['api_key'] ?? ''); ?>" 
                                           placeholder="Your SMS provider API key">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="api_secret" class="form-label">API Secret</label>
                                    <input type="password" class="form-control" id="api_secret" name="api_secret" 
                                           value="<?php echo htmlspecialchars($sms_settings['api_secret'] ?? ''); ?>" 
                                           placeholder="Your SMS provider API secret">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="sender_id" class="form-label">Sender ID</label>
                                    <input type="text" class="form-control" id="sender_id" name="sender_id" 
                                           value="<?php echo htmlspecialchars($sms_settings['sender_id'] ?? ''); ?>" 
                                           placeholder="Your sender ID (max 11 characters)">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <div class="form-check mt-4">
                                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                               <?php echo ($sms_settings['is_active'] ?? false) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="is_active">
                                            Enable SMS notifications
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Save Settings
                            </button>
                            
                            <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#testSmsModal">
                                <i class="fas fa-paper-plane me-2"></i>Test SMS
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- SMS Status -->
        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">SMS Status</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>Status:</strong>
                        <span class="badge badge-<?php echo ($sms_settings['is_active'] ?? false) ? 'success' : 'danger'; ?> ms-2">
                            <?php echo ($sms_settings['is_active'] ?? false) ? 'Active' : 'Inactive'; ?>
                        </span>
                    </div>
                    
                    <div class="mb-3">
                        <strong>Provider:</strong>
                        <span class="text-muted ms-2">Custom SMS Provider</span>
                    </div>
                    
                    <div class="mb-3">
                        <strong>Last Updated:</strong>
                        <span class="text-muted ms-2">
                            <?php echo $sms_settings ? date('M d, Y H:i', strtotime($sms_settings['updated_at'])) : 'Never'; ?>
                        </span>
                    </div>
                    
                    <hr>
                    
                    <h6>Quick Stats</h6>
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="text-primary h4"><?php echo count($sms_logs); ?></div>
                            <div class="text-muted small">Total Sent</div>
                        </div>
                        <div class="col-6">
                            <div class="text-success h4"><?php echo count(array_filter($sms_logs, function($log) { return $log['status'] === 'sent'; })); ?></div>
                            <div class="text-muted small">Successful</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- SMS Logs -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Recent SMS Logs</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="smsLogsTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Phone Number</th>
                                    <th>Message Type</th>
                                    <th>Message</th>
                                    <th>Sent At</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sms_logs as $log): ?>
                                <tr>
                                    <td>
                                        <?php if ($log['student_name']): ?>
                                            <?php echo htmlspecialchars($log['student_name']); ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($log['roll_number']); ?></small>
                                        <?php else: ?>
                                            <span class="text-muted">Test SMS</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($log['phone_number']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $log['sms_type'] === 'check_in' ? 'success' : 'info'; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $log['sms_type'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="max-width: 200px; overflow: hidden; text-overflow: ellipsis;">
                                            <?php echo htmlspecialchars($log['message']); ?>
                                        </div>
                                    </td>
                                    <td><?php echo date('M d, Y H:i', strtotime($log['sent_at'])); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $log['status'] === 'sent' ? 'success' : 'danger'; ?>">
                                            <?php echo ucfirst($log['status']); ?>
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

<!-- Test SMS Modal -->
<div class="modal fade" id="testSmsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Test SMS</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="test_sms">
                    <div class="mb-3">
                        <label for="test_phone" class="form-label">Phone Number</label>
                        <input type="tel" class="form-control" id="test_phone" name="test_phone" 
                               placeholder="+1234567890" required>
                        <div class="form-text">Enter the phone number to send test SMS</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Send Test SMS</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#smsLogsTable').DataTable({
        "pageLength": 25,
        "order": [[ 4, "desc" ]]
    });
});
</script>

<?php include 'includes/footer.php'; ?>

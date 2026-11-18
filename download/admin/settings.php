<?php
require_once __DIR__ . '/../includes/auth.php';
$u = require_role('admin'); $layout = 'dash'; $pageTitle = 'System Settings';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['action'] ?? '') === 'settings') {
        $vals = ['site_name' => trim($_POST['site_name']), 'contact_email' => trim($_POST['contact_email']), 'contact_phone' => trim($_POST['contact_phone']), 'contact_address' => trim($_POST['contact_address']),
                 'jobs_per_page' => (string)max(3, min(30, (int)$_POST['jobs_per_page'])), 'email_notifications' => !empty($_POST['email_notifications']) ? '1' : '0', 'allow_registration' => !empty($_POST['allow_registration']) ? '1' : '0'];
        foreach ($vals as $k => $v) q('INSERT INTO system_settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)', [$k, $v]);
        log_activity('Settings updated', ''); flash('success', 'Settings saved.');
    } elseif (($_POST['action'] ?? '') === 'password') {
        $new = $_POST['new_password'] ?? '';
        if (!password_verify($_POST['current_password'] ?? '', $u['password'])) flash('danger', 'Current password is incorrect.');
        elseif (strlen($new) < 8 || !preg_match('/[A-Za-z]/', $new) || !preg_match('/\d/', $new)) flash('danger', 'New password needs 8+ characters with letters and numbers.');
        else { q('UPDATE users SET password=? WHERE id=?', [password_hash($new, PASSWORD_DEFAULT), $u['id']]); log_activity('Admin password changed', ''); flash('success', 'Password changed.'); }
    }
    redirect('admin/settings.php');
}
$s = array_column(rows('SELECT setting_key, setting_value FROM system_settings'), 'setting_value', 'setting_key');
include ROOT_PATH . '/includes/header.php';
?>

<!-- Custom CSS para sa mas pinagandang hitsura -->
<style>
    .settings-card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.04);
        transition: transform 0.2s ease-in-out;
    }
    .settings-card:hover {
        box-shadow: 0 0.75rem 1.5rem rgba(0, 0, 0, 0.08);
    }
    .form-control:focus, .form-check-input:focus {
        border-color: #86b7fe;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.15);
    }
    .form-switch .form-check-input {
        width: 2.5em;
        height: 1.25em;
        cursor: pointer;
    }
    .settings-header {
        border-bottom: 1px solid #f1f3f5;
        padding-bottom: 1rem;
        margin-bottom: 1.5rem;
    }
</style>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            
            <!-- General Settings Form -->
            <form method="post" class="card settings-card p-4 p-md-5 mb-4">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="settings">
                
                <div class="settings-header d-flex align-items-center justify-content-between">
                    <div>
                        <h4 class="fw-bold text-dark mb-1">System Settings</h4>
                        <p class="text-muted small mb-0">I-configure ang pangkalahatang detalye at mga katangian ng sistema.</p>
                    </div>
                    <div class="bg-primary bg-opacity-10 p-2 rounded-circle text-primary">
                        <i class="bi bi-gear-fill fs-5"></i>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-secondary small">Site Name</label>
                        <input name="site_name" class="form-control" value="<?= e($s['site_name'] ?? '') ?>" placeholder="Ilagay ang pangalan ng site">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-secondary small">Jobs Per Page</label>
                        <input type="number" name="jobs_per_page" min="3" max="30" class="form-control" value="<?= e($s['jobs_per_page'] ?? 6) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-secondary small">Contact Email</label>
                        <input type="email" name="contact_email" class="form-control" value="<?= e($s['contact_email'] ?? '') ?>" placeholder="admin@example.com">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-secondary small">Contact Phone</label>
                        <input name="contact_phone" class="form-control" value="<?= e($s['contact_phone'] ?? '') ?>" placeholder="+63 912 345 6789">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold text-secondary small">Address</label>
                        <input name="contact_address" class="form-control" value="<?= e($s['contact_address'] ?? '') ?>" placeholder="Buong tirahan o opisina">
                    </div>
                    
                    <div class="col-12 mt-4">
                        <div class="p-3 bg-light rounded-3 border">
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" name="email_notifications" id="en" <?= ($s['email_notifications'] ?? '') === '1' ? 'checked' : '' ?>>
                                <label class="form-check-label fw-medium text-dark" for="en">Send email notifications <span class="text-muted small">(nangangailangan ng mail server)</span></label>
                            </div>
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" name="allow_registration" id="ar" <?= ($s['allow_registration'] ?? '') === '1' ? 'checked' : '' ?>>
                                <label class="form-check-label fw-medium text-dark" for="ar">Allow new job seeker registrations</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4 pt-3 border-top d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary px-4 fw-semibold shadow-sm">Save Settings</button>
                </div>
            </form>

            <!-- Change Password Card -->
            <div class="card settings-card p-4 p-md-5">
                <div class="settings-header d-flex align-items-center justify-content-between">
                    <div>
                        <h5 class="fw-bold text-dark mb-1">Security Settings</h5>
                        <p class="text-muted small mb-0">Palitan ang password ng iyong administrator account.</p>
                    </div>
                    <div class="bg-warning bg-opacity-10 p-2 rounded-circle text-warning">
                        <i class="bi bi-shield-lock-fill fs-5"></i>
                    </div>
                </div>

                <form method="post" class="row g-3">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="password">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold text-secondary small">Current Password</label>
                        <input type="password" name="current_password" class="form-control" placeholder="••••••••" required>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label fw-semibold text-secondary small">New Password</label>
                        <input type="password" name="new_password" class="form-control" placeholder="8+ chars, may letters & numbers" required>
                    </div>
                    <div class="col-md-3 d-grid align-items-end">
                        <button type="submit" class="btn btn-outline-primary fw-semibold shadow-sm">Update Password</button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>

<?php include ROOT_PATH . '/includes/footer.php'; ?>
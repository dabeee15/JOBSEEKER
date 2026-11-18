<?php
require_once __DIR__ . '/../includes/auth.php';

$u = require_role('staff');

$appId = (int) ($_GET['app'] ?? 0);

// Palitan ang a.user_id kung iba ang column name ng applicant sa applications table
$r = row(
    'SELECT p.valid_id, p.valid_id_type
     FROM applications a
     JOIN user_profiles p ON p.user_id = a.user_id
     WHERE a.id = ?',
    [$appId]
);

$file = $r ? ROOT_PATH . '/uploads/ids/' . basename($r['valid_id']) : '';

if (!$r || empty($r['valid_id']) || !is_file($file)) {
    http_response_code(404);
    exit('Not found');
}

header('Content-Type: ' . $r['valid_id_type']);
header('Content-Disposition: inline; filename="valid-id"');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Cache-Control: private, no-store');
header('Content-Length: ' . filesize($file));
readfile($file);
exit;
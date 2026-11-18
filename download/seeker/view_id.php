<?php
require_once __DIR__ . '/../includes/auth.php';

$u = require_role('seeker');

$r = row(
    'SELECT valid_id, valid_id_type FROM user_profiles WHERE user_id=?',
    [$u['id']]
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
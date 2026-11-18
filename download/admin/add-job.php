<?php
require __DIR__ . "/../staff/add-job.php";
if ($u['role'] !== 'admin') {
    foreach (rows("SELECT id FROM users WHERE role='admin'") as $ad) {
        notify($ad['id'], 'Job awaiting approval', "A new job \"$title\" needs your approval.", false);
    }
    flash('success', 'Job submitted. It will be visible to job seekers once an admin approves it.');
}

<?php
require_once __DIR__ . '/../admin/includes/auth.php';
$_SESSION['admin_id'] = 1;
$_SESSION['admin_username'] = 'admin';
$_SESSION['admin_name'] = 'Administrator';
$_SESSION['admin_role'] = 'Concierge Lead';
$_SESSION['admin_email'] = 'foodforestkanthalloor@gmail.com';
$_SESSION['admin_logged_in'] = true;
$_SESSION['last_activity'] = time();

header("Location: ../admin/kitchen.php");
exit;

<?php
// admin/logout.php
require_once dirname(__DIR__) . '/includes/functions.php';
session_destroy();
header('Location: /admin/login.php');
exit;

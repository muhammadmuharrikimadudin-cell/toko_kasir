<?php
session_start();
$_SESSION['user_id'] = 1; // Fake login
require 'config/database.php';

// call api/dashboard.php
ob_start();
require 'api/dashboard.php';
$output = ob_get_clean();
echo $output;

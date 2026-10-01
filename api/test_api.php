<?php
session_start();
$_SESSION['user_id'] = 1; // Fake login
require '../config/database.php';
require 'dashboard.php';

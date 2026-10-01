<?php
$_SERVER['REQUEST_METHOD'] = 'GET';
session_start();
$_SESSION['user_id'] = 1;
require '../config/database.php';
require 'dashboard.php';

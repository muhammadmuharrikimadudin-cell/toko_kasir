<?php
$_SERVER['REQUEST_METHOD'] = 'GET';
session_start();
$_SESSION['user_id'] = 1;
$_GET['limit'] = 50;
require '../config/database.php';
require 'products.php';

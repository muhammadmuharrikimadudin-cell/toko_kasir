<?php
$_SERVER['REQUEST_METHOD'] = 'POST';
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['name'] = 'Administrator';

// Simulasi cart items
$input = json_encode([
    'items' => [
        ['id' => 1, 'nama' => 'Indomie Goreng', 'harga_jual' => 3500, 'harga_beli' => 2500, 'qty' => 2],
        ['id' => 6, 'nama' => 'Snack Chitato', 'harga_jual' => 10000, 'harga_beli' => 8000, 'qty' => 1],
    ],
    'diskon' => 0,
    'metode_bayar' => 'Cash',
]);

// Override php://input
$GLOBALS['_TEST_INPUT'] = $input;
require '../config/database.php';

// Patch file_get_contents untuk test
// jalankan langsung
require 'transactions.php';

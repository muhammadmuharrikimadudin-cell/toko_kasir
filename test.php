<?php
require 'config/database.php';
$db = getDB();
try {
    $stmt = $db->query('SELECT COALESCE(SUM(total_amount), 0) FROM transactions');
    echo "OK: " . $stmt->fetchColumn();
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}

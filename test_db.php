<?php
require 'config/database.php';
$db = getDB();
try {
    $stmt = $db->query('SELECT e.*, a.status AS attendance_status FROM employees e LEFT JOIN attendance a ON a.employee_id = e.id AND a.tanggal = CURDATE() ORDER BY e.id ASC');
    var_dump($stmt->fetchAll());
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

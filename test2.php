<?php
require 'config/database.php';
$db = getDB();
try {
    $stmt = $db->query('DESCRIBE transactions');
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}

<?php
require 'config/database.php';
$db = getDB();

echo "=== ALL TABLES ===\n";
$stmt = $db->query("SHOW TABLES");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
print_r($tables);

echo "\n=== TABLE SCHEMAS ===\n";
foreach ($tables as $t) {
    echo "\n--- $t ---\n";
    $s = $db->query("DESCRIBE `$t`");
    foreach ($s->fetchAll(PDO::FETCH_ASSOC) as $col) {
        echo "  {$col['Field']} ({$col['Type']})\n";
    }
}

<?php
$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => 'Cookie: PHPSESSID=testcookie',
        'ignore_errors' => true
    ]
]);
$result = file_get_contents('http://localhost:8000/api/employees.php', false, $context);
echo "Status: " . $http_response_header[0] . "\n";
echo "Response: " . $result . "\n";

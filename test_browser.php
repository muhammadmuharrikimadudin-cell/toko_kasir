<?php
session_start();
$_SESSION['user_id'] = 1;
require_once 'config/database.php';
?>
<!DOCTYPE html>
<html>
<body>
<h1>Test Fetch</h1>
<pre id="out">Fetching...</pre>
<script>
async function test() {
    try {
        const res = await fetch('/api/employees.php');
        const text = await res.text();
        document.getElementById('out').textContent = text;
        
        const json = JSON.parse(text);
        document.getElementById('out').textContent += '\n\nParsed OK:\n' + JSON.stringify(json, null, 2);
    } catch(e) {
        document.getElementById('out').textContent += '\n\nERROR:\n' + e.message;
    }
}
test();
</script>
</body>
</html>

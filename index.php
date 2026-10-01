<?php
// index.php — Redirect ke dashboard atau login
session_start();
if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
} else {
    header('Location: login.php');
}
exit;
?>

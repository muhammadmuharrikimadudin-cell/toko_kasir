<?php
declare(strict_types=1);

// ============================================================
// config/database.php — PHP 8.3
// Koneksi PDO ke MySQL
// ============================================================

define('DB_HOST',    'localhost');
define('DB_NAME',    'db_toko_kasir');
define('DB_USER',    'root');
define('DB_PASS',    '');
define('DB_CHARSET', 'utf8mb4');

function getDB(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn     = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO(dsn: $dsn, username: DB_USER, password: DB_PASS, options: $options);
        } catch (PDOException $e) {
            http_response_code(500);
            die(json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]));
        }
    }

    return $pdo;
}

// ---- Session helpers ----

function requireLogin(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (empty($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

function checkRole(array $allowedRoles): void {
    requireLogin();
    $userRole = $_SESSION['role'] ?? '';
    if (!in_array($userRole, $allowedRoles, true)) {
        if ($userRole === 'cashier') {
            header('Location: kasir.php');
        } elseif ($userRole === 'karyawan') {
            header('Location: employee.php');
        } else {
            header('Location: dashboard.php');
        }
        exit;
    }
}

function getCurrentUser(): array
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    return [
        'id'   => $_SESSION['user_id'] ?? 0,
        'name' => $_SESSION['name']    ?? 'Guest',
        'role' => $_SESSION['role']    ?? 'cashier',
    ];
}

function formatRupiah(float $amount): string
{
    return 'Rp ' . number_format(num: $amount, decimals: 0, decimal_separator: ',', thousands_separator: '.');
}

/**
 * Kirim JSON response lalu exit.
 */
function jsonResponse(mixed $data, int $statusCode = 200): never
{
    http_response_code($statusCode);
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Cache-Control: post-check=0, pre-check=0', false);
    header('Pragma: no-cache');
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Validasi dan kembalikan string dari $_GET / $_POST.
 */
function inputString(array $source, string $key, string $default = ''): string
{
    return trim((string)($source[$key] ?? $default));
}

function inputInt(array $source, string $key, int $default = 0): int
{
    return (int)($source[$key] ?? $default);
}

function inputFloat(array $source, string $key, float $default = 0.0): float
{
    return (float)($source[$key] ?? $default);
}

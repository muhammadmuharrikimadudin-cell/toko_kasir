<?php
declare(strict_types=1);

// api/employees.php — CRUD Karyawan & Absensi (with Locked Attendance)
// PHP 8.3 compatible

header('Content-Type: application/json');
session_start();
require_once '../config/database.php';

if (empty($_SESSION['user_id'])) {
    jsonResponse(data: ['error' => 'Unauthorized'], statusCode: 401);
}

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$action = inputString($_GET, 'action');

match ($method) {
    'GET'    => handleGet($db, $action),
    'POST'   => handlePost($db, $action),
    'DELETE' => handleDelete($db),
    default  => jsonResponse(data: ['error' => 'Method not allowed'], statusCode: 405),
};

// ---- Helpers ----

/**
 * Cek apakah absensi untuk tanggal tertentu sudah dikunci.
 */
function isDateLocked(PDO $db, string $date = 'today'): bool
{
    $tanggal = ($date === 'today') ? date('Y-m-d') : $date;
    $stmt = $db->prepare(
        'SELECT COUNT(*) FROM attendance_lock_log WHERE tanggal = :tanggal'
    );
    $stmt->execute([':tanggal' => $tanggal]);
    return (int)$stmt->fetchColumn() > 0;
}

// ---- Handlers ----

function handleGet(PDO $db, string $action): never
{
    // --- Cek status lock hari ini ---
    if ($action === 'lock_status') {
        $locked = isDateLocked($db);
        $lockedAt = null;
        if ($locked) {
            $stmt = $db->prepare(
                'SELECT locked_at FROM attendance_lock_log WHERE tanggal = CURDATE()'
            );
            $stmt->execute();
            $lockedAt = $stmt->fetchColumn();
        }
        jsonResponse(['locked' => $locked, 'locked_at' => $lockedAt]);
    }

    if ($action === 'history') {
        $dateFrom = inputString($_GET, 'date_from');
        $dateTo   = inputString($_GET, 'date_to');

        $where = '';
        $params = [];
        if ($dateFrom !== '' && $dateTo !== '') {
            $where = 'WHERE a.tanggal BETWEEN :date_from AND :date_to';
            $params = [':date_from' => $dateFrom, ':date_to' => $dateTo];
        } elseif ($dateFrom !== '') {
            $where = 'WHERE a.tanggal >= :date_from';
            $params = [':date_from' => $dateFrom];
        } elseif ($dateTo !== '') {
            $where = 'WHERE a.tanggal <= :date_to';
            $params = [':date_to' => $dateTo];
        }

        $stmt = $db->prepare(<<<SQL
            SELECT a.tanggal, e.nama, e.posisi, a.status
            FROM attendance a
            JOIN employees e ON e.id = a.employee_id
            {$where}
            ORDER BY a.tanggal DESC, e.nama ASC
        SQL);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        jsonResponse(['data' => $data ?: []]);
    }

    // Default: daftar karyawan + status absensi hari ini
    $stmt = $db->prepare(<<<SQL
        SELECT e.id,
               e.nama,
               e.posisi,
               e.no_hp,
               e.alamat,
               COALESCE(a.status, '') AS attendance_status,
               COALESCE(a.is_locked, 0) AS is_locked
        FROM employees e
        LEFT JOIN attendance a
               ON a.employee_id = e.id AND a.tanggal = CURDATE()
        ORDER BY e.id ASC
    SQL);
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Cast is_locked to bool for JS
    foreach ($data as &$row) {
        $row['is_locked'] = (bool)(int)$row['is_locked'];
    }
    unset($row);

    jsonResponse(['data' => $data ?: []]);
}

function handlePost(PDO $db, string $action): never
{
    $isAdmin = (($_SESSION['role'] ?? '') === 'admin');
    $data    = json_decode(file_get_contents('php://input'), associative: true) ?? [];

    // --- Simpan & kunci seluruh absensi hari ini (admin only) ---
    if ($action === 'lock') {
        if (!$isAdmin) {
            jsonResponse(data: ['error' => 'Hanya admin yang dapat menyimpan/mengunci absensi.'], statusCode: 403);
        }

        if (isDateLocked($db)) {
            jsonResponse(['success' => false, 'error' => 'Absensi hari ini sudah dikunci.'], 409);
        }

        // Set is_locked = 1 untuk semua absensi hari ini
        $db->prepare(
            'UPDATE attendance SET is_locked = 1 WHERE tanggal = CURDATE()'
        )->execute();

        // Catat di log
        $stmt = $db->prepare(
            'INSERT INTO attendance_lock_log (tanggal, locked_by) VALUES (CURDATE(), :uid)'
        );
        $stmt->execute([':uid' => (int)($_SESSION['user_id'] ?? 0)]);

        jsonResponse(['success' => true, 'message' => 'Absensi hari ini telah dikunci.']);
    }

    // --- Buka kunci (admin only) ---
    if ($action === 'unlock') {
        if (!$isAdmin) {
            jsonResponse(data: ['error' => 'Hanya admin yang dapat membuka kunci absensi'], statusCode: 403);
        }

        // Hapus lock log
        $db->prepare(
            'DELETE FROM attendance_lock_log WHERE tanggal = CURDATE()'
        )->execute();

        // Reset is_locked = 0
        $db->prepare(
            'UPDATE attendance SET is_locked = 0 WHERE tanggal = CURDATE()'
        )->execute();

        jsonResponse(['success' => true, 'message' => 'Kunci absensi hari ini telah dibuka.']);
    }

    // --- Tandai absensi per karyawan ---
    if ($action === 'attendance') {
        $empId  = inputInt($data, 'employee_id');
        $status = inputString($data, 'status', 'Absent');

        // Validasi nilai status
        if (!in_array($status, ['Present', 'Absent'], strict: true)) {
            jsonResponse(data: ['error' => 'Status tidak valid'], statusCode: 422);
        }

        // Cek apakah sudah dikunci — kecuali admin
        if (!$isAdmin && isDateLocked($db)) {
            jsonResponse(
                data: ['error' => 'Absensi hari ini sudah dikunci dan tidak dapat diubah.'],
                statusCode: 403
            );
        }

        $stmt = $db->prepare(<<<SQL
            INSERT INTO attendance (employee_id, tanggal, status)
            VALUES (:emp_id, CURDATE(), :status)
            ON DUPLICATE KEY UPDATE status = VALUES(status)
        SQL);
        $stmt->execute([':emp_id' => $empId, ':status' => $status]);
        jsonResponse(['success' => true]);
    }

    // --- Tambah karyawan baru — hanya admin ---
    if (!$isAdmin) {
        jsonResponse(data: ['error' => 'Hanya admin yang dapat menambah karyawan'], statusCode: 403);
    }

    $nama   = inputString($data, 'nama');
    $posisi = inputString($data, 'posisi');
    $no_hp  = inputString($data, 'no_hp');
    $alamat = inputString($data, 'alamat');

    if ($nama === '') {
        jsonResponse(data: ['error' => 'Nama tidak boleh kosong'], statusCode: 422);
    }

    $stmt = $db->prepare(<<<SQL
        INSERT INTO employees (nama, posisi, no_hp, alamat)
        VALUES (:nama, :posisi, :no_hp, :alamat)
    SQL);
    $stmt->execute([':nama' => $nama, ':posisi' => $posisi ?: null, ':no_hp' => $no_hp, ':alamat' => $alamat]);
    jsonResponse(['success' => true, 'id' => (int)$db->lastInsertId()], 201);
}

function handleDelete(PDO $db): never
{
    // Hapus karyawan — hanya admin
    if (($_SESSION['role'] ?? '') !== 'admin') {
        jsonResponse(data: ['error' => 'Hanya admin yang dapat menghapus karyawan'], statusCode: 403);
    }

    $id = inputInt($_GET, 'id');

    if ($id <= 0) {
        jsonResponse(data: ['error' => 'ID tidak valid'], statusCode: 422);
    }

    $stmt = $db->prepare('DELETE FROM employees WHERE id = :id');
    $stmt->execute([':id' => $id]);
    jsonResponse(['success' => true, 'affected' => $stmt->rowCount()]);
}
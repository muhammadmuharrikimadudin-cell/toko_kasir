<?php
declare(strict_types=1);
// employee.php — Modul 4: Employee Absence — PHP 8.3
session_start();
require_once 'config/database.php';
checkRole(['admin', 'karyawan']);

$pageTitle  = 'Employee Absence';
$activePage = 'employee';

// Fetch shop name for header display
$db = getDB();
try {
    $shopStmt = $db->query("SELECT nama_toko FROM shop_settings LIMIT 1");
    $shopName = $shopStmt ? ($shopStmt->fetchColumn() ?: 'Shop Name') : 'Shop Name';
} catch (PDOException $e) {
    $shopName = 'Shop Name';
}

include 'templates/header.php';
include 'templates/sidebar.php';

$userRole = $_SESSION['role'] ?? '';
?>

<!-- ============================================================
     EMPLOYEE ABSENCE PAGE
     ============================================================ -->
<div class="p-6">

    <!-- Page Header -->
    <div class="flex items-center justify-between mb-2">
        <div>
            <p class="text-xs text-gray-400 font-medium uppercase tracking-wider">⠿⠿ <?= htmlspecialchars($shopName) ?></p>
            <h1 class="text-2xl font-bold text-gray-800">Shop Name</h1>
        </div>
    </div>

    <!-- Employee Absence Card -->
    <div class="bg-white rounded-2xl shadow-sm p-6 mt-4">
        <div class="flex items-center justify-between mb-5">
            <div>
                <h2 class="text-lg font-bold text-blue-500">Employee Absence</h2>
                <p class="text-xs text-gray-400 mt-0.5">Absensi hari ini: <?= date('d F Y') ?></p>
            </div>
            <div class="flex items-center gap-2">
                <button onclick="openHistoryModal()"
                    class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 transition-all border border-gray-200">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Riwayat
                </button>
                <?php if ($userRole === 'admin'): ?>
                <button onclick="openAddModal()"
                    id="btn-add-employee"
                    class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold text-white transition-all"
                    style="background:#4DB9F2;">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Add Employee
                </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="rounded-l-xl pl-4" style="width:40px">No</th>
                        <th>Name</th>
                        <th style="width:130px">Posisi</th>
                        <th>No. Handphone</th>
                        <th>Address</th>
                        <th class="rounded-r-xl" style="text-align:center; width:200px">Detail</th>
                    </tr>
                </thead>
                <tbody id="employeeBody">
                    <tr>
                        <td colspan="6" class="text-center text-gray-400 py-10">Memuat...</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Attendance Action Bar (bottom of card) -->
        <div id="attendanceActionBar" class="mt-5 flex items-center justify-end gap-3">
            <!-- Rendered dynamically by JS based on lock status -->
        </div>
    </div>

</div>

<!-- ============================================================
     ADD EMPLOYEE MODAL
     ============================================================ -->
<div id="addEmpModal" class="modal-overlay hidden">
    <div class="modal-box max-w-md w-full mx-4">
        <div class="flex items-center justify-between mb-5">
            <h3 class="font-bold text-gray-800 text-lg">Tambah Karyawan</h3>
            <button onclick="closeAddModal()" class="text-gray-400 hover:text-gray-600 p-1 rounded-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="addEmpForm" onsubmit="submitEmployee(event)">
            <div class="space-y-4">
                <div>
                    <label class="text-sm font-medium text-gray-700 block mb-1">Nama Lengkap</label>
                    <input type="text" id="empNama" class="input-field" placeholder="Nama karyawan..." required>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700 block mb-1">Posisi / Jabatan</label>
                    <input type="text" id="empPosisi" class="input-field" placeholder="Kasir, Gudang, Kurir...">
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700 block mb-1">No. Handphone</label>
                    <input type="text" id="empNoHp" class="input-field" placeholder="08xxx..." required>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700 block mb-1">Alamat</label>
                    <textarea id="empAlamat" class="input-field" rows="2" placeholder="Alamat lengkap..." required></textarea>
                </div>
            </div>
            <div class="flex gap-3 mt-6">
                <button type="button" onclick="closeAddModal()"
                    class="flex-1 py-3 rounded-xl border-2 border-gray-200 text-gray-700 font-semibold text-sm hover:bg-gray-50 transition-all">
                    Batal
                </button>
                <button type="submit"
                    class="flex-1 py-3 rounded-xl text-white font-semibold text-sm transition-all"
                    style="background:#4DB9F2;">
                    Simpan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================
     HISTORY ABSENSI MODAL
     ============================================================ -->
<div id="historyModal" class="modal-overlay hidden">
    <div class="modal-box max-w-2xl w-full mx-4 max-h-[85vh] flex flex-col">
        <!-- Header -->
        <div class="flex items-center justify-between mb-4 flex-shrink-0">
            <h3 class="font-bold text-gray-800 text-lg">Riwayat Absensi</h3>
            <button onclick="closeHistoryModal()" class="text-gray-400 hover:text-gray-600 p-1 rounded-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <!-- Filter & Export Bar -->
        <div class="flex items-center gap-2 mb-4 flex-shrink-0 flex-wrap">
            <div class="flex items-center gap-2 flex-1 min-w-0">
                <label class="text-xs text-gray-500 whitespace-nowrap">Dari</label>
                <input type="date" id="histDateFrom"
                    class="input-field text-xs py-1.5 flex-1"
                    oninput="loadHistory()">
                <label class="text-xs text-gray-500 whitespace-nowrap">s/d</label>
                <input type="date" id="histDateTo"
                    class="input-field text-xs py-1.5 flex-1"
                    oninput="loadHistory()">
                <button onclick="resetHistoryFilter()"
                    class="text-xs text-gray-400 hover:text-gray-600 px-2 py-1.5 rounded-lg border border-gray-200 whitespace-nowrap">
                    Reset
                </button>
            </div>
            <button onclick="exportAttendanceExcel()"
                id="btn-export-excel"
                class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold text-white transition-all flex-shrink-0"
                style="background:linear-gradient(135deg,#22c55e,#16a34a);box-shadow:0 2px 8px rgba(34,197,94,.25)">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                Export Excel
            </button>
        </div>

        <!-- Table -->
        <div class="overflow-y-auto pr-1 pb-2 flex-1">
            <table class="data-table w-full">
                <thead class="sticky top-0 bg-white z-10">
                    <tr class="bg-gray-50">
                        <th class="pl-4 py-2 text-left text-xs font-semibold text-gray-500" style="width:110px">Tanggal</th>
                        <th class="py-2 text-left text-xs font-semibold text-gray-500">Karyawan</th>
                        <th class="py-2 text-left text-xs font-semibold text-gray-500" style="width:110px">Posisi</th>
                        <th class="pr-4 py-2 text-center text-xs font-semibold text-gray-500" style="width:90px">Status</th>
                    </tr>
                </thead>
                <tbody id="historyBody">
                    <tr><td colspan="4" class="text-center py-6 text-gray-400 text-sm">Memuat...</td></tr>
                </tbody>
            </table>
        </div>

        <!-- Footer info -->
        <div id="historyFooter" class="mt-3 pt-3 border-t border-gray-100 flex-shrink-0 text-xs text-gray-400 text-right"></div>
    </div>
</div>

<!-- ============================================================
     EMPLOYEE JAVASCRIPT
     ============================================================ -->
<style>
/* Locked attendance badge styles */
.badge-locked-present {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    border-radius: 9999px;
    background: #dcfce7;
    color: #16a34a;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .02em;
}
.badge-locked-absent {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    border-radius: 9999px;
    background: #fee2e2;
    color: #dc2626;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .02em;
}
.badge-locked-none {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    border-radius: 9999px;
    background: #f3f4f6;
    color: #9ca3af;
    font-size: 11px;
    font-weight: 600;
}
.save-attendance-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 9px 20px;
    border-radius: 12px;
    background: linear-gradient(135deg, #22c55e, #16a34a);
    color: #fff;
    font-size: 13px;
    font-weight: 700;
    border: none;
    cursor: pointer;
    box-shadow: 0 2px 8px rgba(34,197,94,.25);
    transition: transform .15s, box-shadow .15s;
}
.save-attendance-btn:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 4px 14px rgba(34,197,94,.35);
}
.save-attendance-btn:disabled {
    cursor: not-allowed;
    opacity: .7;
}
.unlock-attendance-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 9px 18px;
    border-radius: 12px;
    background: #fff;
    color: #f97316;
    font-size: 13px;
    font-weight: 700;
    border: 2px solid #fed7aa;
    cursor: pointer;
    transition: background .15s, border-color .15s;
}
.unlock-attendance-btn:hover {
    background: #fff7ed;
    border-color: #f97316;
}
.badge-day-locked {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 9px 16px;
    border-radius: 12px;
    background: #dcfce7;
    color: #15803d;
    font-size: 13px;
    font-weight: 700;
    border: 1.5px solid #bbf7d0;
    pointer-events: none;
}
</style>

<script>
const USER_ROLE = '<?= $userRole ?>';
const IS_ADMIN  = USER_ROLE === 'admin';

// Global lock state
let todayLocked = false;

/* -------------------------------------------------------
   Load employees + lock status on page load
------------------------------------------------------- */
async function initPage() {
    await checkLockStatus();
    await loadEmployees();
}

async function checkLockStatus() {
    try {
        const res = await apiFetch('api/employees.php?action=lock_status');
        todayLocked = res.locked === true;
    } catch (e) {
        todayLocked = false;
    }
    renderActionBar();
}

/* -------------------------------------------------------
   Render action bar (bottom of card)
------------------------------------------------------- */
function renderActionBar(allIndivLocked = false) {
    const bar = document.getElementById('attendanceActionBar');

    // Prioritas 1: global lock (admin sudah klik Simpan Absensi)
    if (todayLocked) {
        bar.innerHTML = `
            <span class="badge-day-locked">
                <svg style="width:15px;height:15px;flex-shrink:0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                </svg>
                Absensi Hari Ini Sudah Disimpan
            </span>
            ${IS_ADMIN ? `<button class="unlock-attendance-btn" onclick="unlockAttendance()" id="btn-unlock-attendance">
                <svg style="width:14px;height:14px;flex-shrink:0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 018 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/>
                </svg>
                Edit Absensi (Admin)
            </button>` : ''}
        `;
        return;
    }

    // Prioritas 2: semua karyawan sudah diinput secara per-individu
    if (allIndivLocked) {
        bar.innerHTML = `
            <button class="save-attendance-btn" disabled style="opacity:.65;cursor:not-allowed;">
                <svg style="width:15px;height:15px;flex-shrink:0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                </svg>
                ✓ Absensi Hari Ini Sudah Selesai &amp; Terkunci
            </button>
            ${IS_ADMIN ? `<button class="unlock-attendance-btn" onclick="unlockAttendance()" id="btn-unlock-attendance">
                <svg style="width:14px;height:14px;flex-shrink:0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 018 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/>
                </svg>
                Edit Absensi (Admin)
            </button>` : ''}
        `;
        return;
    }

    // Prioritas 3: belum semua terkunci
    if (IS_ADMIN) {
        bar.innerHTML = `
            <button class="save-attendance-btn" onclick="saveAttendance()" id="btn-save-attendance">
                <svg style="width:15px;height:15px;flex-shrink:0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
                </svg>
                Simpan Absensi Hari Ini
            </button>
        `;
    } else {
        bar.innerHTML = `
            <span style="display:inline-flex;align-items:center;gap:6px;padding:8px 14px;border-radius:12px;
                         background:#f9fafb;color:#9ca3af;font-size:13px;font-weight:600;
                         border:1.5px dashed #d1d5db;cursor:default;">
                <svg style="width:14px;height:14px;flex-shrink:0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                Hanya Admin yang Dapat Menyimpan Absensi
            </span>
        `;
    }
}


/* -------------------------------------------------------
   Save (lock) today's attendance
------------------------------------------------------- */
async function saveAttendance() {
    const confirm = await Swal.fire({
        title: 'Simpan Absensi?',
        text: 'Setelah disimpan, absensi hari ini tidak dapat diubah lagi.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya, Simpan & Kunci',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#22c55e',
    });
    if (!confirm.isConfirmed) return;

    const btn = document.getElementById('btn-save-attendance');
    if (btn) { btn.disabled = true; btn.textContent = 'Menyimpan...'; }

    try {
        const res = await apiFetch('api/employees.php?action=lock', { method: 'POST', body: '{}' });
        if (res.success) {
            showToast('✓ Absensi hari ini berhasil disimpan & dikunci!', 'success');
            todayLocked = true;
            renderActionBar();
            loadEmployees(); // re-render rows as read-only
        } else {
            showToast(res.error || 'Gagal menyimpan absensi.', 'error');
            if (btn) btn.disabled = false;
        }
    } catch (e) {
        showToast('Gagal menyimpan absensi.', 'error');
        if (btn) { btn.disabled = false; btn.textContent = 'Simpan Absensi Hari Ini'; }
    }
}

/* -------------------------------------------------------
   Unlock today's attendance (admin only)
------------------------------------------------------- */
async function unlockAttendance() {
    const confirm = await Swal.fire({
        title: 'Buka Kunci Absensi?',
        text: 'Absensi hari ini akan dapat diedit kembali.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Buka Kunci',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#f97316',
    });
    if (!confirm.isConfirmed) return;

    try {
        const res = await apiFetch('api/employees.php?action=unlock', { method: 'POST', body: '{}' });
        if (res.success) {
            showToast('Kunci absensi berhasil dibuka.', 'success');
            todayLocked = false;
            renderActionBar();
            loadEmployees();
        } else {
            showToast(res.error || 'Gagal membuka kunci.', 'error');
        }
    } catch (e) {
        showToast('Gagal membuka kunci absensi.', 'error');
    }
}

/* -------------------------------------------------------
   Load & Render employees
------------------------------------------------------- */
async function loadEmployees() {
    try {
        const res = await apiFetch('api/employees.php');
        renderEmployees(res && res.data ? res.data : []);
    } catch(e) {
        console.error('loadEmployees error:', e);
        renderEmployees([]);
    }
}

function renderEmployees(employees) {
    const tbody = document.getElementById('employeeBody');
    if (!employees || employees.length === 0) {
        tbody.innerHTML = `<tr><td colspan="6" class="text-center text-gray-400 py-12">
            <p class="text-3xl mb-2">👥</p>
            <p>Belum ada data karyawan</p>
        </td></tr>`;
        renderActionBar(false);
        return;
    }

    // Cek apakah SEMUA karyawan sudah terkunci (per-individu)
    const allIndivLocked = employees.length > 0 && employees.every(e => e.is_locked === true);

    const lockIcon = `<svg style="width:12px;height:12px;flex-shrink:0" fill="none" stroke="currentColor" viewBox="0 0 24 24" title="Absensi dikunci">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
    </svg>`;

    tbody.innerHTML = employees.map((emp, i) => {
        const attStatus  = emp.attendance_status;
        const isEmpLocked = emp.is_locked === true || todayLocked;

        let detailCell;
        if (isEmpLocked) {
            // Tampilkan badge statis — tidak bisa diklik
            let badge;
            if (attStatus === 'Present') {
                badge = `<span class="badge-locked-present">✓ Absen Terkunci ${lockIcon}</span>`;
            } else if (attStatus === 'Absent') {
                badge = `<span class="badge-locked-absent">✗ Absent ${lockIcon}</span>`;
            } else {
                badge = `<span class="badge-locked-none">— Belum diisi ${lockIcon}</span>`;
            }
            detailCell = `
                <div class="flex items-center gap-2 justify-center" style="flex-wrap:nowrap">
                    ${IS_ADMIN ? `<button onclick="deleteEmployee(${emp.id}, '${emp.nama}')"
                        class="px-3 py-1.5 rounded-xl text-xs font-bold text-white bg-red-400 hover:bg-red-500 transition-all">
                        Delete
                    </button>` : ''}
                    ${badge}
                </div>`;
        } else {
            // Tampilkan tombol Present / Absent
            detailCell = `
                <div class="flex items-center gap-2 justify-center" style="flex-wrap:nowrap">
                    ${IS_ADMIN ? `<button onclick="deleteEmployee(${emp.id}, '${emp.nama}')"
                        class="px-3 py-1.5 rounded-xl text-xs font-bold text-white bg-red-400 hover:bg-red-500 transition-all">
                        Delete
                    </button>` : ''}
                    <button onclick="markAttendance(${emp.id}, 'Present')"
                        class="px-3 py-1.5 rounded-xl text-xs font-bold text-white transition-all ${attStatus === 'Present' ? 'bg-green-500' : 'bg-green-300 hover:bg-green-400'}">
                        Present
                    </button>
                    <button onclick="markAttendance(${emp.id}, 'Absent')"
                        class="px-3 py-1.5 rounded-xl text-xs font-bold text-white transition-all ${attStatus === 'Absent' ? 'bg-red-500' : 'bg-red-300 hover:bg-red-400'}">
                        Absent
                    </button>
                </div>`;
        }

        return `
        <tr>
            <td class="pl-4 font-semibold text-gray-500">${i + 1}.</td>
            <td>
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold text-white"
                         style="background:${['#4DB9F2','#22c55e','#f97316','#8b5cf6'][i%4]}">
                        ${emp.nama.charAt(0).toUpperCase()}
                    </div>
                    <span class="font-medium text-gray-800">${emp.nama}</span>
                </div>
            </td>
            <td>
                ${emp.posisi
                    ? `<span style="display:inline-block;padding:3px 9px;border-radius:9999px;font-size:11px;font-weight:700;background:#eff6ff;color:#2563eb;white-space:nowrap">${emp.posisi}</span>`
                    : `<span style="color:#d1d5db;font-size:12px">—</span>`
                }
            </td>
            <td class="text-gray-500">${emp.no_hp || '-'}</td>
            <td class="text-gray-500 max-w-xs truncate" title="${emp.alamat || ''}">${emp.alamat || '-'}</td>
            <td style="text-align:center">${detailCell}</td>
        </tr>`;
    }).join('');

    // Update action bar berdasarkan status all-locked
    renderActionBar(allIndivLocked);
}

/* -------------------------------------------------------
   Mark individual attendance
------------------------------------------------------- */
async function markAttendance(empId, status) {
    if (todayLocked && !IS_ADMIN) {
        showToast('Absensi sudah dikunci oleh admin dan tidak dapat diubah.', 'error');
        return;
    }
    try {
        const res = await apiFetch('api/employees.php?action=attendance', {
            method: 'POST',
            body: JSON.stringify({ employee_id: empId, status }),
        });
        if (res && res.success === false) {
            // Tampilkan pesan lock per-karyawan dari server
            showToast(res.message || 'Absensi sudah terkunci.', 'error');
        } else if (res && res.error) {
            showToast(res.error, 'error');
        } else {
            showToast(`Absensi: ${status}`);
            loadEmployees(); // re-render dengan status terkunci
        }
    } catch(e) { console.error('markAttendance error:', e); }
}

/* -------------------------------------------------------
   Delete employee
------------------------------------------------------- */
async function deleteEmployee(id, nama) {
    const confirm = await Swal.fire({
        title: 'Hapus Karyawan?',
        text: `Hapus data ${nama}?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, hapus',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#ef4444',
    });
    if (!confirm.isConfirmed) return;
    try {
        await apiFetch(`api/employees.php?id=${id}`, { method: 'DELETE' });
        showToast('Karyawan dihapus');
        loadEmployees();
    } catch(e) { console.error('deleteEmployee error:', e); }
}

/* -------------------------------------------------------
   Modals
------------------------------------------------------- */
function openAddModal() {
    document.getElementById('addEmpModal').classList.remove('hidden');
}
function closeAddModal() {
    document.getElementById('addEmpModal').classList.add('hidden');
    document.getElementById('addEmpForm').reset();
}
function openHistoryModal() {
    document.getElementById('historyModal').classList.remove('hidden');
    loadHistory();
}
function closeHistoryModal() {
    document.getElementById('historyModal').classList.add('hidden');
}
document.getElementById('addEmpModal').addEventListener('click', function(e) {
    if (e.target === this) closeAddModal();
});
document.getElementById('historyModal').addEventListener('click', function(e) {
    if (e.target === this) closeHistoryModal();
});

// Cache history data for export
let _historyData = [];

async function loadHistory() {
    const tbody  = document.getElementById('historyBody');
    const footer = document.getElementById('historyFooter');
    tbody.innerHTML = '<tr><td colspan="4" class="text-center py-6 text-gray-400 text-sm">Memuat...</td></tr>';
    if (footer) footer.textContent = '';

    const dateFrom = document.getElementById('histDateFrom')?.value || '';
    const dateTo   = document.getElementById('histDateTo')?.value   || '';
    let url = 'api/employees.php?action=history';
    if (dateFrom) url += `&date_from=${dateFrom}`;
    if (dateTo)   url += `&date_to=${dateTo}`;

    try {
        const res  = await apiFetch(url);
        const data = res.data || [];
        _historyData = data;

        if (!data.length) {
            tbody.innerHTML = '<tr><td colspan="4" class="text-center py-6 text-gray-400 text-sm">Tidak ada riwayat absensi.</td></tr>';
            return;
        }

        // Render rows — group by date visually
        let lastDate = null;
        tbody.innerHTML = data.map(row => {
            const isNewDate = row.tanggal !== lastDate;
            lastDate = row.tanggal;
            const statusClass = row.status === 'Present'
                ? 'text-green-600 bg-green-50'
                : 'text-red-500 bg-red-50';
            const dateCell = isNewDate
                ? `<td class="pl-4 py-2.5 text-sm font-semibold text-gray-700" rowspan="1">${row.tanggal}</td>`
                : `<td class="pl-4 py-2.5 text-xs text-gray-300">—</td>`;
            return `
                <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors ${isNewDate ? 'border-t-2 border-t-gray-100' : ''}">
                    ${dateCell}
                    <td class="py-2.5 font-medium text-sm text-gray-800">${row.nama}</td>
                    <td class="py-2.5 text-sm text-gray-500">${row.posisi || '<span class="text-gray-300">—</span>'}</td>
                    <td class="pr-4 py-2.5 text-center">
                        <span class="inline-block px-2.5 py-1 rounded-lg text-xs font-bold ${statusClass}">${row.status}</span>
                    </td>
                </tr>
            `;
        }).join('');

        // Footer summary
        const totalPresent = data.filter(r => r.status === 'Present').length;
        const totalAbsent  = data.filter(r => r.status === 'Absent').length;
        const days = [...new Set(data.map(r => r.tanggal))].length;
        if (footer) footer.innerHTML =
            `${data.length} catatan · ${days} hari · ` +
            `<span class="text-green-500 font-semibold">${totalPresent} Hadir</span> · ` +
            `<span class="text-red-400 font-semibold">${totalAbsent} Absen</span>`;

    } catch(e) {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center py-6 text-red-400 text-sm">Gagal memuat riwayat.</td></tr>';
    }
}

function resetHistoryFilter() {
    const from = document.getElementById('histDateFrom');
    const to   = document.getElementById('histDateTo');
    if (from) from.value = '';
    if (to)   to.value   = '';
    loadHistory();
}

/* -------------------------------------------------------
   Export Riwayat Absensi → Excel (SheetJS)
   Format: rekap per hari dengan summary baris
------------------------------------------------------- */
async function exportAttendanceExcel() {
    if (!_historyData.length) {
        showToast('Tidak ada data untuk diekspor.', 'warning');
        return;
    }

    // Tunggu SheetJS siap
    if (typeof XLSX === 'undefined') {
        showToast('Library Excel belum siap, coba lagi.', 'error');
        return;
    }

    const btn = document.getElementById('btn-export-excel');
    if (btn) { btn.disabled = true; btn.textContent = 'Mengekspor...'; }

    try {
        // ---- Susun data per hari ----
        const grouped = {};
        _historyData.forEach(row => {
            if (!grouped[row.tanggal]) grouped[row.tanggal] = [];
            grouped[row.tanggal].push(row);
        });

        const wb = XLSX.utils.book_new();

        // === Sheet 1: Detail Lengkap ===
        const detailRows = [
            ['Tanggal', 'Nama Karyawan', 'Posisi', 'Status'],
        ];
        _historyData.forEach(row => {
            detailRows.push([row.tanggal, row.nama, row.posisi || '-', row.status]);
        });
        const wsDetail = XLSX.utils.aoa_to_sheet(detailRows);
        // Style header
        wsDetail['!cols'] = [{wch:14},{wch:24},{wch:18},{wch:10}];
        XLSX.utils.book_append_sheet(wb, wsDetail, 'Detail Absensi');

        // === Sheet 2: Rekap Per Hari ===
        const rekapRows = [
            ['Tanggal', 'Total Karyawan', 'Hadir', 'Absen', '% Kehadiran'],
        ];
        Object.keys(grouped).sort().reverse().forEach(tgl => {
            const rows    = grouped[tgl];
            const hadir   = rows.filter(r => r.status === 'Present').length;
            const absen   = rows.filter(r => r.status === 'Absent').length;
            const total   = rows.length;
            const pct     = total > 0 ? ((hadir / total) * 100).toFixed(1) + '%' : '-';
            rekapRows.push([tgl, total, hadir, absen, pct]);
        });
        const wsRekap = XLSX.utils.aoa_to_sheet(rekapRows);
        wsRekap['!cols'] = [{wch:14},{wch:16},{wch:10},{wch:10},{wch:14}];
        XLSX.utils.book_append_sheet(wb, wsRekap, 'Rekap Per Hari');

        // === Nama file ===
        const dateFrom = document.getElementById('histDateFrom')?.value;
        const dateTo   = document.getElementById('histDateTo')?.value;
        let suffix = '';
        if (dateFrom && dateTo) suffix = `_${dateFrom}_sd_${dateTo}`;
        else if (dateFrom)     suffix = `_dari_${dateFrom}`;
        else if (dateTo)       suffix = `_sd_${dateTo}`;
        const filename = `Absensi_Karyawan${suffix}.xlsx`;

        XLSX.writeFile(wb, filename);
        showToast('File Excel berhasil diunduh!', 'success');
    } catch(err) {
        console.error('Export error:', err);
        showToast('Gagal mengekspor data.', 'error');
    } finally {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg> Export Excel`;
        }
    }
}

async function submitEmployee(e) {
    e.preventDefault();
    const data = {
        nama:   document.getElementById('empNama').value,
        posisi: document.getElementById('empPosisi').value,
        no_hp:  document.getElementById('empNoHp').value,
        alamat: document.getElementById('empAlamat').value,
    };
    try {
        await apiFetch('api/employees.php', {
            method: 'POST',
            body: JSON.stringify(data),
        });
        showToast('Karyawan berhasil ditambahkan');
        closeAddModal();
        loadEmployees();
    } catch(e) { console.error('submitEmployee error:', e); }
}

document.addEventListener('DOMContentLoaded', () => {
    initPage();
});
</script>

<!-- SheetJS untuk Export Excel -->
<script src="https://cdn.sheetjs.com/xlsx-latest/package/dist/xlsx.full.min.js"></script>

<?php include 'templates/footer.php'; ?>

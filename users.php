<?php
declare(strict_types=1);
// users.php — Modul Manajemen Akun
session_start();
require_once 'config/database.php';
checkRole(['admin']); // Hanya admin yang dapat mengelola akun

$pageTitle  = 'Manajemen Akun';
$activePage = 'users';

include 'templates/header.php';
include 'templates/sidebar.php';
?>

<!-- ============================================================
     MANAJEMEN AKUN PAGE
     ============================================================ -->
<div class="p-6">
    <!-- Page Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-bold text-gray-800">Manajemen Akun</h1>
            <p class="text-sm text-gray-500">Kelola akun admin, kasir, dan karyawan</p>
        </div>
        <button onclick="openModal()" class="flex items-center gap-2 px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-xl text-sm font-semibold transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Tambah Akun
        </button>
    </div>

    <!-- Data Table -->
    <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="data-table w-full">
                <thead>
                    <tr>
                        <th class="w-16">No</th>
                        <th>Nama Lengkap</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Tgl Terdaftar</th>
                        <th class="text-center rounded-r-xl">Aksi</th>
                    </tr>
                </thead>
                <tbody id="usersBody">
                    <tr><td colspan="6" class="text-center py-8 text-gray-400">Memuat data...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ============================================================
     USER MODAL (Add / Edit)
     ============================================================ -->
<div id="userModal" class="modal-overlay hidden">
    <div class="modal-box max-w-md w-full mx-4">
        <div class="flex items-center justify-between mb-5">
            <h3 id="modalTitle" class="text-lg font-bold text-gray-800">Tambah Akun</h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 transition-colors p-1 rounded-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="userForm" onsubmit="submitUser(event)">
            <input type="hidden" id="userId" value="0">
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Nama Lengkap</label>
                    <input type="text" id="userName" class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100 transition-all" required>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Username</label>
                    <input type="text" id="userUsername" class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100 transition-all" required autocomplete="username">
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Role (Hak Akses)</label>
                    <select id="userRole" class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100 transition-all bg-white" required>
                        <option value="admin">Admin</option>
                        <option value="cashier">Cashier</option>
                        <option value="karyawan">Karyawan</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Password</label>
                    <input type="password" id="userPassword" class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100 transition-all" autocomplete="new-password">
                    <p id="passwordHint" class="text-xs text-gray-400 mt-1 hidden">Kosongkan jika tidak ingin mengubah password.</p>
                </div>
            </div>
            
            <div class="mt-6 flex gap-3">
                <button type="button" onclick="closeModal()" class="flex-1 py-2.5 rounded-xl border border-gray-200 text-gray-700 font-semibold text-sm hover:bg-gray-50 transition-colors">Batal</button>
                <button type="submit" id="btnSubmitUser" class="flex-1 py-2.5 rounded-xl bg-blue-500 text-white font-semibold text-sm hover:bg-blue-600 transition-colors">Simpan Akun</button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================
     JAVASCRIPT
     ============================================================ -->
<script>
let users = [];

async function loadUsers() {
    try {
        const res = await apiFetch('api/users.php');
        users = res.data || [];
        renderUsers();
    } catch (e) {
        document.getElementById('usersBody').innerHTML = `<tr><td colspan="6" class="text-center py-8 text-red-500">Gagal memuat data</td></tr>`;
    }
}

function renderUsers() {
    const tbody = document.getElementById('usersBody');
    if (!users.length) {
        tbody.innerHTML = `<tr><td colspan="6" class="text-center py-8 text-gray-400">Tidak ada data akun.</td></tr>`;
        return;
    }
    
    tbody.innerHTML = users.map((u, i) => {
        let roleBadge = '';
        if (u.role === 'admin') roleBadge = '<span class="bg-purple-100 text-purple-600 px-2.5 py-1 rounded-lg text-xs font-bold uppercase tracking-wider">Admin</span>';
        else if (u.role === 'cashier') roleBadge = '<span class="bg-blue-100 text-blue-600 px-2.5 py-1 rounded-lg text-xs font-bold uppercase tracking-wider">Cashier</span>';
        else roleBadge = '<span class="bg-orange-100 text-orange-600 px-2.5 py-1 rounded-lg text-xs font-bold uppercase tracking-wider">Karyawan</span>';
        
        let statusBadge = '';
        if (u.status === 'approved') statusBadge = '<span class="text-green-500 font-semibold text-sm">Approved</span>';
        else if (u.status === 'rejected') statusBadge = '<span class="text-red-500 font-semibold text-sm">Rejected</span>';
        else statusBadge = '<span class="text-yellow-500 font-semibold text-sm">Pending</span>';

        let actionBtns = '';
        if (u.status === 'pending') {
            actionBtns += `
                <button onclick="changeStatus(${u.id}, 'approved')" class="p-1.5 text-green-500 hover:bg-green-50 rounded-lg transition-colors" title="Approve">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </button>
                <button onclick="changeStatus(${u.id}, 'rejected')" class="p-1.5 text-red-500 hover:bg-red-50 rounded-lg transition-colors" title="Reject">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            `;
        }
        
        return `
            <tr class="hover:bg-gray-50/50 transition-colors">
                <td class="text-center text-gray-500 text-sm">${i + 1}</td>
                <td class="font-medium text-gray-800">${u.name}</td>
                <td class="text-gray-500 text-sm">${u.username}</td>
                <td>${roleBadge}</td>
                <td>${statusBadge}</td>
                <td class="text-gray-500 text-sm">${u.created_at || '-'}</td>
                <td class="text-center px-4">
                    <div class="flex items-center justify-center gap-1">
                        ${actionBtns}
                        <button onclick="editUser(decodeURIComponent('${encodeURIComponent(JSON.stringify(u))}'))" class="p-1.5 text-blue-500 hover:bg-blue-50 rounded-lg transition-colors" title="Edit">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                        </button>
                        <button onclick="deleteUser(${u.id}, decodeURIComponent('${encodeURIComponent(u.name)}'))" class="p-1.5 text-red-400 hover:bg-red-50 hover:text-red-500 rounded-lg transition-colors" title="Hapus">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

function openModal() {
    document.getElementById('userModal').classList.remove('hidden');
    document.getElementById('modalTitle').textContent = 'Tambah Akun Baru';
    document.getElementById('userForm').reset();
    document.getElementById('userId').value = '0';
    document.getElementById('userPassword').required = true;
    document.getElementById('passwordHint').classList.add('hidden');
}

function closeModal() {
    document.getElementById('userModal').classList.add('hidden');
}

document.getElementById('userModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});

function editUser(uData) {
    let u = typeof uData === 'string' ? JSON.parse(uData) : uData;
    openModal();
    document.getElementById('modalTitle').textContent = 'Edit Akun';
    document.getElementById('userId').value = u.id;
    document.getElementById('userName').value = u.name;
    document.getElementById('userUsername').value = u.username;
    document.getElementById('userRole').value = u.role;
    document.getElementById('userPassword').required = false; // Optional saat edit
    document.getElementById('passwordHint').classList.remove('hidden');
}

async function submitUser(e) {
    e.preventDefault();
    const btn = document.getElementById('btnSubmitUser');
    btn.disabled = true;
    btn.textContent = 'Menyimpan...';

    const payload = {
        id: document.getElementById('userId').value,
        name: document.getElementById('userName').value,
        username: document.getElementById('userUsername').value,
        role: document.getElementById('userRole').value,
        password: document.getElementById('userPassword').value
    };

    try {
        const res = await apiFetch('api/users.php', {
            method: 'POST',
            body: JSON.stringify(payload)
        });
        if (res.success) {
            showToast(res.message);
            closeModal();
            loadUsers();
        }
    } catch (err) {
        // Error di-handle apiFetch
    } finally {
        btn.disabled = false;
        btn.textContent = 'Simpan Akun';
    }
}

function deleteUser(id, name) {
    Swal.fire({
        title: 'Hapus Akun?',
        html: `Yakin ingin menghapus <b>${name}</b>?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#94a3b8',
        confirmButtonText: 'Ya, Hapus!'
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                const res = await apiFetch('api/users.php', {
                    method: 'DELETE',
                    body: JSON.stringify({ id })
                });
                if (res.success) {
                    showToast(res.message);
                    loadUsers();
                }
            } catch(e) {}
        }
    });
}

async function changeStatus(id, status) {
    try {
        const res = await apiFetch('api/users.php?action=status', {
            method: 'POST',
            body: JSON.stringify({ id, status })
        });
        if (res.success) {
            showToast(res.message);
            loadUsers();
        }
    } catch(e) {}
}

document.addEventListener('DOMContentLoaded', () => {
    loadUsers();
});
</script>

<?php include 'templates/footer.php'; ?>

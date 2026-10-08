<?php
declare(strict_types=1);
session_start();
require_once 'config/database.php';
checkRole(['admin']);

$pageTitle  = 'Kategori Produk';
$activePage = 'inventory'; // Highlight the inventory icon in sidebar
include 'templates/header.php';
include 'templates/sidebar.php';
?>

<div class="p-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <a href="dashboard.php" class="text-gray-400 hover:text-blue-500 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                </a>
                <h1 class="text-xl font-bold text-gray-800">Kategori Produk</h1>
            </div>
            <p class="text-sm text-gray-500">Kelola kategori barang yang Anda jual</p>
        </div>
        <button onclick="openAddModal()" class="btn-primary flex items-center gap-2 text-sm shadow-lg shadow-blue-500/30">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Tambah Kategori
        </button>
    </div>

    <!-- Kategori Table -->
    <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
        <table class="data-table">
            <thead>
                <tr class="bg-gray-50">
                    <th class="pl-6 w-16">No</th>
                    <th>Nama Kategori</th>
                    <th>Jumlah Produk Terkait</th>
                    <th class="pr-6 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody id="categoryTableBody">
                <tr>
                    <td colspan="4" class="text-center text-gray-400 py-10">
                        <div class="animate-pulse flex justify-center">
                            <div class="h-4 bg-gray-100 rounded w-32"></div>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah Kategori -->
<div id="categoryModal" class="modal-overlay hidden">
    <div class="modal-box max-w-sm w-full mx-4">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-bold text-gray-800 text-lg">Tambah Kategori</h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 p-1 rounded-lg transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        
        <form id="categoryForm" onsubmit="saveCategory(event)">
            <div class="mb-5">
                <label class="block text-xs font-medium text-gray-500 mb-2">Nama Kategori</label>
                <input type="text" id="catName" class="input-field w-full text-sm font-medium" placeholder="Cth: Makanan Ringan" required autocomplete="off">
            </div>
            
            <div class="flex justify-end gap-2">
                <button type="button" onclick="closeModal()" class="px-4 py-2 rounded-xl border border-gray-200 text-sm font-medium text-gray-600 hover:bg-gray-50 transition-colors">Batal</button>
                <button type="submit" id="btnSave" class="px-4 py-2 rounded-xl bg-blue-500 text-white text-sm font-medium hover:bg-blue-600 transition-colors shadow-lg shadow-blue-500/30">Simpan</button>
            </div>
        </form>
    </div>
</div>

<?php include 'templates/footer.php'; ?>

<script>
async function loadCategories() {
    try {
        const res = await apiFetch('api/categories.php');
        const tbody = document.getElementById('categoryTableBody');
        
        if (!res.data || res.data.length === 0) {
            tbody.innerHTML = `<tr><td colspan="4" class="text-center text-gray-400 py-10">Belum ada kategori. Silakan tambah kategori baru.</td></tr>`;
            return;
        }

        tbody.innerHTML = res.data.map((c, i) => `
            <tr>
                <td class="pl-6 text-gray-500">${i + 1}</td>
                <td class="font-semibold text-gray-800">${c.name}</td>
                <td>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-blue-50 text-blue-700 text-xs font-medium">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        ${c.total_products} Item
                    </span>
                </td>
                <td class="pr-6 text-right">
                    <button onclick="deleteCategory(${c.id}, '${c.name}', ${c.total_products})" class="p-2 bg-red-50 text-red-500 hover:bg-red-500 hover:text-white rounded-lg transition-all" title="Hapus">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </td>
            </tr>
        `).join('');
    } catch (e) {
        document.getElementById('categoryTableBody').innerHTML = `<tr><td colspan="4" class="text-center text-red-400 py-10">Gagal memuat kategori</td></tr>`;
    }
}

function openAddModal() {
    document.getElementById('catName').value = '';
    document.getElementById('categoryModal').classList.remove('hidden');
    setTimeout(() => document.getElementById('catName').focus(), 100);
}

function closeModal() {
    document.getElementById('categoryModal').classList.add('hidden');
}

async function saveCategory(e) {
    e.preventDefault();
    const btn = document.getElementById('btnSave');
    const name = document.getElementById('catName').value.trim();
    
    btn.disabled = true;
    btn.textContent = 'Menyimpan...';

    try {
        const res = await apiFetch('api/categories.php', {
            method: 'POST',
            body: JSON.stringify({ name })
        });
        
        if (res.success) {
            showToast('Kategori berhasil ditambahkan', 'success');
            closeModal();
            loadCategories();
        } else {
            showToast(res.error || 'Gagal menyimpan', 'error');
        }
    } catch (err) {
        showToast('Koneksi gagal', 'error');
    } finally {
        btn.disabled = false;
        btn.textContent = 'Simpan';
    }
}

async function deleteCategory(id, name, totalProducts) {
    if (totalProducts > 0) {
        showToast(`Tidak bisa menghapus "${name}" karena sedang digunakan oleh ${totalProducts} produk.`, 'error');
        return;
    }
    
    if (!confirm(`Hapus kategori "${name}"?`)) return;

    try {
        const res = await apiFetch(`api/categories.php?id=${id}`, { method: 'DELETE' });
        if (res.success) {
            showToast('Kategori terhapus', 'success');
            loadCategories();
        } else {
            showToast(res.error || 'Gagal menghapus', 'error');
        }
    } catch (e) {
        showToast('Koneksi gagal', 'error');
    }
}

document.getElementById('categoryModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});

document.addEventListener('DOMContentLoaded', loadCategories);
</script>

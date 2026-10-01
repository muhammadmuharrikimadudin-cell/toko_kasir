<?php
declare(strict_types=1);
// purchases.php — Riwayat Restock & Pembelian Modal
session_start();
require_once 'config/database.php';
checkRole(['admin']);

$pageTitle  = 'Riwayat Restock (Pengeluaran)';
$activePage = 'purchases'; // Ini akan disorot jika ada di sidebar, tapi karena belum ada di sidebar kita biarkan
include 'templates/header.php';
include 'templates/sidebar.php';
?>

<!-- ============================================================
     RIWAYAT RESTOCK PAGE
     ============================================================ -->
<div class="p-6">

    <!-- Page Header -->
    <div class="mb-6 flex flex-wrap gap-4 items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-gray-800">Riwayat Restock & Pengeluaran</h1>
            <p class="text-sm text-gray-500">Lihat semua riwayat pengadaan produk (belanja modal)</p>
        </div>
        <a href="products.php" class="px-4 py-2 bg-blue-500 text-white text-sm font-semibold rounded-xl hover:bg-blue-600 transition-colors flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Restock Baru
        </a>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white rounded-2xl shadow-sm p-4 mb-4 flex flex-wrap items-center gap-3">
        <!-- Search -->
        <div class="flex-1 min-w-48 flex items-center gap-2 bg-gray-100 rounded-xl px-4 py-2">
            <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input
                type="text"
                id="searchTrx"
                placeholder="Cari No. Pembelian atau catatan..."
                class="bg-transparent outline-none text-sm text-gray-700 placeholder-gray-400 flex-1"
                oninput="debounceSearch()"
            >
        </div>

        <!-- Export Buttons -->
        <button onclick="exportExcel()" class="flex items-center gap-2 px-4 py-2 bg-green-50 hover:bg-green-100 text-green-600 rounded-xl text-sm font-semibold transition-all">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Excel
        </button>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="pl-5">No. Pembelian</th>
                        <th>Tanggal</th>
                        <th>Catatan / Produk</th>
                        <th class="text-right pr-5">Total Pengeluaran (Rp)</th>
                    </tr>
                </thead>
                <tbody id="trxTableBody">
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

        <!-- Pagination -->
        <div class="flex items-center justify-between px-5 py-3 border-t border-gray-50 bg-gray-50/50">
            <p class="text-xs text-gray-400" id="paginationInfo">Memuat data...</p>
            <div class="flex gap-2" id="paginationBtns"></div>
        </div>
    </div>

</div>

<?php include 'templates/footer.php'; ?>

<!-- ============================================================
     JAVASCRIPT
     ============================================================ -->
<script>
let searchTimer = null;
let currentPage = 1;
let totalPages  = 1;
let allData     = [];

function debounceSearch() {
    clearTimeout(searchTimer);
    currentPage = 1;
    searchTimer = setTimeout(() => loadPurchases(), 300);
}

async function loadPurchases() {
    const search = document.getElementById('searchTrx').value;
    const tbody  = document.getElementById('trxTableBody');
    try {
        const res = await apiFetch(`/api/purchases.php?search=${encodeURIComponent(search)}&page=${currentPage}&limit=10`);
        allData    = res.data  ?? [];
        totalPages = res.pages ?? 1;
        renderTable(allData);
        renderPagination(res.total ?? 0, totalPages);
    } catch(e) {
        tbody.innerHTML = `<tr><td colspan="4" class="text-center text-red-400 py-10">Gagal memuat data</td></tr>`;
        document.getElementById('paginationInfo').textContent = '';
    }
}

function renderTable(rows) {
    const tbody = document.getElementById('trxTableBody');
    if (!rows || !rows.length) {
        tbody.innerHTML = `<tr><td colspan="4" class="text-center text-gray-400 py-12">
            <p class="text-3xl mb-2">📦</p>
            <p>Tidak ada riwayat pengadaan</p>
        </td></tr>`;
        return;
    }
    tbody.innerHTML = rows.map((t, i) => `
        <tr class="border-b border-gray-200 hover:bg-gray-50 transition-colors">
            <td class="pl-5 py-3 font-mono text-xs text-blue-600 font-semibold">${t.purchase_number}</td>
            <td class="text-gray-500 text-xs">${t.created_at ? t.created_at.slice(0,16).replace('T',' ') : '-'}</td>
            <td>
                <span class="font-medium text-sm text-gray-700 block max-w-sm leading-snug truncate" title="${t.notes || '-'}">
                    ${t.notes || '-'}
                </span>
            </td>
            <td class="font-bold text-red-500 text-right pr-5">
                -${formatRupiah(t.total_amount)}
            </td>
        </tr>
    `).join('');
}

function renderPagination(total, pages) {
    document.getElementById('paginationInfo').textContent =
        `Menampilkan ${allData.length} dari ${total} catatan`;

    const container = document.getElementById('paginationBtns');
    container.innerHTML = '';

    for (let p = 1; p <= pages; p++) {
        const btn = document.createElement('button');
        btn.textContent = p;
        btn.className = `w-8 h-8 rounded-lg text-sm font-semibold transition-all ` +
            (p === currentPage
                ? 'text-white'
                : 'bg-gray-100 text-gray-600 hover:bg-gray-200');
        if (p === currentPage) btn.style.background = '#4DB9F2';
        btn.onclick = () => { currentPage = p; loadPurchases(); };
        container.appendChild(btn);
    }
}

// ---- Export Excel ----
function exportExcel() {
    if (!allData.length) { showToast('Tidak ada data', 'warning'); return; }
    const rows = allData.map(t => ({
        'No Pembelian': t.purchase_number,
        'Tanggal': t.created_at?.slice(0,16) || '-',
        'Total Pengeluaran (Rp)': parseFloat(t.total_amount) || 0,
        'Catatan / Barang': t.notes,
    }));
    const ws  = XLSX.utils.json_to_sheet(rows);
    const wb  = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Pengeluaran');
    XLSX.writeFile(wb, 'Riwayat-Restock.xlsx');
    showToast('Excel berhasil diunduh');
}

// ---- Init ----
loadPurchases();
</script>

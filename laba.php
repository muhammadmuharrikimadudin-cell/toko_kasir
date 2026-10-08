<?php
declare(strict_types=1);
// laba.php — Riwayat Laba Kotor & Bersih per Transaksi
session_start();
require_once 'config/database.php';
checkRole(['admin']);

$pageTitle  = 'Laporan Laba';
$activePage = 'laba';
include 'templates/header.php';
include 'templates/sidebar.php';
?>

<!-- ============================================================
     LABA PAGE
     ============================================================ -->
<div class="p-6">

    <!-- Page Header -->
    <div class="mb-6 flex flex-wrap gap-4 items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="dashboard.php" class="w-9 h-9 rounded-xl bg-white shadow-sm border border-gray-100 flex items-center justify-center text-gray-500 hover:text-blue-500 hover:border-blue-300 transition-all" title="Kembali ke Dashboard">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div>
                <h1 class="text-xl font-bold text-gray-800">Laporan Laba Penjualan</h1>
                <p class="text-sm text-gray-500">Rincian Laba Kotor dan Laba Bersih per Transaksi</p>
            </div>
        </div>
        <button onclick="exportExcel()" class="flex items-center gap-2 px-4 py-2 bg-green-50 hover:bg-green-100 text-green-600 rounded-xl text-sm font-semibold transition-all">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Excel
        </button>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6" id="summaryCards">
        <div class="bg-white rounded-2xl shadow-sm p-5 animate-pulse">
            <div class="h-3 bg-gray-100 rounded w-1/2 mb-3"></div>
            <div class="h-7 bg-gray-100 rounded w-2/3"></div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm p-5 animate-pulse">
            <div class="h-3 bg-gray-100 rounded w-1/2 mb-3"></div>
            <div class="h-7 bg-gray-100 rounded w-2/3"></div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm p-5 animate-pulse">
            <div class="h-3 bg-gray-100 rounded w-1/2 mb-3"></div>
            <div class="h-7 bg-gray-100 rounded w-2/3"></div>
        </div>
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
                placeholder="Cari ID Transaksi..."
                class="bg-transparent outline-none text-sm text-gray-700 placeholder-gray-400 flex-1"
                oninput="debounceSearch()"
            >
        </div>

        <!-- Period Filter -->
        <div class="flex items-center gap-2">
            <button id="filterAll"   onclick="setFilter('')"       class="filter-btn active px-3 py-2 rounded-xl text-xs font-semibold transition-all bg-blue-500 text-white">Semua</button>
            <button id="filterToday" onclick="setFilter('today')"  class="filter-btn px-3 py-2 rounded-xl text-xs font-semibold transition-all bg-gray-100 text-gray-600 hover:bg-gray-200">Hari Ini</button>
            <button id="filterMonth" onclick="setFilter('month')"  class="filter-btn px-3 py-2 rounded-xl text-xs font-semibold transition-all bg-gray-100 text-gray-600 hover:bg-gray-200">Bulan Ini</button>
            <button id="filterYear"  onclick="setFilter('year')"   class="filter-btn px-3 py-2 rounded-xl text-xs font-semibold transition-all bg-gray-100 text-gray-600 hover:bg-gray-200">Tahun Ini</button>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="pl-5">ID Transaksi</th>
                        <th>Tanggal</th>
                        <th>Produk Terjual</th>
                        <th>Metode</th>
                        <th class="text-right">Laba Kotor (Rp)</th>
                        <th class="text-right pr-5">Laba Bersih (Rp)</th>
                    </tr>
                </thead>
                <tbody id="labaTableBody">
                    <tr>
                        <td colspan="6" class="text-center text-gray-400 py-10">
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
let searchTimer  = null;
let currentPage  = 1;
let totalPages   = 1;
let allData      = [];
let activeFilter = '';

function debounceSearch() {
    clearTimeout(searchTimer);
    currentPage = 1;
    searchTimer = setTimeout(() => loadLaba(), 300);
}

function setFilter(f) {
    activeFilter = f;
    currentPage  = 1;

    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.classList.remove('bg-blue-500', 'text-white');
        btn.classList.add('bg-gray-100', 'text-gray-600', 'hover:bg-gray-200');
    });
    const ids = { '': 'filterAll', 'today': 'filterToday', 'month': 'filterMonth', 'year': 'filterYear' };
    const active = document.getElementById(ids[f]);
    if (active) {
        active.classList.add('bg-blue-500', 'text-white');
        active.classList.remove('bg-gray-100', 'text-gray-600', 'hover:bg-gray-200');
    }
    loadLaba();
}

function methodBadge(m) {
    const normalized = (m || '').toLowerCase();
    const labelMap = { cash: 'Cash', qris: 'QRIS', transfer: 'Transfer' };
    const classMap  = { cash: 'badge badge-cash', qris: 'badge badge-qris', transfer: 'badge badge-transfer' };
    const label = labelMap[normalized] || m || '-';
    const cls   = classMap[normalized] || 'badge badge-cash';
    return `<span class="${cls}">${label}</span>`;
}

async function loadLaba() {
    const search = encodeURIComponent(document.getElementById('searchTrx').value);
    try {
        const res = await apiFetch(`api/laba.php?search=${search}&filter=${activeFilter}&page=${currentPage}&limit=15`);
        allData    = res.data  ?? [];
        totalPages = res.pages ?? 1;

        renderSummary(res.summary ?? {});
        renderTable(allData);
        renderPagination(res.total ?? 0, totalPages);
    } catch(e) {
        document.getElementById('labaTableBody').innerHTML =
            `<tr><td colspan="6" class="text-center text-red-400 py-10">Gagal memuat data</td></tr>`;
        document.getElementById('paginationInfo').textContent = '';
    }
}

function renderSummary(s) {
    document.getElementById('summaryCards').innerHTML = `
        <div class="bg-white rounded-2xl shadow-sm p-5 border-l-4 border-blue-400">
            <p class="text-xs text-gray-500 mb-1">Total Laba Kotor</p>
            <p class="text-xl font-bold text-blue-600">${formatRupiah(s.total_gross ?? 0)}</p>
            <p class="text-xs text-gray-400 mt-1">Omzet penjualan</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm p-5 border-l-4 border-red-400">
            <p class="text-xs text-gray-500 mb-1">Total HPP (Modal)</p>
            <p class="text-xl font-bold text-red-500">${formatRupiah(s.total_hpp ?? 0)}</p>
            <p class="text-xs text-gray-400 mt-1">Harga pokok penjualan</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm p-5 border-l-4 border-green-400">
            <p class="text-xs text-gray-500 mb-1">Total Laba Bersih</p>
            <p class="text-xl font-bold text-green-600">${formatRupiah(s.total_net ?? 0)}</p>
            <p class="text-xs text-gray-400 mt-1">Setelah dikurangi modal</p>
        </div>
    `;
}

function renderTable(rows) {
    const tbody = document.getElementById('labaTableBody');
    if (!rows || !rows.length) {
        tbody.innerHTML = `<tr><td colspan="6" class="text-center text-gray-400 py-12">
            <p class="text-3xl mb-2">💰</p>
            <p>Tidak ada data laba</p>
        </td></tr>`;
        return;
    }
    tbody.innerHTML = rows.map(t => {
        const net    = parseFloat(t.laba_bersih ?? 0);
        const gross  = parseFloat(t.laba_kotor ?? 0);
        const netCls = net >= 0 ? 'text-green-600' : 'text-red-500';
        return `
        <tr class="border-b border-gray-200 hover:bg-gray-50 transition-colors">
            <td class="pl-5 py-3 font-mono text-xs text-blue-600 font-semibold">${t.kode_transaksi}</td>
            <td class="text-gray-500 text-xs whitespace-nowrap">${t.created_at ? t.created_at.slice(0,16).replace('T',' ') : '-'}</td>
            <td>
                <span class="font-medium text-sm text-gray-700 block max-w-sm leading-snug truncate" title="${t.produk || '-'}">
                    ${t.produk || '-'}
                </span>
            </td>
            <td>${methodBadge(t.metode_bayar)}</td>
            <td class="text-right font-semibold text-gray-800">${formatRupiah(gross)}</td>
            <td class="text-right font-bold pr-5 ${netCls}">${formatRupiah(net)}</td>
        </tr>`;
    }).join('');
}

function renderPagination(total, pages) {
    document.getElementById('paginationInfo').textContent =
        `Menampilkan ${allData.length} dari ${total} transaksi`;

    const container = document.getElementById('paginationBtns');
    container.innerHTML = '';
    for (let p = 1; p <= pages; p++) {
        const btn = document.createElement('button');
        btn.textContent = p;
        btn.className = `w-8 h-8 rounded-lg text-sm font-semibold transition-all ` +
            (p === currentPage ? 'text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200');
        if (p === currentPage) btn.style.background = '#4DB9F2';
        btn.onclick = () => { currentPage = p; loadLaba(); };
        container.appendChild(btn);
    }
}

// ---- Export Excel ----
function exportExcel() {
    if (!allData.length) { showToast('Tidak ada data', 'warning'); return; }
    const rows = allData.map(t => ({
        'ID Transaksi'       : t.kode_transaksi,
        'Tanggal'            : t.created_at?.slice(0,16) || '-',
        'Produk'             : t.produk || '-',
        'Metode Bayar'       : t.metode_bayar || '-',
        'Laba Kotor (Rp)'    : parseFloat(t.laba_kotor)  || 0,
        'HPP / Modal (Rp)'   : parseFloat(t.hpp)         || 0,
        'Laba Bersih (Rp)'   : parseFloat(t.laba_bersih) || 0,
    }));
    const ws = XLSX.utils.json_to_sheet(rows);
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Laporan Laba');
    XLSX.writeFile(wb, 'Laporan-Laba.xlsx');
    showToast('Excel berhasil diunduh');
}

// ---- Init — baca filter dari URL ?filter= ----
(function() {
    const params = new URLSearchParams(window.location.search);
    const f = params.get('filter') || '';
    setFilter(f);
})();
</script>

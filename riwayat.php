<?php
declare(strict_types=1);
// riwayat.php — Modul 3: Riwayat Transaksi — PHP 8.3
session_start();
require_once 'config/database.php';
checkRole(['admin', 'cashier']);

$pageTitle  = 'Riwayat Transaksi';
$activePage = 'riwayat';
include 'templates/header.php';
include 'templates/sidebar.php';
?>

<!-- ============================================================
     RIWAYAT TRANSAKSI PAGE
     ============================================================ -->
<div class="p-6">

    <!-- Page Header -->
    <div class="mb-6">
        <h1 class="text-xl font-bold text-gray-800">Riwayat Transaksi</h1>
        <p class="text-sm text-gray-500">Lihat Semua Transaksi Penjualan</p>
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

        <!-- Export Buttons -->
        <button onclick="exportPDF()" class="flex items-center gap-2 px-4 py-2 bg-red-50 hover:bg-red-100 text-red-600 rounded-xl text-sm font-semibold transition-all">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
            PDF
        </button>
        <button onclick="exportExcel()" class="flex items-center gap-2 px-4 py-2 bg-green-50 hover:bg-green-100 text-green-600 rounded-xl text-sm font-semibold transition-all">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Excel
        </button>
    </div>

    <!-- Transactions Table -->
    <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="pl-5">Product</th>
                        <th>ID Transaksi</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th class="pr-5"></th>
                    </tr>
                </thead>
                <tbody id="trxTableBody">
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

<!-- ============================================================
     DETAIL TRANSACTION MODAL
     ============================================================ -->
<div id="detailModal" class="modal-overlay hidden">
    <div class="modal-box max-w-lg w-full mx-4">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-bold text-gray-800 text-lg">Detail Transaksi</h3>
            <button onclick="closeDetail()" class="text-gray-400 hover:text-gray-600 p-1 rounded-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div id="detailContent" class="text-sm space-y-2 text-gray-600">
            <!-- Loaded via JS -->
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?>

<!-- ============================================================
     RIWAYAT JAVASCRIPT
     PENTING: Script ini di-load SETELAH footer.php agar
     fungsi apiFetch() dan formatRupiah() dari footer sudah tersedia.
     ============================================================ -->
<script>
let searchTimer = null;
let currentPage = 1;
let totalPages  = 1;
let allData     = []; // for export

function debounceSearch() {
    clearTimeout(searchTimer);
    currentPage = 1;
    searchTimer = setTimeout(() => loadTransactions(), 300);
}

async function loadTransactions() {
    const search = document.getElementById('searchTrx').value;
    const tbody  = document.getElementById('trxTableBody');
    try {
        const res = await apiFetch(`api/transactions.php?search=${encodeURIComponent(search)}&page=${currentPage}&limit=10`);
        allData    = res.data  ?? [];
        totalPages = res.pages ?? 1;
        renderTable(allData);
        renderPagination(res.total ?? 0, totalPages);
    } catch(e) {
        tbody.innerHTML = `<tr><td colspan="6" class="text-center text-red-400 py-10">Gagal memuat data transaksi</td></tr>`;
        document.getElementById('paginationInfo').textContent = '';
    }
}

function methodBadge(m) {
    // DB menyimpan nilai lowercase: cash, qris, transfer
    const normalized = (m || '').toLowerCase();
    const labelMap = { cash: 'Cash', qris: 'QRIS', transfer: 'Transfer' };
    const classMap  = { cash: 'badge badge-cash', qris: 'badge badge-qris', transfer: 'badge badge-transfer' };
    const label = labelMap[normalized] || m || '-';
    const cls   = classMap[normalized] || 'badge badge-cash';
    return `<span class="${cls}">${label}</span>`;
}

function statusBadge(s) {
    const map = { 'Pending':'badge badge-pending', 'Complete':'badge badge-complete', 'Cancel':'badge badge-cancel' };
    return `<span class="${map[s] || 'badge badge-pending'}">${s}</span>`;
}

const productEmojis = ['🍜','🥤','✨','📱','🛒','🍿','💊','📦'];

function renderTable(rows) {
    const tbody = document.getElementById('trxTableBody');
    if (!rows || !rows.length) {
        tbody.innerHTML = `<tr><td colspan="6" class="text-center text-gray-400 py-12">
            <p class="text-3xl mb-2">📋</p>
            <p>Tidak ada transaksi</p>
        </td></tr>`;
        return;
    }
    tbody.innerHTML = rows.map((t, i) => `
        <tr class="border-b border-gray-200 hover:bg-gray-50 transition-colors">
            <td class="pl-5 py-3">
                <span class="font-medium text-sm text-gray-700 block max-w-xs leading-snug">${t.product_nama || 'Mix Items'}</span>
            </td>
            <td class="font-mono text-xs text-blue-600 font-semibold">${t.kode_transaksi}</td>
            <td class="text-gray-500 text-xs">${t.created_at ? t.created_at.slice(0,16).replace('T',' ') : '-'}</td>
            <td class="font-bold text-gray-800">${formatRupiah(t.total_bayar)}</td>
            <td>${methodBadge(t.metode_bayar)}</td>
            <td class="pr-5">
                <button onclick='showDetail(${JSON.stringify(t)})' class="w-7 h-7 rounded-full bg-gray-100 hover:bg-blue-100 hover:text-blue-600 flex items-center justify-center transition-all">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>
            </td>
        </tr>
    `).join('');
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
            (p === currentPage
                ? 'text-white'
                : 'bg-gray-100 text-gray-600 hover:bg-gray-200');
        if (p === currentPage) btn.style.background = '#4DB9F2';
        btn.onclick = () => { currentPage = p; loadTransactions(); };
        container.appendChild(btn);
    }
}

function showDetail(t) {
    const labaBersih = t.total_bayar - (t.total_hpp || 0);
    
    let itemsHtml = '';
    try {
        if (t.items_json) {
            const items = JSON.parse(t.items_json);
            itemsHtml = items.map(i => `
                <div class="flex justify-between border-b border-gray-100 py-1">
                    <div>
                        <p class="text-xs font-semibold text-gray-700">${i.name}</p>
                        <p class="text-[10px] text-gray-400">${i.qty} x ${formatRupiah(i.subtotal/i.qty)}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs font-bold text-gray-800">${formatRupiah(i.subtotal)}</p>
                        <p class="text-[10px] text-green-600">HPP FIFO: ${formatRupiah(i.hpp * i.qty)}</p>
                    </div>
                </div>
            `).join('');
        }
    } catch(e) {}

    document.getElementById('detailContent').innerHTML = `
        <div class="grid grid-cols-2 gap-3 mb-3">
            <div><p class="text-gray-400 text-xs">ID Transaksi</p><p class="font-bold font-mono text-blue-600">${t.kode_transaksi}</p></div>
            <div><p class="text-gray-400 text-xs">Status</p>${statusBadge(t.status)}</div>
            <div><p class="text-gray-400 text-xs">Tanggal</p><p class="font-medium">${t.created_at?.slice(0,16) || '-'}</p></div>
            <div><p class="text-gray-400 text-xs">Metode Bayar</p>${methodBadge(t.metode_bayar)}</div>
            <div><p class="text-gray-400 text-xs">Sub Total</p><p class="font-medium">${formatRupiah(t.total)}</p></div>
            <div><p class="text-gray-400 text-xs">Diskon</p><p class="font-medium">${t.diskon ?? 0}%</p></div>
            <div class="col-span-2 bg-blue-50 rounded-xl p-3">
                <div class="flex justify-between items-center mb-1">
                    <p class="text-gray-500 text-xs">Total Bayar</p>
                    <p class="font-bold text-lg text-blue-600">${formatRupiah(t.total_bayar)}</p>
                </div>
                <div class="flex justify-between items-center">
                    <p class="text-gray-500 text-xs">Laba Bersih</p>
                    <p class="font-bold text-sm text-green-600">${formatRupiah(labaBersih)}</p>
                </div>
            </div>
        </div>
        <div class="border-t border-gray-100 pt-2 max-h-48 overflow-y-auto pr-1">
            <p class="text-xs font-bold text-gray-500 mb-2">Item Details & HPP</p>
            ${itemsHtml}
        </div>
    `;
    document.getElementById('detailModal').classList.remove('hidden');
}

function closeDetail() {
    document.getElementById('detailModal').classList.add('hidden');
}
document.getElementById('detailModal').addEventListener('click', function(e) {
    if (e.target === this) closeDetail();
});

// ---- Export PDF ----
function exportPDF() {
    if (!allData.length) { showToast('Tidak ada data', 'warning'); return; }
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();

    doc.setFontSize(16);
    doc.setFont('helvetica', 'bold');
    doc.text('Riwayat Transaksi — Kasir Toko', 14, 15);

    doc.setFontSize(9);
    doc.setFont('helvetica', 'normal');
    doc.text('Dicetak: ' + new Date().toLocaleString('id-ID'), 14, 22);

    let y = 30;
    const cols = ['ID Transaksi', 'Tanggal', 'Total', 'Metode', 'Status'];
    const widths = [45, 45, 30, 25, 25];
    doc.setFillColor(77, 185, 242);
    doc.setTextColor(255,255,255);
    doc.setFont('helvetica', 'bold');
    doc.rect(14, y-5, 182, 8, 'F');
    let x = 14;
    cols.forEach((c, i) => { doc.text(c, x+2, y); x += widths[i]; });

    doc.setFont('helvetica', 'normal');
    doc.setTextColor(50,50,50);
    allData.forEach((t, idx) => {
        y += 8;
        if (y > 270) { doc.addPage(); y = 20; }
        if (idx % 2 === 0) { doc.setFillColor(248,250,252); doc.rect(14, y-5, 182, 8, 'F'); }
        x = 14;
        const row = [t.kode_transaksi, t.created_at?.slice(0,16)||'-', 'Rp '+parseInt(t.total_bayar).toLocaleString('id-ID'), t.metode_bayar, t.status];
        row.forEach((v, i) => { doc.text(String(v), x+2, y); x += widths[i]; });
    });

    doc.save('Riwayat-Transaksi.pdf');
    showToast('PDF berhasil diunduh');
}

// ---- Export Excel ----
function exportExcel() {
    if (!allData.length) { showToast('Tidak ada data', 'warning'); return; }
    const rows = allData.map(t => ({
        'ID Transaksi': t.kode_transaksi,
        'Tanggal': t.created_at?.slice(0,16) || '-',
        'Sub Total': t.total,
        'Diskon (%)': t.diskon ?? 0,
        'Total Bayar': t.total_bayar,
        'Metode Bayar': t.metode_bayar,
        'Status': t.status,
    }));
    const ws  = XLSX.utils.json_to_sheet(rows);
    const wb  = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Transaksi');
    XLSX.writeFile(wb, 'Riwayat-Transaksi.xlsx');
    showToast('Excel berhasil diunduh');
}

// ---- Init ----
loadTransactions();
</script>

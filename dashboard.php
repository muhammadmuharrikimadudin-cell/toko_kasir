<?php
declare(strict_types=1);
// dashboard.php — Modul 1: Dashboard & Statistik — PHP 8.3
session_start();
require_once 'config/database.php';
checkRole(['admin']);

$pageTitle  = 'Dashboard';
$activePage = 'dashboard';
include 'templates/header.php';
include 'templates/sidebar.php';
?>

<!-- ============================================================
     DASHBOARD PAGE
     ============================================================ -->
<div class="p-6">

    <!-- Page Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-bold text-gray-800">Dashboard</h1>
            <p class="text-sm text-gray-500">Ringkasan aktivitas toko hari ini</p>
        </div>
        <div class="flex items-center gap-3">
            <!-- Tab System: General / Inventory / Cashier -->
            <div class="flex bg-gray-200 rounded-xl p-1 gap-1" id="dashTabGroup">
                <button class="tab-btn active" data-tab="general" onclick="switchTab('general')">Umum</button>
                <button class="tab-btn" data-tab="inventory" onclick="switchTab('inventory')">Inventaris</button>
                <button class="tab-btn" data-tab="cashier" onclick="switchTab('cashier')">Kasir</button>
            </div>
            <!-- Year selector -->
            <select id="yearSelect" class="input-field w-28 text-sm" onchange="loadDashboard()">
                <?php for ($y = date('Y'); $y >= date('Y') - 4; $y--): ?>
                <option value="<?= $y ?>" <?= $y == date('Y') ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
        </div>
    </div>

    <!-- ===== TAB: GENERAL ===== -->
    <div id="tab-general" class="tab-content">
        <!-- Stat Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6" id="statsGeneral">
            <!-- Loaded via JS -->
            <?php for ($i = 0; $i < 4; $i++): ?>
            <div class="stat-card animate-pulse">
                <div class="h-4 bg-gray-100 rounded w-2/3 mb-3"></div>
                <div class="h-7 bg-gray-100 rounded w-full mb-2"></div>
                <div class="h-3 bg-gray-100 rounded w-1/2"></div>
            </div>
            <?php endfor; ?>
        </div>

        <!-- Charts Row -->
        <div class="bg-white rounded-2xl shadow-sm p-5 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-semibold text-gray-800">Aktivitas Penjualan Harian</h2>
            </div>
            <div class="chart-container" style="height:200px">
                <canvas id="dailyChart"></canvas>
            </div>
        </div>

        <!-- Tables Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">
            <!-- Latest Transaction -->
            <div class="bg-white rounded-2xl shadow-sm p-5">
                <h2 class="font-semibold text-gray-800 mb-4">Transaksi Terakhir</h2>
                <div class="overflow-x-auto">
                    <table class="data-table" id="latestTrxTable">
                        <thead>
                            <tr>
                                <th>ID Transaksi</th>
                                <th>Waktu</th>
                                <th>Jumlah</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="latestTrxBody">
                            <tr><td colspan="4" class="text-center text-gray-400 py-4">Memuat...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Popular Product -->
            <div class="bg-white rounded-2xl shadow-sm p-5">
                <h2 class="font-semibold text-gray-800 mb-4">Produk Populer</h2>
                <div class="overflow-x-auto">
                    <table class="data-table" id="popularTable">
                        <thead>
                            <tr>
                                <th>Kode Produk</th>
                                <th>Nama Barang</th>
                                <th>Stok</th>
                            </tr>
                        </thead>
                        <tbody id="popularBody">
                            <tr><td colspan="3" class="text-center text-gray-400 py-4">Memuat...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Stock Receipt / Issued -->
        <div class="bg-white rounded-2xl shadow-sm p-5">
            <h2 class="font-semibold text-gray-800 mb-4">Penerimaan / Pengeluaran Stok</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Nama Barang</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody id="stockLogBody">
                    <tr><td colspan="3" class="text-center text-gray-400 py-4">Memuat...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ===== TAB: INVENTORY ===== -->
    <div id="tab-inventory" class="tab-content hidden">
        <!-- Stat Cards Inventory -->
        <div class="grid grid-cols-2 lg:grid-cols-3 gap-4 mb-6" id="statsInventory">
            <!-- Loaded via JS -->
            <?php for ($i = 0; $i < 6; $i++): ?>
            <div class="stat-card animate-pulse">
                <div class="h-4 bg-gray-100 rounded w-1/2 mb-3"></div>
                <div class="h-7 bg-gray-100 rounded w-full mb-2"></div>
                <div class="h-3 bg-gray-100 rounded w-2/3"></div>
            </div>
            <?php endfor; ?>
        </div>

        <!-- Yearly Receive Spending Chart -->
        <div class="bg-white rounded-2xl shadow-sm p-5 mb-4">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-semibold text-gray-800">Pengeluaran Tahunan</h2>
                <span class="text-xs text-gray-400">Tahunan ▾</span>
            </div>
            <div class="chart-container" style="height:220px">
                <canvas id="yearlyChart"></canvas>
            </div>
        </div>

        <!-- Monthly Receive Spending Chart -->
        <div class="bg-white rounded-2xl shadow-sm p-5">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-semibold text-gray-800">Pengeluaran Bulanan</h2>
                <span class="text-xs text-gray-400">Bulanan ▾</span>
            </div>
            <div class="chart-container" style="height:220px">
                <canvas id="monthlyChart"></canvas>
            </div>
        </div>
    </div>

    <!-- ===== TAB: CASHIER ===== -->
    <div id="tab-cashier" class="tab-content hidden">
        <!-- Stat Cards Cashier -->
        <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-6" id="statsCashier">
            <?php for ($i = 0; $i < 6; $i++): ?>
            <div class="stat-card animate-pulse">
                <div class="h-4 bg-gray-100 rounded w-1/2 mb-3"></div>
                <div class="h-7 bg-gray-100 rounded w-full mb-2"></div>
            </div>
            <?php endfor; ?>
        </div>

        <!-- Interactive Selling Activity Chart -->
        <div class="bg-white rounded-2xl shadow-sm p-5 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-semibold text-gray-800" id="sellChartTitle">Aktivitas Penjualan (Harian)</h2>
                <select id="sellChartFilter" class="input-field text-sm w-32 py-1 px-2 h-auto" onchange="updateSellChart()">
                    <option value="daily_sales">Harian</option>
                    <option value="monthly_sales">Bulanan</option>
                    <option value="yearly_sales">Tahunan</option>
                </select>
            </div>
            <div class="chart-container" style="height:250px">
                <canvas id="unifiedSellChart"></canvas>
            </div>
        </div>
    </div>

</div><!-- /p-6 -->

<!-- ============================================================
     LOW STOCK MODAL
     ============================================================ -->
<div id="lowStockModal" class="modal-overlay hidden">
    <div class="modal-box max-w-lg w-full mx-4">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center">
                    <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
                <h3 class="font-bold text-gray-800 text-lg">Barang Stok Menipis</h3>
            </div>
            <button onclick="document.getElementById('lowStockModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600 p-1 rounded-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <p class="text-xs text-gray-400 mb-4">Daftar barang yang stoknya di bawah minimum — perlu segera di-restok.</p>
        <div class="overflow-y-auto max-h-96">
            <table class="data-table">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="pl-4">Nama Barang</th>
                        <th class="text-center">Stok Saat Ini</th>
                        <th class="pr-4 text-center">Stok Minimum</th>
                    </tr>
                </thead>
                <tbody id="lowStockTableBody">
                    <tr><td colspan="3" class="text-center text-gray-400 py-6">Memuat...</td></tr>
                </tbody>
            </table>
        </div>
        <div class="mt-4 flex justify-end">
            <a href="products.php" class="px-4 py-2 bg-blue-500 text-white text-sm font-medium rounded-xl hover:bg-blue-600 transition-colors">Kelola Stok di Inventaris</a>
        </div>
    </div>
</div>

<!-- ============================================================
     DASHBOARD JAVASCRIPT
     ============================================================ -->
<script>
// ---- Chart instances ----
let dailyChartInst    = null;
let yearlyChartInst   = null;
let monthlyChartInst  = null;
let unifiedSellChartInst = null;
let sellChartData = null;

let currentTab = 'general';

// ---- Tab switching ----
function switchTab(tab) {
    currentTab = tab;
    document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    document.getElementById('tab-' + tab).classList.remove('hidden');
    document.querySelector(`.tab-btn[data-tab="${tab}"]`).classList.add('active');
    loadDashboard();
}

// ---- Status badge ----
function statusBadge(s) {
    const map = {
        'Pending':  'badge badge-pending',
        'Complete': 'badge badge-complete',
        'Cancel':   'badge badge-cancel',
    };
    return `<span class="${map[s] || 'badge badge-pending'}">${s}</span>`;
}

// ---- Payment badge ----
function payBadge(m) {
    const map = {
        'QRIS':     'badge badge-qris',
        'Cash':     'badge badge-cash',
        'Transfer': 'badge badge-transfer',
    };
    return `<span class="${map[m] || 'badge badge-cash'}">${m}</span>`;
}

// ---- Trend indicator ----
function trendUp(pct, label) {
    return `<span class="flex items-center gap-1 text-xs text-green-500 mt-2">
        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
        </svg>
        ${pct}% Naik dari ${label}
    </span>`;
}

// ---- Icon map for stat cards ----
const statIcons = {
    blue:   `<div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center"><svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>`,
    green:  `<div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center"><svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>`,
    yellow: `<div class="w-10 h-10 rounded-full bg-yellow-100 flex items-center justify-center"><svg class="w-5 h-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg></div>`,
    red:    `<div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center"><svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg></div>`,
    purple: `<div class="w-10 h-10 rounded-full bg-purple-100 flex items-center justify-center"><svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg></div>`,
};

function statCard(label, value, trend, icon, extra='', link='') {
    const linkHtml = link ? `<div class="mt-3 pt-2 border-t border-gray-50 flex justify-end"><a href="${link}" class="text-xs text-blue-500 hover:text-blue-600 font-medium flex items-center gap-1 transition-colors">Lihat Detail <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></a></div>` : '';
    
    return `<div class="stat-card flex flex-col h-full">
        <div class="flex items-start justify-between flex-1">
            <div>
                <p class="text-xs text-gray-500 mb-1">${label}</p>
                <p class="text-lg font-bold text-gray-800">${value}</p>
                ${trend}
            </div>
            ${statIcons[icon] || statIcons.blue}
        </div>
        ${extra}
        ${linkHtml}
    </div>`;
}

function stockAlertCard(count) {
    const hasAlert = parseInt(count) > 0;
    const trend = `<span class="flex items-center gap-1 text-xs ${hasAlert ? 'text-red-400' : 'text-green-400'} mt-2">
        <span class="${hasAlert ? 'notif-dot' : ''}"></span>
        ${hasAlert ? 'Perlu restok segera' : 'Semua stok aman'}
    </span>`;
    const linkHtml = hasAlert
        ? `<div class="mt-3 pt-2 border-t border-gray-50 flex justify-end">
            <button onclick="showLowStockModal()" class="text-xs text-red-500 hover:text-red-600 font-medium flex items-center gap-1 transition-colors">
                Lihat Detail
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </button>
          </div>`
        : '';
    return `<div class="stat-card flex flex-col h-full">
        <div class="flex items-start justify-between flex-1">
            <div>
                <p class="text-xs text-gray-500 mb-1">Peringatan Stok</p>
                <p class="text-lg font-bold text-gray-800">${count} Barang</p>
                ${trend}
            </div>
            ${statIcons.red}
        </div>
        ${linkHtml}
    </div>`;
}

function showLowStockModal() {
    const list = window._lowStockList || [];
    const tbody = document.getElementById('lowStockTableBody');
    if (!list.length) {
        tbody.innerHTML = `<tr><td colspan="3" class="text-center text-green-500 py-6">✅ Semua stok aman!</td></tr>`;
    } else {
        tbody.innerHTML = list.map(item => {
            const isVeryLow = parseInt(item.stock) === 0;
            return `<tr class="border-b border-gray-50">
                <td class="pl-4 font-medium text-gray-800 py-2.5">
                    ${isVeryLow ? '<span class="inline-block w-2 h-2 rounded-full bg-red-500 mr-1.5"></span>' : '<span class="inline-block w-2 h-2 rounded-full bg-orange-400 mr-1.5"></span>'}
                    ${item.name}
                </td>
                <td class="text-center">
                    <span class="font-bold ${isVeryLow ? 'text-red-500' : 'text-orange-500'}">${item.stock}</span>
                    <span class="text-gray-400 text-xs"> pcs</span>
                </td>
                <td class="pr-4 text-center text-gray-500">${item.min_stock} pcs</td>
            </tr>`;
        }).join('');
    }
    document.getElementById('lowStockModal').classList.remove('hidden');
}

document.getElementById('lowStockModal').addEventListener('click', function(e) {
    if (e.target === this) this.classList.add('hidden');
});

// ---- Chart helpers ----
function makeLineChart(id, labels, data, color, fill = false) {
    const ctx = document.getElementById(id);
    if (!ctx) return null;
    return new Chart(ctx, {
        type: 'line',
        data: {
            labels,
            datasets: [{
                data,
                borderColor: color,
                backgroundColor: fill ? color + '22' : 'transparent',
                borderWidth: 2,
                pointRadius: 3,
                pointBackgroundColor: color,
                fill,
                tension: 0.4,
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false }, tooltip: {
                callbacks: { label: ctx => ' ' + formatRupiah(ctx.raw) }
            }},
            scales: {
                x: { grid: { display: false }, ticks: { font: { size: 10 } } },
                y: {
                    grid: { color: '#f1f5f9' },
                    beginAtZero: true,
                    ticks: {
                        font: { size: 10 },
                        callback: v => {
                            if (v >= 1e9) return 'Rp' + (v / 1e9).toLocaleString('id-ID', { maximumFractionDigits: 1 }) + 'M';
                            if (v >= 1e6) return 'Rp' + (v / 1e6).toLocaleString('id-ID', { maximumFractionDigits: 1 }) + 'jt';
                            if (v >= 1e3) return 'Rp' + (v / 1e3).toFixed(0) + 'rb';
                            return 'Rp' + v;
                        }
                    }
                },
            }
        }
    });
}

// ---- Update Unified Chart ----
function updateSellChart() {
    if (!sellChartData || !unifiedSellChartInst) return;
    const filter = document.getElementById('sellChartFilter').value;
    const titleEl = document.getElementById('sellChartTitle');
    
    let chartSrc = sellChartData[filter];
    if (!chartSrc) return;
    
    if (filter === 'daily_sales') {
        titleEl.textContent = 'Aktivitas Penjualan (Harian)';
        unifiedSellChartInst.data.labels = chartSrc.labels.map(d => d.length > 5 ? d.slice(5) : d);
    } else if (filter === 'monthly_sales') {
        titleEl.textContent = 'Aktivitas Penjualan (Bulanan)';
        unifiedSellChartInst.data.labels = chartSrc.labels;
    } else if (filter === 'yearly_sales') {
        titleEl.textContent = 'Aktivitas Penjualan (Tahunan)';
        unifiedSellChartInst.data.labels = chartSrc.labels;
    }
    
    unifiedSellChartInst.data.datasets[0].data = chartSrc.data;
    unifiedSellChartInst.update();
}

// ---- Main Load ----
async function loadDashboard() {
    try {
        const year = document.getElementById('yearSelect')?.value || new Date().getFullYear();
        const res  = await apiFetch(`api/dashboard.php?year=${year}`);
        if (!res || !res.stats) throw new Error("Invalid response");
        const s    = res.stats;
        const c    = res.chart;

        // ---- GENERAL TAB ----
        document.getElementById('statsGeneral').innerHTML = [
            statCard('Laba Kotor Hari Ini',  formatRupiah(s.today_gross),    '', 'blue',   '', 'laba.php?filter=today'),
            statCard('Laba Bersih Hari Ini', formatRupiah(s.today_net),      '', 'green',  '', 'laba.php?filter=today'),
            statCard('Total Pengeluaran',    formatRupiah(s.today_spending), '', 'yellow', '', 'purchases.php'),
            stockAlertCard(s.stock_alert),
        ].join('');

        // Daily Chart
        if (dailyChartInst) { dailyChartInst.destroy(); }
        dailyChartInst = makeLineChart('dailyChart',
            c.daily_sales.labels.map(d => d.length > 5 ? d.slice(5) : d),
            c.daily_sales.data, '#22c55e', true);

        // Latest Transactions
        const tbody = document.getElementById('latestTrxBody');
        tbody.innerHTML = (res.latest_transactions && res.latest_transactions.length) ? res.latest_transactions.map(t => `
            <tr>
                <td class="font-mono text-xs text-blue-600">${t.kode_transaksi}</td>
                <td class="text-gray-500">${t.created_at ? 'Hari ini · ' + t.created_at.slice(11,16) : '-'}</td>
                <td class="font-semibold">${formatRupiah(t.total_bayar)}</td>
                <td>${statusBadge(t.status)}</td>
            </tr>`).join('')
        : '<tr><td colspan="4" class="text-center text-gray-400 py-6">Belum ada transaksi</td></tr>';

        // Popular Products
        const pbody = document.getElementById('popularBody');
        pbody.innerHTML = (res.popular_products && res.popular_products.length) ? res.popular_products.map(p => `
            <tr>
                <td class="font-mono text-xs text-gray-500">${p.kode}</td>
                <td class="font-medium">${p.nama}</td>
                <td>${p.stok} <span class="text-gray-400 text-xs">Pcs</span></td>
            </tr>`).join('')
        : '<tr><td colspan="3" class="text-center text-gray-400 py-6">-</td></tr>';

        // Stock Log
        const slBody = document.getElementById('stockLogBody');
        slBody.innerHTML = (res.stock_log && res.stock_log.length) ? res.stock_log.map(l => `
            <tr>
                <td class="text-gray-500 text-xs">${l.created_at ? l.created_at.slice(0,16) : '-'}</td>
                <td>
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-lg bg-gray-100 flex items-center justify-center text-sm">📦</div>
                        <div>
                            <p class="font-medium text-xs">${l.nama}</p>
                            <p class="text-gray-400 text-xs">${l.keterangan || ''}</p>
                        </div>
                    </div>
                </td>
                <td><span class="text-xs font-bold ${l.tipe === 'Masuk' ? 'text-green-500' : 'text-red-500'}">${l.qty} Pcs</span></td>
            </tr>`).join('')
        : '<tr><td colspan="3" class="text-center text-gray-400 py-6">-</td></tr>';

        // ---- INVENTORY TAB ----
        const inv = s.inv;
        document.getElementById('statsInventory').innerHTML = [
            statCard('Barang',              inv.total_item || 0,     '', 'blue', '', 'products.php'),
            statCard('Kategori',          inv.total_category || 0,   '', 'yellow', '', 'categories.php'),
            statCard('Total Pengeluaran', formatRupiah(s.total_received), '', 'red', '', 'purchases.php'),
        ].join('');

        if (yearlyChartInst)  yearlyChartInst.destroy();
        if (monthlyChartInst) monthlyChartInst.destroy();
        // Grafik Pengeluaran Tahunan — dari restock stock_logs
        yearlyChartInst  = makeLineChart('yearlyChart',  c.yearly_spend.labels, c.yearly_spend.data, '#ef4444', true);
        // Grafik Pengeluaran Bulanan — sama sumber, tampilan bar per bulan
        monthlyChartInst = makeLineChart('monthlyChart', c.yearly_spend.labels, c.yearly_spend.data, '#f97316', true);

        // ---- CASHIER TAB ----
        const cs = s.cashier;
        document.getElementById('statsCashier').innerHTML = [
            statCard('Laba Kotor Hari Ini',           formatRupiah(cs.today_gross   || 0), '', 'blue',   '', 'laba.php?filter=today'),
            statCard('Laba Bersih Hari Ini',          formatRupiah(cs.today_net     || 0), '', 'green',  '', 'laba.php?filter=today'),
            statCard('Laba Kotor Bulan Ini',          formatRupiah(cs.monthly_gross || 0), '', 'blue',   '', 'laba.php?filter=month'),
            statCard('Laba Bersih Bulan Ini',         formatRupiah(cs.monthly_net   || 0), '', 'green',  '', 'laba.php?filter=month'),
            statCard('Laba Kotor Tahunan',            formatRupiah(cs.yearly_gross  || 0), '', 'yellow', '', 'laba.php?filter=year'),
            statCard('Laba Bersih Penjualan Tahunan', formatRupiah(cs.yearly_net    || 0), '', 'purple', '', 'laba.php?filter=year'),
        ].join('');

        // Low stock data
        window._lowStockList = res.low_stock_list || [];

        sellChartData = c;
        if (unifiedSellChartInst) unifiedSellChartInst.destroy();
        
        const filter = document.getElementById('sellChartFilter')?.value || 'daily_sales';
        let initLabels = c[filter]?.labels || [];
        if (filter === 'daily_sales') {
            initLabels = initLabels.map(d => d.length > 5 ? d.slice(5) : d);
        }
        let initData = c[filter]?.data || [];

        unifiedSellChartInst = makeLineChart('unifiedSellChart', initLabels, initData, '#22c55e', true);

    } catch (e) {
        console.error('Dashboard load error:', e);
        showToast('Gagal memuat data dashboard', 'error');
        // Stop pulse on failure
        document.querySelectorAll('.animate-pulse').forEach(el => el.classList.remove('animate-pulse'));
    }
}

document.addEventListener('DOMContentLoaded', () => {
    loadDashboard();
});
</script>

<?php include 'templates/footer.php'; ?>

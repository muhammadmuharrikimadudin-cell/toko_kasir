<?php
declare(strict_types=1);
// products.php — Halaman Manajemen Produk / Inventaris
session_start();
require_once 'config/database.php';
checkRole(['admin']);

$pageTitle  = 'Inventaris Produk';
$activePage = 'products';
include 'templates/header.php';
include 'templates/sidebar.php';
?>

<!-- ============================================================
     INVENTARIS / PRODUCTS PAGE
     ============================================================ -->
<div class="p-6 flex flex-col h-screen">

    <!-- Header -->
    <div class="flex items-center justify-between mb-5">
        <div>
            <h1 class="text-xl font-bold text-gray-800">Inventaris Produk</h1>
            <p class="text-sm text-gray-500">Kelola data barang, harga modal, dan harga jual</p>
        </div>
        <button onclick="openModal()" id="btnTambah"
            class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-white text-sm font-semibold shadow transition-all hover:opacity-90"
            style="background:#2563EB;">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Tambah Produk
        </button>
    </div>

    <!-- Search + Stats Bar -->
    <div class="flex items-center gap-3 mb-4">
        <div class="bg-white rounded-xl shadow-sm p-3 flex items-center gap-2 flex-1">
            <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input type="text" id="searchInput" placeholder="Cari nama barang atau barcode..."
                class="flex-1 outline-none text-sm text-gray-700 placeholder-gray-400"
                oninput="debounceSearch()">
        </div>
        <div class="bg-white rounded-xl shadow-sm px-4 py-3 text-sm text-gray-600">
            Total: <span id="totalCount" class="font-bold text-blue-600">-</span> produk
        </div>
    </div>

    <!-- Product Table -->
    <div class="bg-white rounded-2xl shadow-sm flex-1 overflow-hidden flex flex-col">
        <div class="overflow-x-auto flex-1 overflow-y-auto">
            <table class="data-table w-full">
                <thead class="sticky top-0 bg-white z-10">
                    <tr>
                        <th class="w-12">#</th>
                        <th>Nama Barang</th>
                        <th>Kategori</th>
                        <th>Barcode</th>
                        <th>Stok</th>
                        <th>Harga Beli (Modal)</th>
                        <th>Harga Jual</th>
                        <th>Margin</th>
                        <th class="w-24">Aksi</th>
                    </tr>
                </thead>
                <tbody id="productTableBody">
                    <tr>
                        <td colspan="9" class="text-center py-12">
                            <div class="flex flex-col items-center text-gray-400">
                                <svg class="w-10 h-10 mb-2 animate-spin text-blue-300" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                </svg>
                                <p class="text-sm">Memuat data...</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ============================================================
     MODAL TAMBAH / EDIT PRODUK
     ============================================================ -->
<div id="productModal" class="modal-overlay hidden">
    <div class="modal-box max-w-xl w-full mx-4 max-h-[95vh] flex flex-col">
        <div class="flex items-center justify-between mb-5 flex-shrink-0">
            <h3 class="text-lg font-bold text-gray-800" id="modalTitle">Tambah Produk</h3>
            <button type="button" onclick="closeModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <form id="productForm" onsubmit="saveProduct(event)" class="flex flex-col min-h-0">
            <input type="hidden" id="prodId">
            <div class="grid grid-cols-2 gap-4 overflow-y-auto pr-2 pb-4">

                <!-- Nama Barang -->
                <div class="col-span-2">
                    <label class="block text-xs font-medium text-gray-500 mb-1">Nama Barang <span class="text-red-400">*</span></label>
                    <input type="text" id="prodNama" class="input-field w-full" placeholder="cth: Aqua 600ml" required>
                </div>

                <!-- Kategori -->
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Kategori <span class="text-red-400">*</span></label>
                    <select id="prodKategori" class="input-field w-full">
                        <option value="Makanan">Makanan</option>
                        <option value="Minuman">Minuman</option>
                        <option value="Snack">Snack</option>
                        <option value="Sembako">Sembako</option>
                        <option value="Skincare">Skincare</option>
                        <option value="Elektronik">Elektronik</option>
                        <option value="Obat-obatan">Obat-obatan</option>
                        <option value="Lainnya" selected>Lainnya</option>
                    </select>
                </div>

                <!-- Barcode -->
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Barcode / Kode Produk</label>
                    <input type="text" id="prodBarcode" class="input-field w-full font-mono" placeholder="Kosongkan = auto-generate">
                </div>

                <!-- Stok -->
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1" id="stokLabel">Stok Awal <span class="text-red-400">*</span></label>
                    <div class="flex gap-2">
                        <input type="number" id="prodStok" class="input-field w-full" placeholder="0" min="0" required>
                        <!-- Tombol Restock: hanya tampil saat mode Edit -->
                        <button type="button" id="btnRestockOpen" onclick="openRestockModal()"
                            class="hidden flex-shrink-0 flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold text-white transition-all hover:opacity-90"
                            style="background:#16a34a; white-space:nowrap">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Restock
                        </button>
                    </div>
                    <p id="stokHint" class="hidden mt-1 text-xs text-amber-600">⚠️ Stok tidak bisa diubah langsung. Gunakan tombol <strong>Restock</strong> untuk menambah stok.</p>
                </div>

                <!-- Min Stok -->
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Stok Minimum (Alert)</label>
                    <input type="number" id="prodMinStok" class="input-field w-full" placeholder="5" min="0" value="5">
                </div>

                <!-- Divider -->
                <div class="col-span-2 border-t border-gray-100 pt-1">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Harga</p>
                </div>

                <!-- Harga Beli Modal -->
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">
                        💰 Harga Beli / Modal (Grosir)
                        <span class="text-gray-400 font-normal">— untuk kalkulasi laba</span>
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400 text-sm pointer-events-none">Rp</span>
                        <input type="text" id="prodHargaBeli" class="input-field w-full pl-9" placeholder="0"
                            oninput="formatRupiahInputField(this)" required>
                    </div>
                </div>

                <!-- Harga Jual -->
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">
                        🏷️ Harga Jual Toko
                        <span class="text-gray-400 font-normal">— dipakai di Kasir</span>
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400 text-sm pointer-events-none">Rp</span>
                        <input type="text" id="prodHargaJual" class="input-field w-full pl-9" placeholder="0"
                            oninput="formatRupiahInputField(this); hitungMargin()" required>
                    </div>
                </div>

                <!-- Live Margin Preview -->
                <div class="col-span-2">
                    <div id="marginInfo" class="hidden bg-green-50 border border-green-200 rounded-xl px-4 py-2.5 flex items-center justify-between text-sm">
                        <span class="text-gray-600">Estimasi Margin per Unit:</span>
                        <span id="marginValue" class="font-bold text-green-600"></span>
                    </div>
                </div>

                <!-- Gambar Produk -->
                <div class="col-span-2">
                    <label class="block text-xs font-medium text-gray-500 mb-2">Gambar Produk</label>
                    <!-- Tab Toggle -->
                    <div class="flex gap-1 bg-gray-100 rounded-lg p-1 mb-3">
                        <button type="button" id="imgTabBtnUrl" onclick="switchImgTab('url')"
                            class="flex-1 text-xs py-1.5 rounded-md font-medium transition-all bg-white shadow text-gray-700">
                            🔗 Dari Link URL
                        </button>
                        <button type="button" id="imgTabBtnUpload" onclick="switchImgTab('upload')"
                            class="flex-1 text-xs py-1.5 rounded-md font-medium transition-all text-gray-500">
                            📁 Upload dari Laptop
                        </button>
                    </div>

                    <!-- Panel URL -->
                    <div id="imgPanelUrl">
                        <input type="url" id="prodImageUrl" class="input-field w-full text-sm"
                            placeholder="https://..." oninput="previewProductImage()">
                    </div>

                    <!-- Panel Upload -->
                    <div id="imgPanelUpload" class="hidden">
                        <div id="imgDropZone" onclick="document.getElementById('prodFileInput').click()"
                            class="border-2 border-dashed border-gray-300 rounded-xl p-4 text-center cursor-pointer hover:border-blue-400 hover:bg-blue-50 transition-all">
                            <div id="imgDropZoneContent">
                                <svg class="w-8 h-8 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                                </svg>
                                <p class="text-xs text-gray-500 font-medium">Klik atau seret gambar ke sini</p>
                                <p class="text-xs text-gray-400 mt-1">JPG, PNG, WEBP, GIF — maks. 5MB</p>
                            </div>
                            <input type="file" id="prodFileInput" accept="image/*" class="hidden" onchange="handleProdFileSelect(event)">
                        </div>
                        <!-- Upload Progress -->
                        <div id="prodUploadProgress" class="hidden mt-2">
                            <div class="flex items-center gap-2 text-xs text-blue-600">
                                <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                <span id="prodUploadText">Mengupload gambar...</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-1.5 mt-1">
                                <div id="prodUploadBar" class="bg-blue-500 h-1.5 rounded-full transition-all" style="width:0%"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Preview -->
                    <div class="flex items-center justify-center border border-dashed border-gray-300 rounded-xl mt-3 bg-gray-50 h-28 overflow-hidden">
                        <img id="prodImgPreview" src="" class="hidden max-h-full object-contain" alt="Preview"
                            onerror="this.classList.add('hidden'); document.getElementById('prodImgFallback').classList.remove('hidden');">
                        <div id="prodImgFallback" class="text-gray-400 flex flex-col items-center">
                            <svg class="w-7 h-7 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <span class="text-xs">Preview Gambar</span>
                        </div>
                    </div>
                </div>

            </div>

            <div class="mt-4 flex justify-end gap-3 flex-shrink-0 pt-4 border-t border-gray-100">
                <button type="button" onclick="closeModal()"
                    class="px-5 py-2.5 rounded-xl border border-gray-200 text-sm font-medium text-gray-600 hover:bg-gray-50">
                    Batal
                </button>
                <button type="submit" id="btnSaveProd"
                    class="px-5 py-2.5 rounded-xl text-white text-sm font-semibold transition-all hover:opacity-90"
                    style="background:#2563EB;">
                    Simpan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Konfirmasi Hapus -->
<div id="deleteModal" class="modal-overlay hidden">
    <div class="modal-box max-w-sm w-full mx-4 text-center">
        <div class="w-14 h-14 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-7 h-7 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
            </svg>
        </div>
        <h3 class="text-lg font-bold text-gray-800 mb-2">Hapus Produk?</h3>
        <p class="text-sm text-gray-500 mb-6">Produk <span id="deleteNama" class="font-semibold text-gray-700"></span> akan dihapus permanen. Tindakan ini tidak bisa dibatalkan.</p>
        <div class="flex gap-3">
            <button onclick="closeDeleteModal()" class="flex-1 py-2.5 rounded-xl border border-gray-200 text-sm font-medium text-gray-600 hover:bg-gray-50">Batal</button>
            <button onclick="confirmDelete()" id="btnConfirmDelete" class="flex-1 py-2.5 rounded-xl bg-red-500 text-white text-sm font-semibold hover:bg-red-600">Ya, Hapus</button>
        </div>
    </div>
</div>

<!-- ============================================================
     MODAL RESTOCK STOK
     ============================================================ -->
<div id="restockModal" class="modal-overlay hidden">
    <div class="modal-box max-w-md w-full mx-4">
        <!-- Header -->
        <div class="flex items-center justify-between mb-5">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl flex items-center justify-center" style="background:#dcfce7">
                    <svg class="w-5 h-5" style="color:#16a34a" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-bold text-gray-800">Restock Stok</h3>
                    <p class="text-xs text-gray-500" id="restockProductName">—</p>
                </div>
            </div>
            <button onclick="closeRestockModal()" class="text-gray-400 hover:text-gray-600 transition-colors p-1">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <!-- Info stok saat ini -->
        <div class="flex items-center gap-3 bg-gray-50 rounded-xl px-4 py-3 mb-5">
            <div class="text-center">
                <p class="text-xs text-gray-400 mb-0.5">Stok Saat Ini</p>
                <p class="text-xl font-bold text-gray-800" id="restockCurrentStock">0</p>
            </div>
            <div class="flex-1 flex items-center justify-center gap-2 text-gray-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                </svg>
            </div>
            <div class="text-center">
                <p class="text-xs text-gray-400 mb-0.5">Setelah Restock</p>
                <p class="text-xl font-bold text-green-600" id="restockAfterStock">0</p>
            </div>
        </div>

        <form id="restockForm" onsubmit="saveRestock(event)">
            <input type="hidden" id="restockProductId">
            <div class="space-y-4">

                <!-- Jumlah Restock -->
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">
                        📦 Jumlah Stok Tambahan <span class="text-red-400">*</span>
                    </label>
                    <input type="number" id="restockJumlah" class="input-field w-full text-lg font-semibold"
                        placeholder="0" min="1" required
                        oninput="updateRestockPreview()">
                </div>

                <!-- Harga Beli Per Unit -->
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">
                        💰 Harga Beli Modal (Per Unit) <span class="text-red-400">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400 text-sm pointer-events-none">Rp</span>
                        <input type="text" id="restockHargaBeli" class="input-field w-full pl-9"
                            placeholder="0" required
                            oninput="formatRupiahInputField(this); updateRestockPreview()">
                    </div>
                </div>

                <!-- Total Pengeluaran Preview -->
                <div id="restockTotalBox" class="hidden rounded-xl px-4 py-3" style="background:#f0fdf4; border:1px solid #bbf7d0">
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-gray-600">Total Pengeluaran Modal:</span>
                        <span id="restockTotalVal" class="text-base font-bold" style="color:#16a34a">Rp 0</span>
                    </div>
                </div>

                <!-- Catatan -->
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">
                        📝 Catatan / Supplier <span class="text-gray-400 font-normal">(opsional)</span>
                    </label>
                    <input type="text" id="restockCatatan" class="input-field w-full"
                        placeholder="cth: Supplier Toko ABC, pengiriman batch Okt 2026">
                </div>

            </div>

            <!-- Tombol Aksi -->
            <div class="mt-6 flex gap-3">
                <button type="button" onclick="closeRestockModal()"
                    class="flex-1 py-2.5 rounded-xl border border-gray-200 text-sm font-medium text-gray-600 hover:bg-gray-50 transition-colors">
                    Batal
                </button>
                <button type="submit" id="btnSaveRestock"
                    class="flex-1 py-2.5 rounded-xl text-white text-sm font-semibold transition-all hover:opacity-90"
                    style="background:#16a34a">
                    💾 Simpan Restock
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================
     JAVASCRIPT
     ============================================================ -->
<script>
let products   = [];
let searchTimer = null;
let deleteId    = null;
let restockProduct = null; // produk yang sedang di-restock

// ---- Load Products ----
async function loadProducts(search = '') {
    try {
        const url = `api/products.php?limit=200${search ? '&search=' + encodeURIComponent(search) : ''}`;
        const res = await apiFetch(url);
        products  = res.data || [];
        document.getElementById('totalCount').textContent = products.length;
        renderTable(products);
    } catch (e) {
        showToast('Gagal memuat data produk', 'error');
    }
}

function renderTable(prods) {
    const tbody = document.getElementById('productTableBody');
    if (!prods.length) {
        tbody.innerHTML = `<tr><td colspan="9" class="text-center py-12 text-gray-400">
            <p class="text-3xl mb-2">🔍</p><p class="text-sm">Tidak ada produk ditemukan</p>
        </td></tr>`;
        return;
    }

    tbody.innerHTML = prods.map((p, i) => {
        const beli   = parseFloat(p.harga_beli) || 0;
        const jual   = parseFloat(p.harga_jual) || 0;
        const margin = jual - beli;
        const pct    = beli > 0 ? ((margin / beli) * 100).toFixed(1) : '-';
        const stockClass = parseInt(p.stok) <= (parseInt(p.min_stock) || 5)
            ? 'text-red-500 font-bold' : 'text-gray-700';

        return `<tr class="hover:bg-gray-50/60 transition-colors">
            <td class="text-gray-400 text-center">${i + 1}</td>
            <td>
                <div class="flex items-center gap-2">
                    ${p.imageUrl
                        ? `<img src="${p.imageUrl}" class="w-9 h-9 rounded-lg object-cover flex-shrink-0" onerror="this.style.display='none'">`
                        : `<div class="w-9 h-9 rounded-lg bg-blue-50 flex items-center justify-center text-base flex-shrink-0">${getCategoryEmoji(p.kategori)}</div>`
                    }
                    <span class="font-medium text-gray-800">${p.nama}</span>
                </div>
            </td>
            <td><span class="px-2 py-0.5 bg-gray-100 text-gray-600 text-xs rounded-full">${p.kategori || '-'}</span></td>
            <td class="font-mono text-xs text-gray-500">${p.kode || '-'}</td>
            <td class="${stockClass} text-center">${p.stok}</td>
            <td class="text-gray-600">${formatRupiah(beli)}</td>
            <td class="font-semibold text-blue-600">${formatRupiah(jual)}</td>
            <td>
                ${beli > 0
                    ? `<span class="text-xs font-semibold ${margin >= 0 ? 'text-green-600' : 'text-red-500'}">${formatRupiah(margin)} <span class="text-gray-400 font-normal">(${pct}%)</span></span>`
                    : '<span class="text-gray-300 text-xs">-</span>'
                }
            </td>
            <td>
                <div class="flex items-center gap-1.5 justify-center">
                    <!-- Restock -->
                    <button onclick="openRestockModal(${p.id})"
                        class="p-1.5 rounded-lg transition-colors" style="background:#dcfce7; color:#16a34a" title="Restock Stok"
                        onmouseover="this.style.background='#bbf7d0'" onmouseout="this.style.background='#dcfce7'">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    </button>
                    <!-- Edit -->
                    <button onclick="openEditModal(${p.id})"
                        class="p-1.5 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 transition-colors" title="Edit">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                    </button>
                    <!-- Hapus -->
                    <button onclick="openDeleteModal(${p.id}, '${p.nama.replace(/'/g, "\\'")}')"
                        class="p-1.5 rounded-lg bg-red-50 text-red-500 hover:bg-red-100 transition-colors" title="Hapus">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </div>
            </td>
        </tr>`;
    }).join('');
}

// ---- Search ----
function debounceSearch() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => loadProducts(document.getElementById('searchInput').value), 300);
}

// ---- Modal Tambah ----
function openModal() {
    document.getElementById('modalTitle').textContent = 'Tambah Produk Baru';
    document.getElementById('prodId').value = '';
    document.getElementById('productForm').reset();
    document.getElementById('prodMinStok').value = '5';
    document.getElementById('marginInfo').classList.add('hidden');
    // Stok bisa diisi saat tambah baru
    const stokInput = document.getElementById('prodStok');
    stokInput.readOnly = false;
    stokInput.classList.remove('bg-gray-100', 'text-gray-500', 'cursor-not-allowed');
    stokInput.removeAttribute('title');
    document.getElementById('stokLabel').innerHTML = 'Stok Awal <span class="text-red-400">*</span>';
    document.getElementById('btnRestockOpen').classList.add('hidden');
    document.getElementById('stokHint').classList.add('hidden');
    // Reset gambar
    document.getElementById('prodImageUrl').value = '';
    previewProductImage();
    switchImgTab('url');
    resetDropZone();
    document.getElementById('productModal').classList.remove('hidden');
}

// ---- Modal Edit ----
function openEditModal(id) {
    const p = products.find(x => x.id == id);
    if (!p) return;

    document.getElementById('modalTitle').textContent = 'Edit Produk';
    document.getElementById('prodId').value         = p.id;
    document.getElementById('prodNama').value       = p.nama;
    document.getElementById('prodKategori').value   = p.kategori || 'Lainnya';
    document.getElementById('prodBarcode').value    = p.kode || '';
    document.getElementById('prodMinStok').value    = p.min_stock || 5;

    // Stok: readonly saat edit — harus pakai Restock
    const stokInput = document.getElementById('prodStok');
    stokInput.value    = p.stok;
    stokInput.readOnly = true;
    stokInput.classList.add('bg-gray-100', 'text-gray-500', 'cursor-not-allowed');
    stokInput.title    = 'Gunakan tombol Restock untuk menambah stok';
    document.getElementById('stokLabel').innerHTML = 'Stok Saat Ini <span class="text-gray-400 font-normal text-xs">(readonly)</span>';
    document.getElementById('btnRestockOpen').classList.remove('hidden');
    document.getElementById('stokHint').classList.remove('hidden');

    // Format Rupiah
    document.getElementById('prodHargaBeli').value = formatNumberInput(parseFloat(p.harga_beli) || 0);
    document.getElementById('prodHargaJual').value = formatNumberInput(parseFloat(p.harga_jual) || 0);

    // Gambar
    const imgUrl = p.imageUrl || '';
    document.getElementById('prodImageUrl').value = imgUrl;
    switchImgTab('url');
    previewProductImage();
    // Reset drop zone
    resetDropZone();

    hitungMargin();
    document.getElementById('productModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('productModal').classList.add('hidden');
}

// ---- Save (Tambah / Edit) ----
async function saveProduct(e) {
    e.preventDefault();
    const id  = document.getElementById('prodId').value;
    const btn = document.getElementById('btnSaveProd');
    btn.disabled = true;
    btn.textContent = 'Menyimpan...';

    // Saat edit, stok readonly — ambil nilai asli dari products array
    let stokVal;
    if (id) {
        const p = products.find(x => x.id == id);
        stokVal = p ? parseInt(p.stok) : 0;
    } else {
        stokVal = parseInt(document.getElementById('prodStok').value) || 0;
    }

    const payload = {
        id        : id ? parseInt(id) : undefined,
        nama      : document.getElementById('prodNama').value.trim(),
        kategori  : document.getElementById('prodKategori').value,
        kode      : document.getElementById('prodBarcode').value.trim(),
        stok      : stokVal,
        min_stock : parseInt(document.getElementById('prodMinStok').value) || 5,
        harga_beli: parseInputRupiah('prodHargaBeli'),
        harga_jual: parseInputRupiah('prodHargaJual'),
        imageUrl  : document.getElementById('prodImageUrl').value.trim() || null,
    };

    try {
        const method = id ? 'PUT' : 'POST';
        const res    = await apiFetch('api/products.php', {
            method,
            body: JSON.stringify(payload),
        });

        if (res.success || res.id) {
            closeModal();
            loadProducts(document.getElementById('searchInput').value);
            showToast(id ? 'Produk berhasil diperbarui!' : 'Produk berhasil ditambahkan!', 'success');
        } else {
            showToast(res.error || 'Gagal menyimpan', 'error');
        }
    } catch (err) {
        showToast('Terjadi kesalahan: ' + err.message, 'error');
    } finally {
        btn.disabled = false;
        btn.textContent = 'Simpan';
    }
}

// ---- Delete ----
function openDeleteModal(id, nama) {
    deleteId = id;
    document.getElementById('deleteNama').textContent = nama;
    document.getElementById('deleteModal').classList.remove('hidden');
}
function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
    deleteId = null;
}
async function confirmDelete() {
    if (!deleteId) return;
    const btn = document.getElementById('btnConfirmDelete');
    btn.disabled = true;
    btn.textContent = 'Menghapus...';
    try {
        const res = await apiFetch(`api/products.php?id=${deleteId}`, { method: 'DELETE' });
        if (res.success) {
            closeDeleteModal();
            loadProducts(document.getElementById('searchInput').value);
            showToast('Produk berhasil dihapus', 'success');
        }
    } catch (err) {
        showToast('Gagal menghapus produk', 'error');
    } finally {
        btn.disabled = false;
        btn.textContent = 'Ya, Hapus';
    }
}

// ================================================================
// RESTOCK MODAL
// ================================================================

// Buka dari tombol tabel (id langsung) atau dari dalam modal Edit (tanpa arg)
function openRestockModal(idOrUndefined) {
    const id = idOrUndefined ?? parseInt(document.getElementById('prodId').value);
    const p  = products.find(x => x.id == id);
    if (!p) return;

    restockProduct = p;

    document.getElementById('restockProductId').value   = p.id;
    document.getElementById('restockProductName').textContent = p.nama;
    document.getElementById('restockCurrentStock').textContent = p.stok;
    document.getElementById('restockAfterStock').textContent  = p.stok;
    document.getElementById('restockJumlah').value     = '';
    document.getElementById('restockCatatan').value    = '';
    document.getElementById('restockTotalBox').classList.add('hidden');

    // Pre-fill harga beli dari produk
    const hargaBeli = parseFloat(p.harga_beli) || 0;
    document.getElementById('restockHargaBeli').value = hargaBeli > 0
        ? new Intl.NumberFormat('id-ID').format(hargaBeli) : '';

    document.getElementById('restockModal').classList.remove('hidden');
    setTimeout(() => document.getElementById('restockJumlah').focus(), 100);
}

function closeRestockModal() {
    document.getElementById('restockModal').classList.add('hidden');
    restockProduct = null;
}

function updateRestockPreview() {
    if (!restockProduct) return;
    const jumlah   = parseInt(document.getElementById('restockJumlah').value) || 0;
    const hargaRaw = document.getElementById('restockHargaBeli').value.replace(/[^0-9]/g, '');
    const harga    = parseInt(hargaRaw) || 0;
    const stokLama = parseInt(restockProduct.stok) || 0;

    document.getElementById('restockAfterStock').textContent = stokLama + jumlah;

    if (jumlah > 0 && harga > 0) {
        const total = jumlah * harga;
        document.getElementById('restockTotalVal').textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(total);
        document.getElementById('restockTotalBox').classList.remove('hidden');
    } else {
        document.getElementById('restockTotalBox').classList.add('hidden');
    }
}

async function saveRestock(e) {
    e.preventDefault();
    const btn = document.getElementById('btnSaveRestock');
    btn.disabled = true;
    btn.textContent = '⏳ Menyimpan...';

    const id       = parseInt(document.getElementById('restockProductId').value);
    const jumlah   = parseInt(document.getElementById('restockJumlah').value) || 0;
    const hargaRaw = document.getElementById('restockHargaBeli').value.replace(/[^0-9]/g, '');
    const harga    = parseInt(hargaRaw) || 0;
    const catatan  = document.getElementById('restockCatatan').value.trim() || 'Restock stok';

    if (jumlah < 1) { showToast('Jumlah restock minimal 1', 'error'); btn.disabled = false; btn.textContent = '💾 Simpan Restock'; return; }
    if (harga  < 1) { showToast('Harga beli harus diisi', 'error');   btn.disabled = false; btn.textContent = '💾 Simpan Restock'; return; }

    try {
        const res = await apiFetch('api/products.php?action=restock', {
            method: 'POST',
            body: JSON.stringify({ id, jumlah, harga_beli: harga, catatan }),
        });

        if (res.success) {
            closeRestockModal();
            loadProducts(document.getElementById('searchInput').value);
            showToast(`✅ Restock berhasil! +${jumlah} unit · No. ${res.purchase_number}`, 'success');
        } else {
            showToast(res.error || 'Restock gagal', 'error');
        }
    } catch (err) {
        showToast('Terjadi kesalahan: ' + err.message, 'error');
    } finally {
        btn.disabled = false;
        btn.textContent = '💾 Simpan Restock';
    }
}

// ---- Helpers ----

function hitungMargin() {
    const beli = parseInputRupiah('prodHargaBeli');
    const jual = parseInputRupiah('prodHargaJual');
    const info = document.getElementById('marginInfo');
    const val  = document.getElementById('marginValue');

    if (beli > 0 && jual > 0) {
        const margin = jual - beli;
        const pct    = ((margin / beli) * 100).toFixed(1);
        info.classList.remove('hidden');
        info.className = info.className.replace(/bg-\w+-50|border-\w+-200/g, '');
        if (margin >= 0) {
            info.classList.add('bg-green-50', 'border-green-200');
            val.className = 'font-bold text-green-600';
            val.textContent = `+${formatNumberInput(margin)} (${pct}%)`;
        } else {
            info.classList.add('bg-red-50', 'border-red-200');
            val.className = 'font-bold text-red-500';
            val.textContent = `${formatNumberInput(margin)} (${pct}%)`;
        }
    } else {
        info.classList.add('hidden');
    }
}

// ---- Gambar: Tab URL vs Upload ----
function switchImgTab(tab) {
    const panelUrl    = document.getElementById('imgPanelUrl');
    const panelUpload = document.getElementById('imgPanelUpload');
    const btnUrl      = document.getElementById('imgTabBtnUrl');
    const btnUpload   = document.getElementById('imgTabBtnUpload');

    if (tab === 'url') {
        panelUrl.classList.remove('hidden');
        panelUpload.classList.add('hidden');
        btnUrl.classList.add('bg-white', 'shadow', 'text-gray-700');
        btnUrl.classList.remove('text-gray-500');
        btnUpload.classList.remove('bg-white', 'shadow', 'text-gray-700');
        btnUpload.classList.add('text-gray-500');
    } else {
        panelUrl.classList.add('hidden');
        panelUpload.classList.remove('hidden');
        btnUpload.classList.add('bg-white', 'shadow', 'text-gray-700');
        btnUpload.classList.remove('text-gray-500');
        btnUrl.classList.remove('bg-white', 'shadow', 'text-gray-700');
        btnUrl.classList.add('text-gray-500');
    }
}

function previewProductImage() {
    const url     = document.getElementById('prodImageUrl').value.trim();
    const preview = document.getElementById('prodImgPreview');
    const fallback= document.getElementById('prodImgFallback');
    if (url) {
        preview.src = url;
        preview.classList.remove('hidden');
        fallback.classList.add('hidden');
    } else {
        preview.classList.add('hidden');
        fallback.classList.remove('hidden');
        preview.src = '';
    }
}

function resetDropZone() {
    document.getElementById('imgDropZoneContent').innerHTML = `
        <svg class="w-8 h-8 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
        </svg>
        <p class="text-xs text-gray-500 font-medium">Klik atau seret gambar ke sini</p>
        <p class="text-xs text-gray-400 mt-1">JPG, PNG, WEBP, GIF — maks. 5MB</p>`;
    document.getElementById('prodUploadProgress').classList.add('hidden');
    document.getElementById('prodFileInput').value = '';
}

async function handleProdFileSelect(event) {
    const file = event.target.files[0];
    if (!file) return;

    const allowed = ['image/jpeg','image/png','image/webp','image/gif'];
    if (!allowed.includes(file.type)) { showToast('Format tidak didukung. Gunakan JPG/PNG/WEBP/GIF','error'); return; }
    if (file.size > 5*1024*1024) { showToast('Ukuran file melebihi 5MB','error'); return; }

    // Preview instan
    const localUrl  = URL.createObjectURL(file);
    const preview   = document.getElementById('prodImgPreview');
    const fallback  = document.getElementById('prodImgFallback');
    preview.src     = localUrl;
    preview.classList.remove('hidden');
    fallback.classList.add('hidden');

    // Progress
    const prog = document.getElementById('prodUploadProgress');
    const bar  = document.getElementById('prodUploadBar');
    const txt  = document.getElementById('prodUploadText');
    prog.classList.remove('hidden');
    bar.style.width = '20%';
    txt.textContent = 'Mengupload gambar...';

    try {
        const fd = new FormData();
        fd.append('gambar', file);
        bar.style.width = '60%';
        const res  = await fetch('api/upload.php', { method:'POST', body:fd, credentials:'same-origin' });
        bar.style.width = '90%';
        const data = await res.json();

        if (data.success) {
            bar.style.width = '100%';
            txt.textContent = '✅ Upload berhasil!';
            // Simpan URL ke field tersembunyi
            document.getElementById('prodImageUrl').value = data.url;
            preview.src = data.url;
            document.getElementById('imgDropZoneContent').innerHTML =
                `<p class="text-xs text-green-600 font-semibold">${file.name}</p>
                 <p class="text-xs text-gray-400 mt-0.5">${(file.size/1024).toFixed(1)} KB — Klik untuk ganti</p>`;
            setTimeout(() => prog.classList.add('hidden'), 2000);
        } else {
            throw new Error(data.error || 'Upload gagal');
        }
    } catch(err) {
        prog.classList.add('hidden');
        bar.style.width = '0%';
        showToast('Upload gagal: ' + err.message, 'error');
        preview.src = localUrl;
    }
}

function getCategoryEmoji(kat) {
    const map = {
        'Makanan':'🍜','Minuman':'🥤','Skincare':'✨','Elektronik':'📱',
        'Sembako':'🛒','Snack':'🍿','Obat-obatan':'💊','Lainnya':'📦',
    };
    return map[kat] || '📦';
}

// Close modal on overlay click
['productModal','deleteModal','restockModal'].forEach(id => {
    document.getElementById(id)?.addEventListener('click', function(e) {
        if (e.target === this) {
            if (id === 'productModal')  closeModal();
            else if (id === 'deleteModal')  closeDeleteModal();
            else if (id === 'restockModal') closeRestockModal();
        }
    });
});

document.addEventListener('DOMContentLoaded', () => loadProducts());
</script>

<?php include 'templates/footer.php'; ?>

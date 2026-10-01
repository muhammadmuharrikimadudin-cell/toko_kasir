<?php
declare(strict_types=1);
// kasir.php — Modul 2: Point of Sale / Kasir — PHP 8.3
session_start();
require_once 'config/database.php';
checkRole(['admin', 'cashier']);

$pageTitle  = 'Kasir / POS';$activePage = 'kasir';
$userRole   = $_SESSION['role'] ?? '';
include 'templates/header.php';
include 'templates/sidebar.php';
?>

<!-- ============================================================
     KASIR / POS PAGE
     ============================================================ -->
<div class="p-6 h-screen flex flex-col">

    <!-- Page Header -->
    <div class="mb-4">
        <h1 class="text-xl font-bold text-gray-800">Point of Sale</h1>
        <p class="text-sm text-gray-500">Buat Transaksi Penjualan Baru</p>
    </div>

    <!-- POS Layout: Left (Catalog) + Right (Cart) -->
    <div class="flex gap-5 flex-1 overflow-hidden">

        <!-- ===== LEFT: Product Catalog ===== -->
        <div class="flex-1 flex flex-col overflow-hidden">

            <!-- Search Bar -->
            <div class="bg-white rounded-2xl shadow-sm p-3 mb-4 flex items-center gap-3">
                <svg class="w-5 h-5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input
                    type="text"
                    id="searchProduct"
                    class="flex-1 outline-none text-sm text-gray-700 placeholder-gray-400"
                    placeholder="Search produk atau scan barcode..."
                    autocomplete="off"
                    oninput="debounceSearch()"
                >
                <button id="btnClearSearch" onclick="clearSearch()" class="hidden text-gray-400 hover:text-gray-600 p-1 rounded-lg">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Product Grid -->
            <div id="productGrid" class="grid grid-cols-4 gap-3 overflow-y-auto flex-1 pr-1 content-start">
                <!-- Skeleton loading -->
                <?php for ($i = 0; $i < 8; $i++): ?>
                <div class="product-card animate-pulse bg-white border rounded-xl shadow-sm h-full">
                    <div class="bg-gray-100 aspect-square rounded-t-xl w-full"></div>
                    <div class="p-2">
                        <div class="mt-2 h-3 bg-gray-200 rounded w-3/4"></div>
                        <div class="mt-1 h-3 bg-gray-200 rounded w-1/2"></div>
                        <div class="mt-2 h-4 bg-gray-200 rounded w-2/3"></div>
                    </div>
                </div>
                <?php endfor; ?>
            </div>
        </div>

        <!-- ===== RIGHT: Cart & Payment ===== -->
        <div class="w-80 flex flex-col">
            <div class="bg-white rounded-2xl shadow-sm flex flex-col flex-1 overflow-hidden">
                <!-- Cart Header -->
                <div class="p-4 border-b border-gray-50">
                    <div class="flex items-center justify-between">
                        <h2 class="font-semibold text-gray-800">Keranjang</h2>
                        <button onclick="clearCart()" class="text-xs text-red-400 hover:text-red-600 font-medium">
                            Kosongkan
                        </button>
                    </div>
                </div>

                <!-- Cart Items -->
                <div id="cartItems" class="flex-1 overflow-y-auto p-4">
                    <!-- Empty state -->
                    <div id="cartEmpty" class="flex flex-col items-center justify-center h-full text-center py-8">
                        <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mb-3">
                            <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                        </div>
                        <p class="text-gray-400 text-sm font-medium">Keranjang Kosong</p>
                        <p class="text-gray-300 text-xs mt-1">Klik produk untuk menambahkan</p>
                    </div>
                </div>

                <!-- Cart Summary -->
                <div class="p-4 border-t border-gray-50 bg-gray-50/50">
                    <div class="space-y-2 text-sm mb-3">
                        <div class="flex justify-between text-gray-500">
                            <span>Sub Total</span>
                            <span id="summarySubtotal" class="font-medium text-gray-700">Rp 0</span>
                        </div>
                        <div class="flex items-center justify-between text-gray-500">
                            <span>Diskon (%)</span>
                            <div class="flex items-center gap-2">
                                <input
                                    type="number"
                                    id="discountInput"
                                    class="w-14 text-right border border-gray-200 rounded-lg px-2 py-1 text-xs outline-none focus:border-blue-300"
                                    value="0" min="0" max="100"
                                    oninput="updateSummary()"
                                >
                                <span class="text-gray-400">%</span>
                            </div>
                        </div>
                        <div class="flex justify-between text-gray-500">
                            <span>Payment Method</span>
                            <select id="paymentMethod" class="text-xs border border-gray-200 rounded-lg px-2 py-1 outline-none focus:border-blue-300 bg-white" onchange="toggleCashFields()">
                                <option value="Cash">Cash</option>
                                <option value="QRIS">QRIS</option>
                                <option value="Transfer">Bank Transfer</option>
                            </select>
                        </div>
                        
                        <!-- Cash Fields (Hanya tampil jika Cash) -->
                        <div id="cashFields" class="space-y-2 pt-2 border-t border-gray-100">
                            <div class="flex items-center justify-between text-gray-500">
                                <span>Uang Dibayar (Rp)</span>
                                <input
                                    type="text"
                                    id="cashTendered"
                                    class="w-24 text-right border border-gray-200 rounded-lg px-2 py-1 text-xs outline-none focus:border-blue-300"
                                    placeholder="0"
                                    oninput="formatRupiahInputField(this); updateSummary()"
                                >
                            </div>
                            <div class="flex justify-between text-gray-500">
                                <span>Kembalian</span>
                                <span id="summaryChange" class="font-medium text-gray-700">Rp 0</span>
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-gray-100 pt-3 mb-4">
                        <div class="flex justify-between items-center">
                            <span class="font-bold text-gray-800 text-base">Total</span>
                            <span id="summaryTotal" class="font-bold text-gray-800 text-lg">Rp 0</span>
                        </div>
                    </div>

                    <!-- Process Payment Button -->
                    <?php if ($userRole === 'admin'): ?>
                    <button
                        id="btnProses"
                        onclick="prosesPayment()"
                        class="w-full py-3 rounded-xl font-bold text-white text-sm transition-all"
                        style="background: #4DB9F2;"
                        disabled
                    >
                        Proses Pembayaran
                    </button>
                    <?php else: ?>
                    <div class="w-full py-3 rounded-xl text-center text-sm font-semibold"
                         style="background:#f3f4f6; color:#9ca3af; border:1.5px dashed #d1d5db; cursor:not-allowed;">
                        <span style="display:flex;align-items:center;justify-content:center;gap:6px">
                            <svg style="width:14px;height:14px;flex-shrink:0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                            Hanya Admin yang Dapat Menyimpan
                        </span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- ============================================================
     PAYMENT SUCCESS MODAL
     ============================================================ -->
<div id="paymentModal" class="modal-overlay hidden">
    <div class="modal-box max-w-md w-full mx-4">
        <!-- Success Icon -->
        <div class="text-center mb-6">
            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-3">
                <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <h3 class="text-xl font-bold text-gray-800">Payment Success!</h3>
            <p id="modalTotalBayar" class="text-3xl font-bold text-gray-800 mt-2">IDR 0</p>
        </div>

        <!-- Receipt Details -->
        <div class="bg-gray-50 rounded-xl p-4 mb-5 text-sm space-y-2">
            <div class="flex justify-between">
                <span class="text-gray-500">Ref Number</span>
                <span id="modalRef" class="font-mono font-medium text-gray-800">-</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">Payment Time</span>
                <span id="modalTime" class="font-medium text-gray-800">-</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">Payment Method</span>
                <span id="modalMethod" class="font-medium text-gray-800">-</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">Nama Kasir</span>
                <span id="modalKasir" class="font-medium text-gray-800">-</span>
            </div>
            <div class="border-t border-gray-200 pt-2 mt-2">
                <div class="flex justify-between">
                    <span class="text-gray-500">Amount</span>
                    <span id="modalAmount" class="font-medium text-gray-800">-</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Diskon</span>
                    <span id="modalDiskon" class="font-medium text-gray-800">-</span>
                </div>
            </div>
            
            <div id="modalCashWrapper" class="border-t border-gray-200 pt-2 mt-2 hidden">
                <div class="flex justify-between mb-1">
                    <span class="text-gray-500">Bayar (Cash)</span>
                    <span id="modalCashGiven" class="font-medium text-gray-800">-</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Kembalian</span>
                    <span id="modalCashChange" class="font-medium text-gray-800">-</span>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex gap-3">
            <button onclick="getPDFReceipt()" class="flex-1 py-3 rounded-xl border-2 border-gray-200 text-gray-700 font-semibold text-sm hover:bg-gray-50 flex items-center justify-center gap-2 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Get PDF Receipt
            </button>
            <button onclick="closeModal()" class="flex-1 py-3 rounded-xl font-semibold text-sm text-white transition-all" style="background:#4DB9F2;">
                Transaksi Baru
            </button>
        </div>
    </div>
</div>

<!-- ============================================================
     KASIR JAVASCRIPT
     ============================================================ -->
<script>
const USER_ROLE = '<?= $userRole ?>';
const IS_ADMIN  = USER_ROLE === 'admin';

// ---- State ----
let cart        = [];       // [{id, nama, kategori, harga_jual, stok, qty}]
let products    = [];
let lastTrx     = null;
let searchTimer = null;

// ---- Load Products ----
async function loadProducts(search = '') {
    try {
        const res = await apiFetch(`api/products.php?search=${encodeURIComponent(search)}&limit=50`);
        products = res.data || [];
        renderProductGrid(products);
    } catch(e) {
        console.error("Gagal memuat produk:", e);
    }
}

function renderProductGrid(prods) {
    const grid = document.getElementById('productGrid');
    if (!grid) return;
    
    if (!prods || !prods.length) {
        grid.innerHTML = `<div class="col-span-3 text-center text-gray-400 py-12">
            <p class="text-4xl mb-3">🔍</p>
            <p class="font-medium">Produk tidak ditemukan</p>
        </div>`;
        return;
    }
    grid.innerHTML = prods.map(p => {
        const outOfStock = parseInt(p.stok) <= 0;
        const imgHtml = p.imageUrl
            ? `<img src="${p.imageUrl}" class="object-cover w-full h-full" alt="${p.nama}" onerror="this.onerror=null; this.outerHTML='<div class=\\'flex items-center justify-center text-4xl w-full h-full bg-gray-50\\'>${getCategoryEmoji(p.kategori)}</div>';">`
            : `<div class="flex items-center justify-center text-4xl w-full h-full bg-gray-50">${getCategoryEmoji(p.kategori)}</div>`;
        return `
        <div class="product-card relative ${outOfStock ? 'out-of-stock opacity-50' : ''}"
             id="prod-${p.id}" title="${outOfStock ? 'Stok habis' : 'Klik untuk tambah ke keranjang'}"
             onclick="${outOfStock ? '' : `addToCart(${p.id})`}">
            <div class="h-full flex flex-col">
                <div class="overflow-hidden rounded-t-xl bg-gray-50 aspect-square w-full flex items-center justify-center">
                    ${imgHtml}
                </div>
                <div class="p-2 flex-1 flex flex-col">
                    <p class="font-semibold text-gray-800 text-xs mt-1 leading-tight line-clamp-2">${p.nama}</p>
                    <p class="text-gray-400 text-xs mt-0.5 truncate">${p.kategori || 'Lainnya'}</p>
                    <div class="mt-auto pt-1">
                        <p class="text-blue-500 font-bold text-xs w-full truncate" title="${formatRupiah(p.harga_jual)}">${formatRupiah(p.harga_jual)}</p>
                        ${outOfStock ? '<span class="text-red-400 text-xs font-medium">Stok Habis</span>' : `<span class="text-gray-400 text-xs">Stok: ${p.stok}</span>`}
                    </div>
                </div>
            </div>
        </div>`;
    }).join('');
}

function getCategoryEmoji(kat) {
    const map = {
        'Makanan':'🍜','Minuman':'🥤','Skincare':'✨','Elektronik':'📱',
        'Sembako':'🛒','Snack':'🍿','Obat-obatan':'💊','Lainnya':'📦',
    };
    return map[kat] || '📦';
}

// ---- Search ----
function debounceSearch() {
    clearTimeout(searchTimer);
    const val = document.getElementById('searchProduct')?.value || '';
    document.getElementById('btnClearSearch')?.classList.toggle('hidden', !val);
    searchTimer = setTimeout(() => loadProducts(val), 300);
}

function clearSearch() {
    const searchInput = document.getElementById('searchProduct');
    if (searchInput) searchInput.value = '';
    document.getElementById('btnClearSearch')?.classList.add('hidden');
    loadProducts();
}

// ---- Cart Functions ----
function addToCart(productId) {
    const p = products.find(p => p.id == productId);
    if (!p) return;
    const existing = cart.find(c => c.id == productId);
    if (existing) {
        if (existing.qty >= parseInt(p.stok)) {
            alert('Stok tidak mencukupi');
            return;
        }
        existing.qty++;
    } else {
        cart.push({ ...p, qty: 1 });
    }
    renderCart();
    
    // Visual feedback on product card
    const card = document.getElementById('prod-' + productId);
    if (card) {
        card.style.borderColor = '#4DB9F2';
        setTimeout(() => { if(card) card.style.borderColor = 'transparent'; }, 500);
    }
}

function updateQty(productId, delta) {
    const idx = cart.findIndex(c => c.id == productId);
    if (idx === -1) return;
    
    const item = cart[idx];
    const newQty = item.qty + delta;
    
    // Check stock limit when increasing
    if (delta > 0 && newQty > parseInt(item.stok)) {
        showToast('Stok tidak mencukupi!', 'warning');
        return;
    }
    
    item.qty = newQty;
    if (item.qty <= 0) {
        cart.splice(idx, 1);
    }
    renderCart();
}

function removeFromCart(productId) {
    cart = cart.filter(c => c.id != productId);
    renderCart();
}

function clearCart() {
    if (cart.length === 0) return;
    cart = [];
    renderCart();
}

function renderCart() {
    const container = document.getElementById('cartItems');
    const btnProses  = document.getElementById('btnProses');

    if (!container) return;

    if (cart.length === 0) {
        container.innerHTML = `
            <div id="cartEmpty" class="flex flex-col items-center justify-center h-full text-center py-8">
                <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mb-3">
                    <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                <p class="text-gray-400 text-sm font-medium">Keranjang Kosong</p>
                <p class="text-gray-300 text-xs mt-1">Klik produk untuk menambahkan</p>
            </div>`;
        if (btnProses) btnProses.disabled = true;
        updateSummary();
        return;
    }

    if (btnProses) btnProses.disabled = false;

    const itemsHTML = cart.map(item => {
        const imgHtml = item.imageUrl
            ? `<img src="${item.imageUrl}" class="w-8 h-8 rounded-lg object-cover flex-shrink-0" onerror="this.onerror=null; this.outerHTML='<div class=\\'w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center text-base flex-shrink-0\\'>${getCategoryEmoji(item.kategori)}</div>';">`
            : `<div class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center text-base flex-shrink-0">${getCategoryEmoji(item.kategori)}</div>`;
            
        return `
        <div class="cart-item border-b border-gray-100 pb-3 mb-3" id="cart-${item.id}">
            <div class="flex items-start gap-2">
                ${imgHtml}
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-semibold text-gray-800 leading-tight">${item.nama}</p>
                    <p class="text-xs text-gray-400">${formatRupiah(item.harga_jual)}</p>
                </div>
                <button onclick="removeFromCart(${item.id})" class="text-gray-300 hover:text-red-400 transition-colors p-0.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="flex items-center justify-between mt-2 pl-10">
                <div class="flex items-center gap-2">
                    <button class="qty-btn px-2 py-0.5 bg-gray-100 rounded text-xs font-bold" onclick="updateQty(${item.id}, -1)">−</button>
                    <span class="text-sm font-bold w-5 text-center">${item.qty}</span>
                    <button class="qty-btn px-2 py-0.5 bg-gray-100 rounded text-xs font-bold" onclick="updateQty(${item.id}, 1)">+</button>
                </div>
                <span class="text-sm font-bold text-gray-800">${formatRupiah(item.harga_jual * item.qty)}</span>
            </div>
        </div>
        `;
    }).join('');

    container.innerHTML = itemsHTML;
    updateSummary();
}

function updateSummary() {
    const subTotal = cart.reduce((sum, item) => sum + (item.harga_jual * item.qty), 0);
    const discountEl = document.getElementById('discountInput');
    const diskon   = parseFloat(discountEl?.value || '0') || 0;
    const total    = subTotal - (subTotal * diskon / 100);

    const subTotalEl = document.getElementById('summarySubtotal');
    const totalEl = document.getElementById('summaryTotal');

    if (subTotalEl) subTotalEl.textContent = formatRupiah(subTotal);
    if (totalEl) totalEl.textContent    = formatRupiah(total);
    
    // Kalkulasi kembalian
    const method = document.getElementById('paymentMethod')?.value;
    if (method === 'Cash') {
        const cashInput = document.getElementById('cashTendered')?.value || '0';
        const cashVal = parseInt(cashInput.replace(/[^0-9]/g, ''), 10) || 0;
        const change = cashVal - total;
        const changeEl = document.getElementById('summaryChange');
        if (changeEl) {
            if (change >= 0) {
                changeEl.textContent = formatRupiah(change);
                changeEl.className = 'font-bold text-green-500';
            } else {
                changeEl.textContent = "- " + formatRupiah(Math.abs(change));
                changeEl.className = 'font-bold text-red-500';
            }
        }
    }
}

function toggleCashFields() {
    const method = document.getElementById('paymentMethod')?.value;
    const cashFields = document.getElementById('cashFields');
    if (cashFields) {
        if (method === 'Cash') cashFields.classList.remove('hidden');
        else cashFields.classList.add('hidden');
    }
    updateSummary();
}

// ---- Process Payment ----
async function prosesPayment() {
    if (!IS_ADMIN) {
        showToast('Hanya admin yang dapat menyimpan transaksi.', 'error');
        return;
    }
    if (cart.length === 0) return;
    const diskon = parseFloat(document.getElementById('discountInput')?.value || '0') || 0;
    const metode = document.getElementById('paymentMethod')?.value || 'Cash';
    
    // Validasi uang cash
    const subTotal = cart.reduce((sum, item) => sum + (item.harga_jual * item.qty), 0);
    const total    = subTotal - (subTotal * diskon / 100);
    const cashInput = document.getElementById('cashTendered')?.value || '0';
    const cashVal = parseInt(cashInput.replace(/[^0-9]/g, ''), 10) || 0;
    
    if (metode === 'Cash' && cashVal < total) {
        showToast('Jumlah uang dibayar kurang dari total bayar!', 'error');
        return;
    }

    const btn = document.getElementById('btnProses');
    if (btn) {
        btn.disabled = true;
        btn.textContent = 'Memproses...';
    }

    try {
        const result = await apiFetch('api/transactions.php', {
            method: 'POST',
            body: JSON.stringify({ 
                items: cart, 
                diskon, 
                metode_bayar: metode, 
                cash_tendered: cashVal 
            }),
        });

        if (result.success) {
            lastTrx = result;
            showPaymentModal(result);
            cart = [];
            renderCart();
            loadProducts(); // refresh stok
        }
    } catch(e) {
        alert('Transaksi gagal: ' + e.message);
    } finally {
        if (btn) {
            btn.disabled = false;
            btn.textContent = 'Proses Pembayaran';
        }
    }
}

function showPaymentModal(data) {
    document.getElementById('modalTotalBayar')?.replaceChildren(document.createTextNode('IDR ' + parseInt(data.total_bayar).toLocaleString('id-ID')));
    document.getElementById('modalRef')?.replaceChildren(document.createTextNode(data.kode_transaksi || '-'));
    document.getElementById('modalTime')?.replaceChildren(document.createTextNode(data.created_at || '-'));
    document.getElementById('modalMethod')?.replaceChildren(document.createTextNode(data.metode_bayar || '-'));
    document.getElementById('modalKasir')?.replaceChildren(document.createTextNode(data.kasir || '-'));
    document.getElementById('modalAmount')?.replaceChildren(document.createTextNode('IDR ' + parseInt(data.total || 0).toLocaleString('id-ID')));
    document.getElementById('modalDiskon')?.replaceChildren(document.createTextNode((data.diskon || 0) + '%'));
    
    const cashWrapper = document.getElementById('modalCashWrapper');
    if (data.metode_bayar === 'Cash') {
        document.getElementById('modalCashGiven')?.replaceChildren(document.createTextNode('IDR ' + parseInt(data.pay_amount || 0).toLocaleString('id-ID')));
        document.getElementById('modalCashChange')?.replaceChildren(document.createTextNode('IDR ' + parseInt(data.change_amount || 0).toLocaleString('id-ID')));
        cashWrapper?.classList.remove('hidden');
    } else {
        cashWrapper?.classList.add('hidden');
    }
    
    document.getElementById('paymentModal')?.classList.remove('hidden');
}

function closeModal() {
    document.getElementById('paymentModal')?.classList.add('hidden');
}

// Close modal on overlay click
document.getElementById('paymentModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});

// ---- PDF Receipt ----
function getPDFReceipt() {
    if (!lastTrx) return;
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF({ unit: 'mm', format: [80, 140] });

    doc.setFontSize(12);
    doc.setFont('helvetica', 'bold');
    doc.text('KASIR TOKO', 40, 10, { align: 'center' });

    doc.setFontSize(8);
    doc.setFont('helvetica', 'normal');
    doc.text('Point of Sale Receipt', 40, 15, { align: 'center' });
    doc.line(5, 18, 75, 18);

    doc.text(`Ref: ${lastTrx.kode_transaksi}`, 5, 23);
    doc.text(`Waktu: ${lastTrx.created_at}`, 5, 28);
    doc.text(`Kasir: ${lastTrx.kasir}`, 5, 33);
    doc.text(`Metode: ${lastTrx.metode_bayar}`, 5, 38);
    doc.line(5, 41, 75, 41);

    doc.setFont('helvetica', 'bold');
    doc.text('Total:', 5, 46);
    doc.text('IDR ' + parseInt(lastTrx.total_bayar).toLocaleString('id-ID'), 75, 46, { align: 'right' });

    if (lastTrx.diskon > 0) {
        doc.setFont('helvetica', 'normal');
        doc.text(`Diskon: ${lastTrx.diskon}%`, 5, 52);
    }

    doc.line(5, 56, 75, 56);
    doc.setFontSize(7);
    doc.text('Terima kasih telah berbelanja!', 40, 61, { align: 'center' });

    doc.save(`Receipt-${lastTrx.kode_transaksi}.pdf`);
}

document.addEventListener('DOMContentLoaded', () => {
    loadProducts();
});
</script>

<?php include 'templates/footer.php'; ?>
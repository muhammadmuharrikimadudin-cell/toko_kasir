<?php
// templates/sidebar.php
// Requires $activePage variable set before include
if (!isset($activePage)) $activePage = 'dashboard';
$user = getCurrentUser();

$menuItems = [
    'dashboard' => ['label' => 'Dashboard',           'icon' => 'home',     'href' => '/dashboard.php'],
    'kasir'     => ['label' => 'Kasir / POS',          'icon' => 'cashier',  'href' => '/kasir.php'],
    'products'  => ['label' => 'Inventaris',           'icon' => 'box',      'href' => '/products.php'],
    'riwayat'   => ['label' => 'Riwayat Transaksi',   'icon' => 'clock',    'href' => '/riwayat.php'],
    'employee'  => ['label' => 'Karyawan',             'icon' => 'users',    'href' => '/employee.php'],
    'users'     => ['label' => 'Manajemen Akun',       'icon' => 'shield',   'href' => '/users.php'],
    'settings'  => ['label' => 'Pengaturan',           'icon' => 'settings', 'href' => '/settings.php'],
];

$userRole = $user['role'] ?? '';
if ($userRole === 'cashier') {
    $menuItems = [
        'kasir'   => $menuItems['kasir'],
        'riwayat' => $menuItems['riwayat']
    ];
} elseif ($userRole === 'karyawan') {
    $menuItems = [
        'employee' => $menuItems['employee']
    ];
}

$icons = [
    'home'     => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>',
    'cashier'  => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>',
    'box'      => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 10V7"/></svg>',
    'clock'    => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
    'users'    => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>',
    'shield'   => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>',
    'settings' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>',
];
?>

<!-- Overlay backdrop (mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<!-- Hamburger Button -->
<button class="hamburger-btn" id="hamburgerBtn" onclick="toggleSidebar()" title="Toggle Sidebar" aria-label="Toggle Sidebar">
    <span class="bar"></span>
    <span class="bar"></span>
    <span class="bar"></span>
</button>

<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">
    <!-- Brand / User -->
    <div class="p-5 border-b border-white/20 sidebar-brand" style="display:flex;align-items:center;gap:12px;">
        <div class="w-9 h-9 rounded-full bg-white/20 flex items-center justify-center text-white flex-shrink-0">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
        </div>
        <div class="sidebar-brand-text" style="transition:opacity .25s,width .25s;min-width:0;">
            <p class="text-white font-semibold text-sm leading-tight whitespace-nowrap">Administrasi</p>
            <p class="text-white/70 text-xs whitespace-nowrap"><?= htmlspecialchars($user['name']) ?></p>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="sidebar-nav flex-1 py-4">
        <?php foreach ($menuItems as $key => $item): ?>
        <a href="<?= $item['href'] ?>"
           class="<?= ($activePage === $key) ? 'active' : '' ?>"
           title="<?= $item['label'] ?>">
            <?= $icons[$item['icon']] ?>
            <span class="sidebar-label" style="transition:opacity .25s,width .25s;white-space:nowrap;"><?= $item['label'] ?></span>
        </a>
        <?php endforeach; ?>
    </nav>

    <!-- Quit Button -->
    <div class="p-4 border-t border-white/20">
        <a href="/logout.php"
           class="sidebar-logout flex items-center gap-3 px-4 py-3 text-white/80 hover:text-white hover:bg-white/15 rounded-xl transition-all text-sm font-medium"
           title="Logout">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
            </svg>
            <span class="sidebar-logout-text" style="transition:opacity .25s,width .25s;white-space:nowrap;">Quit</span>
        </a>
    </div>
</aside>

<!-- MAIN CONTENT WRAPPER -->
<main class="main-content flex-1" id="mainContent">

<script>
(function () {
    const sidebar    = document.getElementById('sidebar');
    const main       = document.getElementById('mainContent');
    const btn        = document.getElementById('hamburgerBtn');
    const overlay    = document.getElementById('sidebarOverlay');
    const isMobile   = () => window.innerWidth <= 768;

    // Restore state from localStorage
    const saved = localStorage.getItem('sidebarCollapsed');
    let collapsed = saved === 'true';

    function applyState() {
        if (isMobile()) {
            // Mobile: overlay mode
            sidebar.classList.remove('collapsed');
            main.classList.remove('sidebar-collapsed');
            if (collapsed) {
                sidebar.classList.remove('mobile-open');
                overlay.classList.remove('active');
                btn.classList.add('is-collapsed');
            } else {
                sidebar.classList.add('mobile-open');
                overlay.classList.add('active');
                btn.classList.remove('is-collapsed');
            }
        } else {
            // Desktop: icon-only collapse
            overlay.classList.remove('active');
            sidebar.classList.remove('mobile-open');
            if (collapsed) {
                sidebar.classList.add('collapsed');
                main.classList.add('sidebar-collapsed');
                btn.style.left = '18px';
                btn.classList.add('is-collapsed');
            } else {
                sidebar.classList.remove('collapsed');
                main.classList.remove('sidebar-collapsed');
                btn.style.left = '174px';
                btn.classList.remove('is-collapsed');
            }
        }
    }

    window.toggleSidebar = function () {
        collapsed = !collapsed;
        localStorage.setItem('sidebarCollapsed', collapsed);
        applyState();
    };

    // Init
    applyState();

    // Re-apply on resize
    window.addEventListener('resize', applyState);
})();
</script>

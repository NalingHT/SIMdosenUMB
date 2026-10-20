<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once __DIR__ . '/includes/csrf.php';

$namaPengguna = $_SESSION['nama_lengkap'] ?? 'Pengguna';
$userRole     = $_SESSION['role'] ?? 'Pengguna';
?>
<header class="topbar">
    <div class="topbar-left">
        <button
            type="button"
            class="sidebar-toggle"
            onclick="toggleSidebar()"
            aria-label="Buka atau tutup menu navigasi"
            title="Menu Navigasi"
        >
            <i class="fa-solid fa-bars"></i>
        </button>

        <a href="dashboard.php" class="topbar-brand">
            <div class="logo-emblem-sm">
                <i class="fa-solid fa-graduation-cap"></i>
            </div>
            <div>
                <strong>SIM DOSEN</strong>
                <span>Universitas Muhammadiyah Bengkulu</span>
            </div>
        </a>
    </div>

    <div class="topbar-right">
        <a href="index.php" class="btn-portal-preview" title="Lihat Halaman Depan Publik" target="_blank">
            <i class="fa-solid fa-globe"></i>
            <span>Portal Publik</span>
        </a>

        <div class="user-menu">
            <div class="avatar" title="<?= htmlspecialchars($namaPengguna, ENT_QUOTES, 'UTF-8') ?>">
                <?= htmlspecialchars(strtoupper(substr($namaPengguna, 0, 1)), ENT_QUOTES, 'UTF-8') ?>
            </div>

            <div class="user-info">
                <strong><?= htmlspecialchars($namaPengguna, ENT_QUOTES, 'UTF-8') ?></strong>
                <span class="badge-role"><?= htmlspecialchars($userRole, ENT_QUOTES, 'UTF-8') ?></span>
            </div>
        </div>

        <form
            action="logout.php"
            method="post"
            class="logout-form"
            onsubmit="return confirm('Apakah Anda yakin ingin keluar dari sistem?');"
        >
            <?= csrf_field() ?>
            <button type="submit" class="logout-button">
                <i class="fa-solid fa-arrow-right-from-bracket"></i>
                <span>Keluar</span>
            </button>
        </form>
    </div>
</header>

<script>
(function () {
    function isMobileSidebarMode() {
        return window.innerWidth <= 800;
    }

    window.toggleSidebar = function () {
        const sidebar = document.querySelector('.sidebar');
        if (!sidebar) return;

        if (isMobileSidebarMode()) {
            sidebar.classList.toggle('show');
            return;
        }

        const collapsed = document.body.classList.toggle('sidebar-collapsed');
        sidebar.setAttribute('data-collapsed', collapsed ? '1' : '0');
    };

    function closeMobileSidebar() {
        const sidebar = document.querySelector('.sidebar');
        if (sidebar && isMobileSidebarMode()) {
            sidebar.classList.remove('show');
        }
    }

    document.addEventListener('click', function (event) {
        const link = event.target.closest('.sidebar-link');
        if (link && isMobileSidebarMode()) {
            closeMobileSidebar();
        }
    });

    window.addEventListener('resize', function () {
        const sidebar = document.querySelector('.sidebar');
        if (!sidebar) return;

        if (window.innerWidth > 800) {
            sidebar.classList.remove('show');
        } else {
            document.body.classList.remove('sidebar-collapsed');
        }
    });
})();
</script>

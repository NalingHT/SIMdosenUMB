<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$role = $_SESSION['role'] ?? '';

// Helper: detect current page for active sidebar link
$currentPage = basename($_SERVER['PHP_SELF']);
function isActive($page) {
    global $currentPage;
    return $currentPage === $page ? ' active' : '';
}
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-title">NAVIGASI UTAMA</div>

    <a href="dashboard.php" class="sidebar-link<?= isActive('dashboard.php') ?>">
        <span class="icon"><i class="fa-solid fa-chart-pie"></i></span>
        <span>Dashboard</span>
    </a>

    <?php if ($role === 'ADMIN' || $role === 'OPERATOR_PRODI' || $role === 'FAKULTAS' || $role === 'PIMPINAN'): ?>
        <div class="sidebar-title">DATA AKADEMIK</div>
        <a href="dosen.php" class="sidebar-link<?= isActive('dosen.php') || isActive('dosen_tambah.php') || isActive('dosen_edit.php') || isActive('dosen_detail.php') ?>">
            <span class="icon"><i class="fa-solid fa-chalkboard-user"></i></span>
            <span>Data Dosen</span>
        </a>
        <a href="laporan_dosen.php" class="sidebar-link<?= isActive('laporan_dosen.php') ?>">
            <span class="icon"><i class="fa-solid fa-file-invoice"></i></span>
            <span>Rekap & Laporan</span>
        </a>
        <a href="penempatan.php" class="sidebar-link<?= isActive('penempatan.php') ?>">
            <span class="icon"><i class="fa-solid fa-building-columns"></i></span>
            <span>Penempatan</span>
        </a>
    <?php endif; ?>

    <?php if ($role === 'ADMIN'): ?>
        <div class="sidebar-title">REFERENSI</div>
        <a href="ref_jabatan.php" class="sidebar-link<?= isActive('ref_jabatan.php') ?>">
            <span class="icon"><i class="fa-solid fa-award"></i></span>
            <span>Jabatan Fungsional</span>
        </a>
        <a href="ref_pendidikan.php" class="sidebar-link<?= isActive('ref_pendidikan.php') ?>">
            <span class="icon"><i class="fa-solid fa-graduation-cap"></i></span>
            <span>Pendidikan</span>
        </a>
        <a href="ref_kepangkatan.php" class="sidebar-link<?= isActive('ref_kepangkatan.php') ?>">
            <span class="icon"><i class="fa-solid fa-id-badge"></i></span>
            <span>Kepangkatan</span>
        </a>

        <div class="sidebar-title">WEB SERVICE & INTEGRASI</div>
        <a href="api_docs.php" class="sidebar-link<?= isActive('api_docs.php') ?>">
            <span class="icon"><i class="fa-solid fa-code"></i></span>
            <span>API Dokumentasi</span>
        </a>
        <a href="sinkronisasi.php" class="sidebar-link<?= isActive('sinkronisasi.php') ?>">
            <span class="icon"><i class="fa-solid fa-arrows-rotate"></i></span>
            <span>Integrasi SIM Mahasiswa</span>
        </a>
        <a href="api/semantik.php" target="_blank" class="sidebar-link">
            <span class="icon"><i class="fa-solid fa-share-nodes"></i></span>
            <span>Web Semantik (JSON-LD)</span>
        </a>

        <div class="sidebar-title">PENGATURAN SISTEM</div>
        <a href="pengguna.php" class="sidebar-link<?= isActive('pengguna.php') ?>">
            <span class="icon"><i class="fa-solid fa-users-gear"></i></span>
            <span>Manajemen Pengguna</span>
        </a>
    <?php endif; ?>

    <div class="sidebar-version">SIM Dosen v1.0 • UMB</div>
</aside>

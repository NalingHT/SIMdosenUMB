<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once __DIR__ . '/koneksi.php';

$nama_aplikasi = "SIM Dosen";
$nama_universitas = "Universitas Muhammadiyah Bengkulu";
$tahun = date("Y");

// Live stats from database
$totDosen = 0;
$totAktif = 0;
$totProdi = 0;
$totFakultas = 0;
$totSerdos = 0;
$prodiList = [];

if (isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {
    // Total Dosen
    $r = $conn->query("SELECT COUNT(*) AS c FROM dosen");
    if ($r) $totDosen = (int)$r->fetch_assoc()['c'];

    // Dosen Aktif
    $r = $conn->query("SELECT COUNT(*) AS c FROM dosen WHERE status_aktivitas = 'Aktif'");
    if ($r) $totAktif = (int)$r->fetch_assoc()['c'];

    // Total Program Studi
    $r = $conn->query("SELECT COUNT(DISTINCT program_studi) AS c FROM penempatan WHERE aktif = 1");
    if ($r) $totProdi = (int)$r->fetch_assoc()['c'];

    // Total Fakultas
    $r = $conn->query("SELECT COUNT(DISTINCT fakultas) AS c FROM penempatan WHERE aktif = 1 AND fakultas != ''");
    if ($r) $totFakultas = (int)$r->fetch_assoc()['c'];

    // Dosen Tersertifikasi (Serdos)
    $r = $conn->query("SELECT COUNT(*) AS c FROM dosen WHERE status_serdos = 'Sudah'");
    if ($r) $totSerdos = (int)$r->fetch_assoc()['c'];

    // Daftar Program Studi beserta jumlah dosen
    $sqlProdi = "SELECT p.program_studi, p.fakultas, COUNT(d.id_dosen) AS total_dosen
                 FROM penempatan p
                 LEFT JOIN dosen d ON d.id_penempatan = p.id_penempatan
                 WHERE p.aktif = 1
                 GROUP BY p.program_studi, p.fakultas
                 ORDER BY total_dosen DESC
                 LIMIT 8";
    $resProdi = $conn->query($sqlProdi);
    if ($resProdi) {
        while ($row = $resProdi->fetch_assoc()) {
            $prodiList[] = $row;
        }
    }
}

// Fallback jika belum ada data penempatan
if (empty($prodiList)) {
    $prodiList = [
        ['program_studi' => 'Teknik Informatika', 'fakultas' => 'Teknik', 'total_dosen' => $totDosen ?: 24, 'icon' => 'fa-desktop', 'color' => '#0d9488'],
        ['program_studi' => 'Manajemen', 'fakultas' => 'Ekonomi & Bisnis', 'total_dosen' => 18, 'icon' => 'fa-chart-line', 'color' => '#059669'],
        ['program_studi' => 'Ekonomi Syariah', 'fakultas' => 'Agama Islam', 'total_dosen' => 15, 'icon' => 'fa-book-open-reader', 'color' => '#7c3aed'],
        ['program_studi' => 'Keperawatan', 'fakultas' => 'Ilmu Kesehatan', 'total_dosen' => 16, 'icon' => 'fa-user-doctor', 'color' => '#e11d48'],
        ['program_studi' => 'Pendidikan', 'fakultas' => 'FKIP', 'total_dosen' => 20, 'icon' => 'fa-users', 'color' => '#ea580c'],
        ['program_studi' => 'Agronomi', 'fakultas' => 'Pertanian', 'total_dosen' => 12, 'icon' => 'fa-leaf', 'color' => '#16a34a'],
        ['program_studi' => 'Teknik Mesin', 'fakultas' => 'Teknik', 'total_dosen' => 14, 'icon' => 'fa-gear', 'color' => '#0284c7'],
        ['program_studi' => 'Program Studi Lainnya', 'fakultas' => 'Universitas', 'total_dosen' => 28, 'icon' => 'fa-ellipsis', 'color' => '#64748b']
    ];
} else {
    // Berikan palet warna & icon untuk setiap item dinamis
    $icons = ['fa-desktop', 'fa-chart-line', 'fa-book-open-reader', 'fa-user-doctor', 'fa-users', 'fa-leaf', 'fa-gear', 'fa-graduation-cap'];
    $colors = ['#0d9488', '#059669', '#7c3aed', '#e11d48', '#ea580c', '#16a34a', '#0284c7', '#64748b'];
    foreach ($prodiList as $i => &$p) {
        $p['icon'] = $icons[$i % count($icons)];
        $p['color'] = $colors[$i % count($colors)];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($nama_aplikasi) ?> — <?= htmlspecialchars($nama_universitas) ?></title>
    <meta name="description" content="Sistem Informasi Manajemen Dosen Universitas Muhammadiyah Bengkulu berbasis Web Semantik, RDF, OWL, SPARQL, dan REST API.">

    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- FontAwesome 6 Free Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --primary-blue: #0d9488;
            --primary-hover: #0f766e;
            --primary-light: #f0fdfa;
            --primary-dark: #134e4a;
            --text-dark: #1e293b;
            --text-muted: #64748b;
            --text-light: #94a3b8;
            --bg-light: #f8fafc;
            --bg-white: #ffffff;
            --footer-bg: #0a2723;
            --card-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
            --card-shadow-hover: 0 12px 28px rgba(13, 148, 136, 0.12);
            --border-color: #e2e8f0;
            --transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        html { scroll-behavior: smooth; }

        body {
            color: var(--text-dark);
            background-color: var(--bg-white);
            line-height: 1.6;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
        }

        a {
            text-decoration: none;
            color: inherit;
            transition: var(--transition);
        }

        /* ── HEADER NAVIGATION ───────────────────────────── */
        header {
            background-color: #ffffff;
            border-bottom: 1px solid var(--border-color);
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.02);
        }

        .nav-container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0.85rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo-group {
            display: flex;
            align-items: center;
            gap: 0.85rem;
            text-decoration: none;
        }

        .logo-emblem {
            width: 46px;
            height: 46px;
            background: linear-gradient(135deg, #0d9488, #0b5f57);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffd700;
            font-size: 1.4rem;
            box-shadow: 0 3px 8px rgba(13, 148, 136, 0.25);
            border: 2px solid #2dd4bf;
            flex-shrink: 0;
        }

        .logo-text h1 {
            font-size: 1.15rem;
            font-weight: 800;
            color: var(--text-dark);
            line-height: 1.1;
            letter-spacing: -0.3px;
        }

        .logo-text span {
            font-size: 0.72rem;
            color: var(--text-muted);
            font-weight: 600;
            letter-spacing: 0.2px;
            display: block;
            margin-top: 2px;
        }

        .nav-menu {
            display: flex;
            list-style: none;
            gap: 1.8rem;
            align-items: center;
        }

        .nav-link {
            font-size: 0.88rem;
            font-weight: 600;
            color: var(--text-dark);
            padding: 0.5rem 0;
            position: relative;
        }

        .nav-link:hover, .nav-link.active {
            color: var(--primary-blue);
        }

        .nav-link.active::after {
            content: '';
            position: absolute;
            bottom: -4px;
            left: 0;
            width: 100%;
            height: 2.5px;
            background-color: var(--primary-blue);
            border-radius: 2px;
        }

        .btn-login-nav {
            background-color: var(--primary-blue);
            color: white !important;
            padding: 0.6rem 1.4rem;
            border-radius: 8px;
            font-weight: 700;
            font-size: 0.88rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 4px 12px rgba(13, 148, 136, 0.25);
            border: none;
            cursor: pointer;
            transition: var(--transition);
        }

        .btn-login-nav:hover {
            background-color: var(--primary-hover);
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(13, 148, 136, 0.35);
        }

        .menu-toggle {
            display: none;
            font-size: 1.4rem;
            cursor: pointer;
            color: var(--text-dark);
        }

        /* ── HERO SECTION ────────────────────────────────── */
        .hero-section {
            background: linear-gradient(180deg, #f0fdfa 0%, #ffffff 100%);
            padding: 4.5rem 2rem 5rem 2rem;
            position: relative;
            overflow: hidden;
        }

        .hero-bg-overlay {
            position: absolute;
            top: 0;
            right: 0;
            width: 100%;
            height: 100%;
            background-image: radial-gradient(#cbd5e1 0.75px, transparent 0.75px);
            background-size: 24px 24px;
            opacity: 0.35;
            pointer-events: none;
        }

        .hero-container {
            max-width: 1280px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1.1fr 1fr;
            gap: 3.5rem;
            align-items: center;
            position: relative;
            z-index: 2;
        }

        .hero-badge-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px;
            border-radius: 20px;
            background: #ccfbf1;
            color: var(--primary-blue);
            font-size: 0.8rem;
            font-weight: 800;
            margin-bottom: 1.2rem;
        }

        .hero-content h2 {
            font-size: clamp(2.4rem, 4.5vw, 3.4rem);
            font-weight: 800;
            line-height: 1.15;
            color: var(--text-dark);
            letter-spacing: -1px;
            margin-bottom: 1.2rem;
        }

        .hero-content h2 span {
            color: var(--primary-blue);
            display: block;
        }

        .hero-description {
            font-size: 1.05rem;
            color: var(--text-muted);
            margin-bottom: 2rem;
            max-width: 540px;
            line-height: 1.7;
        }

        .hero-actions {
            display: flex;
            gap: 1rem;
            margin-bottom: 2.2rem;
            flex-wrap: wrap;
        }

        .btn-primary {
            background-color: var(--primary-blue);
            color: white;
            padding: 0.85rem 1.6rem;
            border-radius: 8px;
            font-weight: 700;
            font-size: 0.95rem;
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            box-shadow: 0 4px 14px rgba(13, 148, 136, 0.3);
            cursor: pointer;
            border: none;
        }

        .btn-primary:hover {
            background-color: var(--primary-hover);
            box-shadow: 0 6px 20px rgba(13, 148, 136, 0.4);
            transform: translateY(-1px);
            color: white;
        }

        .btn-outline {
            background-color: white;
            color: var(--primary-blue);
            border: 1.5px solid var(--primary-blue);
            padding: 0.85rem 1.6rem;
            border-radius: 8px;
            font-weight: 700;
            font-size: 0.95rem;
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
        }

        .btn-outline:hover {
            background-color: var(--primary-light);
            transform: translateY(-1px);
        }

        .hero-quote {
            font-style: italic;
            color: var(--text-muted);
            font-weight: 500;
            font-size: 0.98rem;
        }

        /* ── HERO LAPTOP / SEMANTIC MOCKUP ───────────────── */
        .hero-mockup-wrapper {
            position: relative;
            width: 100%;
            max-width: 520px;
            margin: 0 auto;
        }

        .laptop-frame {
            background: #ffffff;
            border-radius: 14px;
            border: 1px solid #99f6e4;
            box-shadow: 0 20px 45px rgba(15, 23, 42, 0.12);
            overflow: hidden;
            transition: var(--transition);
        }

        .laptop-topbar {
            background: #f8fafc;
            padding: 0.65rem 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .browser-dots {
            display: flex;
            gap: 6px;
        }
        .dot { width: 10px; height: 10px; border-radius: 50%; }
        .dot-red { background-color: #ef4444; }
        .dot-yellow { background-color: #f59e0b; }
        .dot-green { background-color: #10b981; }

        .browser-bar {
            flex: 1;
            height: 22px;
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            font-size: 0.7rem;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            padding: 0 10px;
            gap: 6px;
            margin: 0 0.5rem;
        }

        .laptop-body {
            padding: 1.8rem 1.6rem;
            background: linear-gradient(135deg, #f0fdfa, #ffffff);
            position: relative;
        }

        /* Visualisasi Graf Semantik di dalam Laptop */
        .graph-card {
            background: #ffffff;
            border: 1.5px solid #99f6e4;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 8px 24px rgba(13, 148, 136, 0.08);
            text-align: center;
        }

        .graph-central-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, #0d9488, #134e4a);
            color: #ffffff;
            font-weight: 800;
            font-size: 0.85rem;
            padding: 8px 16px;
            border-radius: 20px;
            box-shadow: 0 4px 12px rgba(13, 148, 136, 0.3);
            margin-bottom: 18px;
        }

        .graph-nodes-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin-top: 10px;
        }

        .graph-node-pill {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 10px 12px;
            display: flex;
            align-items: center;
            gap: 10px;
            text-align: left;
            transition: var(--transition);
        }
        .graph-node-pill:hover {
            border-color: var(--primary-blue);
            background: #f0fdfa;
            transform: translateY(-2px);
        }

        .node-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 0.85rem;
            flex-shrink: 0;
        }

        .node-info strong {
            font-size: 0.82rem;
            color: var(--text-dark);
            display: block;
            line-height: 1.1;
        }
        .node-info span {
            font-size: 0.68rem;
            color: var(--text-muted);
            display: block;
        }

        /* Linked Data Bookmarks Callout */
        .semantic-floating-badge {
            position: absolute;
            bottom: -15px;
            right: -15px;
            background: #0a2723;
            color: #ffffff;
            padding: 10px 16px;
            border-radius: 10px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
            font-size: 0.75rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 8px;
            border: 1px solid #134e4a;
            z-index: 3;
        }
        .semantic-floating-badge span {
            color: #5eead4;
        }

        /* ── FITUR UTAMA (4 CARDS DENGAN BULATAN HALUS) ─── */
        .features-section {
            padding: 5rem 2rem;
            background-color: var(--bg-white);
        }

        .section-container {
            max-width: 1280px;
            margin: 0 auto;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 2rem;
            margin-top: 1rem;
        }

        .feature-card {
            text-align: center;
            padding: 2.2rem 1.5rem;
            border-radius: 16px;
            background: #ffffff;
            border: 1px solid var(--border-color);
            box-shadow: var(--card-shadow);
            transition: var(--transition);
        }
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--card-shadow-hover);
            border-color: #5eead4;
        }

        .feature-circle-icon {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            background-color: #f0fdfa;
            color: var(--primary-blue);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            margin: 0 auto 1.4rem auto;
            transition: var(--transition);
        }
        .feature-card:hover .feature-circle-icon {
            background-color: var(--primary-blue);
            color: #ffffff;
            transform: scale(1.08);
        }

        .feature-card h3 {
            font-size: 1.15rem;
            font-weight: 800;
            color: var(--text-dark);
            margin-bottom: 0.6rem;
        }

        .feature-card p {
            font-size: 0.88rem;
            color: var(--text-muted);
            line-height: 1.6;
        }

        /* ── DAFTAR PROGRAM STUDI GRID ──────────────────── */
        .prodi-section {
            padding: 5rem 2rem;
            background-color: #f8fafc;
            border-top: 1px solid #f1f5f9;
        }

        .section-header-flex {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            margin-bottom: 2.5rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .section-header-flex h2 {
            font-size: 2rem;
            font-weight: 800;
            color: var(--text-dark);
            letter-spacing: -0.5px;
        }

        .section-header-flex p {
            color: var(--text-muted);
            font-size: 0.95rem;
            margin-top: 4px;
        }

        .link-view-all {
            color: var(--primary-blue);
            font-weight: 700;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .link-view-all:hover {
            color: var(--primary-hover);
            transform: translateX(3px);
        }

        .prodi-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
        }

        .prodi-card {
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: 14px;
            padding: 1.3rem;
            box-shadow: var(--card-shadow);
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: var(--transition);
            text-decoration: none;
        }
        .prodi-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--card-shadow-hover);
            border-color: #5eead4;
        }

        .prodi-info-wrap {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .prodi-icon-badge {
            width: 46px;
            height: 46px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 1.15rem;
            flex-shrink: 0;
        }

        .prodi-text strong {
            font-size: 0.92rem;
            font-weight: 800;
            color: var(--text-dark);
            display: block;
            line-height: 1.2;
        }
        .prodi-text span {
            font-size: 0.78rem;
            color: var(--text-muted);
            font-weight: 600;
            display: block;
            margin-top: 2px;
        }

        .prodi-arrow {
            color: var(--primary-blue);
            font-size: 0.85rem;
            transition: var(--transition);
        }
        .prodi-card:hover .prodi-arrow {
            transform: translateX(4px);
        }

        /* ── STATS SECTION WITH OVERLAY ─────────────────── */
        .stats-banner {
            background: linear-gradient(rgba(8, 47, 42, 0.92), rgba(13, 148, 136, 0.92)),
                        url('https://images.unsplash.com/photo-1541829070764-84a7d30dd3f3?auto=format&fit=crop&w=1600&q=80') center/cover no-repeat;
            padding: 4.5rem 2rem;
            color: #ffffff;
        }

        .stats-banner-container {
            max-width: 1280px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 2rem;
        }

        .stat-banner-item {
            text-align: center;
            position: relative;
            padding: 0 1rem;
        }

        .stat-banner-item:not(:last-child)::after {
            content: '';
            position: absolute;
            right: 0;
            top: 15%;
            height: 70%;
            width: 1px;
            background-color: rgba(255, 255, 255, 0.25);
        }

        .stat-banner-icon {
            font-size: 2.2rem;
            color: #ffd700;
            margin-bottom: 0.8rem;
        }

        .stat-banner-number {
            font-size: 2.8rem;
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 0.4rem;
            letter-spacing: -1px;
        }

        .stat-banner-label {
            font-size: 0.95rem;
            font-weight: 600;
            color: #cbd5e1;
        }

        /* ── ECOSYSTEM SECTION ───────────────────────────── */
        .ecosystem-section {
            padding: 5.5rem 2rem;
            background-color: var(--bg-white);
        }

        .ecosystem-container {
            max-width: 1280px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1.15fr 1fr;
            gap: 4rem;
            align-items: center;
        }

        .ecosystem-content h2 {
            font-size: 2.2rem;
            font-weight: 800;
            color: var(--text-dark);
            margin-bottom: 1.2rem;
            letter-spacing: -0.5px;
        }

        .ecosystem-content p {
            font-size: 1rem;
            color: var(--text-muted);
            line-height: 1.7;
            margin-bottom: 2rem;
        }

        .ecosystem-quote-box {
            background-color: #f8fafc;
            border-left: 4px solid var(--primary-blue);
            padding: 2.4rem;
            border-radius: 0 14px 14px 0;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
        }

        .ecosystem-quote-box blockquote {
            font-size: 1.1rem;
            font-style: italic;
            color: #334155;
            line-height: 1.65;
            margin-bottom: 1.2rem;
        }

        .ecosystem-quote-box p {
            font-size: 0.88rem;
            font-weight: 700;
            color: var(--primary-blue);
            margin-bottom: 0;
        }

        /* ── FOOTER ──────────────────────────────────────── */
        footer {
            background-color: var(--footer-bg);
            color: #94a3b8;
            padding: 4.5rem 2rem 2rem 2rem;
        }

        .footer-container {
            max-width: 1280px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1.3fr 0.8fr 0.8fr 1.1fr;
            gap: 3rem;
            padding-bottom: 3rem;
            border-bottom: 1px solid #1e293b;
        }

        .footer-brand .logo-group {
            margin-bottom: 1rem;
        }

        .footer-brand .logo-text h1 {
            color: white;
        }

        .footer-column h4 {
            color: white;
            font-size: 0.95rem;
            font-weight: 700;
            margin-bottom: 1.2rem;
        }

        .footer-links {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 0.7rem;
        }

        .footer-links a {
            font-size: 0.85rem;
            color: #94a3b8;
        }

        .footer-links a:hover {
            color: white;
        }

        .footer-contact-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 0.8rem;
        }

        .footer-contact-item {
            display: flex;
            align-items: flex-start;
            gap: 0.8rem;
            font-size: 0.85rem;
            color: #94a3b8;
        }

        .footer-contact-item i {
            color: var(--primary-blue);
            margin-top: 0.25rem;
        }

        .footer-bottom {
            max-width: 1280px;
            margin: 0 auto;
            padding-top: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .social-links {
            display: flex;
            gap: 1.2rem;
        }

        .social-icon {
            color: #94a3b8;
            font-size: 1.15rem;
            transition: var(--transition);
        }

        .social-icon:hover {
            color: white;
            transform: translateY(-2px);
        }

        .copyright {
            font-size: 0.82rem;
            color: #64748b;
        }

        /* ── RESPONSIVE DESIGN ───────────────────────────── */
        @media (max-width: 1024px) {
            .hero-container {
                grid-template-columns: 1fr;
                gap: 2.5rem;
            }
            .features-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .prodi-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .stats-banner-container {
                grid-template-columns: repeat(2, 1fr);
                gap: 2rem;
            }
            .ecosystem-container {
                grid-template-columns: 1fr;
                gap: 2rem;
            }
            .footer-container {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .nav-menu { display: none; }
            .menu-toggle { display: block; }
            .features-grid { grid-template-columns: 1fr; }
            .prodi-grid { grid-template-columns: 1fr; }
            .stats-banner-container { grid-template-columns: 1fr; }
            .stat-banner-item:not(:last-child)::after { display: none; }
            .footer-container { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<!-- ── HEADER NAVIGATION ───────────────────────────── -->
<header>
    <div class="nav-container">
        <a href="index.php" class="logo-group">
            <div class="logo-emblem">
                <i class="fa-solid fa-graduation-cap"></i>
            </div>
            <div class="logo-text">
                <h1><?= htmlspecialchars($nama_universitas) ?></h1>
                <span>Unggul · Islam · Berkemajuan</span>
            </div>
        </a>

        <ul class="nav-menu">
            <li><a href="index.php" class="nav-link active">Beranda</a></li>
            <li><a href="dosen.php" class="nav-link">Data Dosen</a></li>
            <li><a href="#program-studi" class="nav-link">Program Studi</a></li>
            <li><a href="laporan_dosen.php" class="nav-link">Laporan</a></li>
            <li><a href="api/semantik.php" target="_blank" class="nav-link"><i class="fa-solid fa-share-nodes"></i> Web Semantik</a></li>
        </ul>

        <div style="display: flex; align-items: center; gap: 1rem;">
            <a href="login.php" class="btn-login-nav">
                <i class="fa-solid fa-user"></i> Login
            </a>
        </div>
    </div>
</header>

<!-- ── HERO SECTION ────────────────────────────────── -->
<section class="hero-section">
    <div class="hero-bg-overlay"></div>
    <div class="hero-container">
        
        <!-- Left Content -->
        <div class="hero-content">
            <div class="hero-badge-eyebrow">
                <i class="fa-solid fa-certificate"></i> PORTAL TERPADU SIM DOSEN
            </div>
            <h2>Data Dosen <span>Per Program Studi</span></h2>
            <p class="hero-description">
                Akses, eksplorasi, dan manfaatkan data dosen secara terbuka, terstruktur, dan terhubung untuk mendukung tata kelola universitas yang lebih baik.
            </p>
            <div class="hero-actions">
                <a href="dosen.php" class="btn-primary">
                    <i class="fa-solid fa-magnifying-glass"></i> Lihat Data Dosen
                </a>
                <a href="#ekosistem" class="btn-outline">
                    <i class="fa-solid fa-book-open"></i> Pelajari Web Semantik
                </a>
            </div>
            <p class="hero-quote">“Data yang terhubung, pengetahuan yang lebih luas”</p>
        </div>

        <!-- Right Content: Interactive Laptop Mockup (Sesuai Desain Acuan) -->
        <div class="hero-mockup-wrapper">
            <div class="laptop-frame">
                <div class="laptop-topbar">
                    <div class="browser-dots">
                        <div class="dot dot-red"></div>
                        <div class="dot dot-yellow"></div>
                        <div class="dot dot-green"></div>
                    </div>
                    <div class="browser-bar">
                        <i class="fa-solid fa-lock" style="font-size: 0.65rem; color: #10b981;"></i>
                        <span>https://simdosen.umb.ac.id/rdf/dosen</span>
                    </div>
                </div>

                <div class="laptop-body">
                    <div class="graph-card">
                        <div class="graph-central-badge">
                            <i class="fa-solid fa-share-nodes"></i>
                            <span>RDF SEMANTIC GRAPH</span>
                        </div>

                        <div class="graph-nodes-grid">
                            <div class="graph-node-pill">
                                <div class="node-icon" style="background:#0d9488;">
                                    <i class="fa-solid fa-chalkboard-user"></i>
                                </div>
                                <div class="node-info">
                                    <strong>Data Dosen</strong>
                                    <span><?= $totDosen ?> Dosen Terdata</span>
                                </div>
                            </div>

                            <div class="graph-node-pill">
                                <div class="node-icon" style="background:#7c3aed;">
                                    <i class="fa-solid fa-graduation-cap"></i>
                                </div>
                                <div class="node-info">
                                    <strong>Program Studi</strong>
                                    <span><?= $totProdi ?> Prodi Aktif</span>
                                </div>
                            </div>

                            <div class="graph-node-pill">
                                <div class="node-icon" style="background:#059669;">
                                    <i class="fa-solid fa-award"></i>
                                </div>
                                <div class="node-info">
                                    <strong>Jabatan & Serdos</strong>
                                    <span><?= $totSerdos ?> Tersertifikasi</span>
                                </div>
                            </div>

                            <div class="graph-node-pill">
                                <div class="node-icon" style="background:#ea580c;">
                                    <i class="fa-solid fa-building-columns"></i>
                                </div>
                                <div class="node-info">
                                    <strong>Universitas</strong>
                                    <span><?= $totFakultas ?> Fakultas</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Side linked data badge -->
            <div class="semantic-floating-badge">
                <i class="fa-solid fa-layer-group" style="color: #ffd700;"></i>
                <span>RDF • OWL • SPARQL • LINKED DATA</span>
            </div>
        </div>

    </div>
</section>

<!-- ── FITUR UTAMA ─────────────────────────────────── -->
<section class="features-section">
    <div class="section-container">
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-circle-icon">
                    <i class="fa-solid fa-database"></i>
                </div>
                <h3>Data Terintegrasi</h3>
                <p>Data dosen terhubung dengan program studi, fakultas, dan informasi akademik lainnya.</p>
            </div>

            <div class="feature-card">
                <div class="feature-circle-icon">
                    <i class="fa-solid fa-share-nodes"></i>
                </div>
                <h3>Standar Terbuka</h3>
                <p>Menggunakan teknologi Web Semantik (RDF, OWL, SPARQL) untuk interoperabilitas data.</p>
            </div>

            <div class="feature-card">
                <div class="feature-circle-icon">
                    <i class="fa-solid fa-chart-simple"></i>
                </div>
                <h3>Mudah Diakses</h3>
                <p>Pencarian dan visualisasi data yang cepat, responsif, dan interaktif dari berbagai perangkat.</p>
            </div>

            <div class="feature-card">
                <div class="feature-circle-icon">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <h3>Mendukung Transparansi</h3>
                <p>Data yang terbuka untuk mendukung akreditasi, riset, dan pengambilan keputusan akademik.</p>
            </div>
        </div>
    </div>
</section>

<!-- ── DAFTAR PROGRAM STUDI ────────────────────────── -->
<section class="prodi-section" id="program-studi">
    <div class="section-container">
        <div class="section-header-flex">
            <div>
                <h2>Daftar Program Studi</h2>
                <p>Pilih program studi untuk melihat persebaran data dosen secara detail</p>
            </div>
            <a href="dosen.php" class="link-view-all">
                Lihat Semua Program Studi <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>

        <div class="prodi-grid">
            <?php foreach ($prodiList as $p): ?>
                <a href="dosen.php?q=<?= urlencode($p['program_studi']) ?>" class="prodi-card">
                    <div class="prodi-info-wrap">
                        <div class="prodi-icon-badge" style="background: <?= htmlspecialchars($p['color']) ?>;">
                            <i class="fa-solid <?= htmlspecialchars($p['icon']) ?>"></i>
                        </div>
                        <div class="prodi-text">
                            <strong><?= htmlspecialchars($p['program_studi']) ?></strong>
                            <span><?= (int)$p['total_dosen'] ?> Dosen</span>
                        </div>
                    </div>
                    <i class="fa-solid fa-arrow-right prodi-arrow"></i>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ── STATS BANNER ────────────────────────────────── -->
<section class="stats-banner">
    <div class="stats-banner-container">
        <div class="stat-banner-item">
            <div class="stat-banner-icon"><i class="fa-solid fa-chalkboard-user"></i></div>
            <div class="stat-banner-number"><?= number_format($totDosen, 0, ',', '.') ?></div>
            <div class="stat-banner-label">Total Dosen</div>
        </div>
        <div class="stat-banner-item">
            <div class="stat-banner-icon"><i class="fa-solid fa-users"></i></div>
            <div class="stat-banner-number"><?= number_format($totProdi, 0, ',', '.') ?></div>
            <div class="stat-banner-label">Program Studi</div>
        </div>
        <div class="stat-banner-item">
            <div class="stat-banner-icon"><i class="fa-solid fa-building-columns"></i></div>
            <div class="stat-banner-number"><?= number_format($totFakultas, 0, ',', '.') ?></div>
            <div class="stat-banner-label">Fakultas</div>
        </div>
        <div class="stat-banner-item">
            <div class="stat-banner-icon"><i class="fa-solid fa-globe"></i></div>
            <div class="stat-banner-number"><i class="fa-solid fa-check text-success"></i> Aktif</div>
            <div class="stat-banner-label">Data Terhubung Web Semantik</div>
        </div>
    </div>
</section>

<!-- ── EKOSISTEM DATA TERBUKA ──────────────────────── -->
<section class="ecosystem-section" id="ekosistem">
    <div class="ecosystem-container">
        <div class="ecosystem-content">
            <h2>Membangun Ekosistem Data Terbuka</h2>
            <p>
                Dengan pendekatan <strong>Web Semantik</strong>, data dosen tidak hanya disimpan, tetapi juga dipahami maknanya, dihubungkan secara ontologis, dan dimanfaatkan oleh berbagai aplikasi untuk mendukung pendidikan, penelitian, dan inovasi.
            </p>
            <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                <a href="api/semantik.php" target="_blank" class="btn-primary">
                    <i class="fa-solid fa-share-nodes"></i> Endpoint JSON-LD Semantik
                </a>
                <a href="api_docs.php" class="btn-outline">
                    <i class="fa-solid fa-book"></i> Dokumentasi API REST
                </a>
            </div>
        </div>

        <div class="ecosystem-quote-box">
            <blockquote>
                “Web Semantik memungkinkan data di universitas tidak hanya dilihat oleh manusia, tetapi juga dipahami oleh mesin.”
            </blockquote>
            <p>— Menuju Universitas yang Lebih Cerdas & Terintegrasi</p>
        </div>
    </div>
</section>

<!-- ── FOOTER ──────────────────────────────────────── -->
<footer>
    <div class="footer-container">
        <div class="footer-brand">
            <div class="logo-group">
                <div class="logo-emblem">
                    <i class="fa-solid fa-graduation-cap"></i>
                </div>
                <div class="logo-text">
                    <h1><?= htmlspecialchars($nama_universitas) ?></h1>
                    <span>Unggul · Islam · Berkemajuan</span>
                </div>
            </div>
            <p style="font-size: 0.85rem; color: #94a3b8; line-height: 1.6; margin-top: 1rem;">
                Portal resmi pengelolaan data dosen dan ontologi akademik terintegrasi Universitas Muhammadiyah Bengkulu.
            </p>
        </div>

        <div class="footer-column">
            <h4>Tautan Cepat</h4>
            <ul class="footer-links">
                <li><a href="index.php">Beranda</a></li>
                <li><a href="dosen.php">Data Dosen</a></li>
                <li><a href="#program-studi">Program Studi</a></li>
                <li><a href="laporan_dosen.php">Rekap Laporan</a></li>
                <li><a href="login.php">Portal Pengelola</a></li>
            </ul>
        </div>

        <div class="footer-column">
            <h4>Sumber Daya</h4>
            <ul class="footer-links">
                <li><a href="api/semantik.php" target="_blank">Skema RDF / JSON-LD</a></li>
                <li><a href="sinkronisasi.php">Integrasi SIM Mahasiswa</a></li>
            </ul>
        </div>

        <div class="footer-column">
            <h4>Kontak Kampus</h4>
            <ul class="footer-contact-list">
                <li class="footer-contact-item">
                    <i class="fa-solid fa-location-dot"></i>
                    <span>Jl. Bali, Teluk Segara, Kota Bengkulu, Bengkulu 38119</span>
                </li>
                <li class="footer-contact-item">
                    <i class="fa-solid fa-envelope"></i>
                    <span>simdosen@umb.ac.id</span>
                </li>
                <li class="footer-contact-item">
                    <i class="fa-solid fa-phone"></i>
                    <span>+62 736 22765</span>
                </li>
            </ul>
        </div>
    </div>

    <div class="footer-bottom">
        <div class="copyright">
            © <?= $tahun ?> <?= htmlspecialchars($nama_universitas) ?>. All rights reserved.
        </div>
        <div class="social-links">
            <a href="#" class="social-icon" aria-label="YouTube"><i class="fa-brands fa-youtube"></i></a>
            <a href="#" class="social-icon" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
            <a href="#" class="social-icon" aria-label="Facebook"><i class="fa-brands fa-facebook"></i></a>
            <a href="#" class="social-icon" aria-label="LinkedIn"><i class="fa-brands fa-linkedin"></i></a>
        </div>
    </div>
</footer>

</body>
</html>

<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/koneksi.php';
require_once __DIR__ . '/includes/helpers.php';

require_login();

// ─── Filter parameters ───────────────────────────
$filterFakultas = trim($_GET['fakultas'] ?? '');
$filterProdi    = trim($_GET['prodi'] ?? '');
$filterJabatan  = (int)($_GET['id_jabatan'] ?? 0);
$filterSerdos   = trim($_GET['serdos'] ?? '');
$filterAktivitas= trim($_GET['status_aktivitas'] ?? '');

$where = ["1=1"];
$params = [];
$types = '';

if ($filterFakultas !== '') {
    $where[] = "p.fakultas = ?";
    $params[] = $filterFakultas;
    $types .= 's';
}
if ($filterProdi !== '') {
    $where[] = "p.program_studi = ?";
    $params[] = $filterProdi;
    $types .= 's';
}
if ($filterJabatan > 0) {
    $where[] = "d.id_jabatan = ?";
    $params[] = $filterJabatan;
    $types .= 'i';
}
if ($filterSerdos !== '') {
    $where[] = "d.status_serdos = ?";
    $params[] = $filterSerdos;
    $types .= 's';
}
if ($filterAktivitas !== '') {
    $where[] = "d.status_aktivitas = ?";
    $params[] = $filterAktivitas;
    $types .= 's';
}

$whereClause = implode(" AND ", $where);

// ─── Query Data Dosen ───────────────────────────
$sql = "SELECT d.*, 
               jf.nama_jabatan, 
               kp.nama_pangkat, kp.golongan, kp.ruang,
               pend.jenjang, 
               ri.nama_rumpun, 
               sk.status_pegawai, sk.ikatan_kerja,
               p.universitas, p.fakultas, p.program_studi
        FROM dosen d
        LEFT JOIN jabatan_fungsional jf ON d.id_jabatan = jf.id_jabatan
        LEFT JOIN kepangkatan kp ON d.id_kepangkatan = kp.id_kepangkatan
        LEFT JOIN pendidikan pend ON d.id_pendidikan = pend.id_pendidikan
        LEFT JOIN rumpun_ilmu ri ON d.id_rumpun_ilmu = ri.id_rumpun_ilmu
        LEFT JOIN status_kepegawaian sk ON d.id_status_kepegawaian = sk.id_status_kepegawaian
        LEFT JOIN penempatan p ON d.id_penempatan = p.id_penempatan
        WHERE $whereClause
        ORDER BY p.fakultas ASC, p.program_studi ASC, d.nama ASC";

$stmt = $conn->prepare($sql);
if ($types) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$res = $stmt->get_result();
$list = [];
$totAktif = 0;
$totSerdos = 0;
while ($row = $res->fetch_assoc()) {
    $list[] = $row;
    if ($row['status_aktivitas'] === 'Aktif') $totAktif++;
    if ($row['status_serdos'] === 'Sudah') $totSerdos++;
}
$totalDosen = count($list);

// ─── Export CSV Handler ──────────────────────────
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $filename = 'Laporan_Dosen_UMB_' . date('Ymd_His') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $output = fopen('php://output', 'w');
    fputs($output, "\xEF\xBB\xBF"); // UTF-8 BOM
    fputcsv($output, ['No', 'Nama Dosen', 'NIP', 'NIDN/No Reg', 'NUPTK', 'Jabatan Fungsional', 'Pangkat/Golongan', 'Pendidikan', 'Rumpun Ilmu', 'Fakultas', 'Program Studi', 'Status Pegawai', 'Status Serdos', 'Status Aktivitas']);
    foreach ($list as $idx => $d) {
        $pangkatStr = $d['nama_pangkat'] ? $d['nama_pangkat'] . ' (' . $d['golongan'] . '/' . $d['ruang'] . ')' : '-';
        fputcsv($output, [
            $idx + 1,
            $d['nama'],
            $d['nip'] ?: '-',
            $d['nomor_registrasi'] ?: '-',
            $d['nuptk'] ?: '-',
            $d['nama_jabatan'] ?: '-',
            $pangkatStr,
            $d['jenjang'] ?: '-',
            $d['nama_rumpun'] ?: '-',
            $d['fakultas'] ?: '-',
            $d['program_studi'] ?: '-',
            $d['status_pegawai'] ?: '-',
            $d['status_serdos'],
            $d['status_aktivitas']
        ]);
    }
    fclose($output);
    exit;
}

// Dropdowns for filters
$optFakultas = $conn->query("SELECT DISTINCT fakultas FROM penempatan WHERE fakultas IS NOT NULL AND fakultas != '' ORDER BY fakultas ASC");
$optProdi    = $conn->query("SELECT DISTINCT program_studi FROM penempatan WHERE program_studi IS NOT NULL AND program_studi != '' ORDER BY program_studi ASC");
$optJabatan  = $conn->query("SELECT id_jabatan, nama_jabatan FROM jabatan_fungsional WHERE aktif = 1 ORDER BY id_jabatan ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan & Rekapitulasi Data Dosen — SIM Dosen UMB</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .report-header {
            text-align: center;
            border-bottom: 3px double #134e4a;
            padding-bottom: 16px;
            margin-bottom: 24px;
            display: none;
        }
        .report-header h2 {
            margin: 0;
            font-size: 1.35rem;
            color: #134e4a;
            text-transform: uppercase;
            font-weight: 800;
        }
        .report-header h3 {
            margin: 4px 0;
            font-size: 1.15rem;
            color: #0d9488;
            font-weight: 700;
        }
        .report-header p {
            margin: 2px 0;
            font-size: 0.88rem;
            color: #555;
        }
        .signature-section {
            margin-top: 40px;
            display: none;
            justify-content: space-between;
            page-break-inside: avoid;
        }
        .signature-box {
            text-align: center;
            width: 250px;
        }
        .signature-space {
            height: 70px;
        }

        @media print {
            .topbar, .sidebar, .filter-panel, .page-heading-flex .btn-group, .btn, .no-print {
                display: none !important;
            }
            .main-content {
                margin: 0 !important;
                padding: 10px !important;
            }
            .report-header, .signature-section {
                display: flex !important;
            }
            .report-header {
                display: block !important;
            }
            .dashboard-panel {
                border: none !important;
                box-shadow: none !important;
                padding: 0 !important;
            }
            .data-table {
                font-size: 0.82rem;
                width: 100%;
                border-collapse: collapse;
            }
            .data-table th, .data-table td {
                border: 1px solid #333 !important;
                padding: 6px 8px !important;
            }
            .data-table thead th {
                background: #f1f5f9 !important;
                color: #000 !important;
            }
            .badge {
                border: 1px solid #666;
                color: #000 !important;
                background: transparent !important;
                padding: 2px 5px;
            }
        }
    </style>
</head>
<body class="app-body">

<?php include 'topbar.php'; ?>
<?php include 'sidebar.php'; ?>

<main class="main-content animate-in">
    <!-- Header Web View -->
    <div class="page-heading-flex no-print">
        <div class="page-heading">
            <span class="eyebrow">REKAPITULASI & LAPORAN</span>
            <h1>Laporan Data Dosen</h1>
            <p>Universitas Muhammadiyah Bengkulu</p>
        </div>
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <?php
                $csvParams = $_GET;
                $csvParams['export'] = 'csv';
                $csvUrl = 'laporan_dosen.php?' . http_build_query($csvParams);
            ?>
            <a href="<?= $csvUrl ?>" class="btn btn-outline btn-lg"><i class="fa-solid fa-file-excel text-success"></i> Unduh CSV (Excel)</a>
            <button onclick="window.print()" class="btn btn-primary btn-lg"><i class="fa-solid fa-print"></i> Cetak Laporan (PDF)</button>
            <a href="dosen.php" class="btn btn-outline btn-lg"><i class="fa-solid fa-arrow-left"></i> Kembali</a>
        </div>
    </div>

    <!-- Filter Form -->
    <div class="dashboard-panel filter-panel no-print" style="margin-bottom: 24px;">
        <div style="padding: 20px 24px;">
            <form method="get" action="laporan_dosen.php" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)) 120px; gap: 14px; align-items: end;">
                <div class="form-group" style="margin: 0;">
                    <label style="font-size: 0.85rem; margin-bottom: 4px; display: block; font-weight: 600;">Fakultas</label>
                    <select name="fakultas" class="form-control" style="padding: 8px 12px; font-size: 0.88rem;">
                        <option value="">-- Semua Fakultas --</option>
                        <?php while ($f = $optFakultas->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($f['fakultas']) ?>" <?= $filterFakultas === $f['fakultas'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($f['fakultas']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group" style="margin: 0;">
                    <label style="font-size: 0.85rem; margin-bottom: 4px; display: block; font-weight: 600;">Program Studi</label>
                    <select name="prodi" class="form-control" style="padding: 8px 12px; font-size: 0.88rem;">
                        <option value="">-- Semua Prodi --</option>
                        <?php while ($p = $optProdi->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($p['program_studi']) ?>" <?= $filterProdi === $p['program_studi'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($p['program_studi']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group" style="margin: 0;">
                    <label style="font-size: 0.85rem; margin-bottom: 4px; display: block; font-weight: 600;">Jabatan Fungsional</label>
                    <select name="id_jabatan" class="form-control" style="padding: 8px 12px; font-size: 0.88rem;">
                        <option value="0">-- Semua Jabatan --</option>
                        <?php while ($j = $optJabatan->fetch_assoc()): ?>
                            <option value="<?= $j['id_jabatan'] ?>" <?= $filterJabatan === (int)$j['id_jabatan'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($j['nama_jabatan']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group" style="margin: 0;">
                    <label style="font-size: 0.85rem; margin-bottom: 4px; display: block; font-weight: 600;">Sertifikasi Dosen</label>
                    <select name="serdos" class="form-control" style="padding: 8px 12px; font-size: 0.88rem;">
                        <option value="">-- Semua Status --</option>
                        <option value="Sudah" <?= $filterSerdos === 'Sudah' ? 'selected' : '' ?>>Sudah Serdos</option>
                        <option value="Belum" <?= $filterSerdos === 'Belum' ? 'selected' : '' ?>>Belum Serdos</option>
                    </select>
                </div>

                <div class="form-group" style="margin: 0;">
                    <label style="font-size: 0.85rem; margin-bottom: 4px; display: block; font-weight: 600;">Status Aktivitas</label>
                    <select name="status_aktivitas" class="form-control" style="padding: 8px 12px; font-size: 0.88rem;">
                        <option value="">-- Semua Status --</option>
                        <option value="Aktif" <?= $filterAktivitas === 'Aktif' ? 'selected' : '' ?>>Aktif</option>
                        <option value="Cuti" <?= $filterAktivitas === 'Cuti' ? 'selected' : '' ?>>Cuti</option>
                        <option value="Tidak Aktif" <?= $filterAktivitas === 'Tidak Aktif' ? 'selected' : '' ?>>Tidak Aktif</option>
                    </select>
                </div>

                <div style="display:flex; gap:6px;">
                    <button type="submit" class="btn btn-primary btn-sm" style="flex:1;"><i class="fa-solid fa-filter"></i> Filter</button>
                    <a href="laporan_dosen.php" class="btn btn-outline btn-sm" title="Reset Filter"><i class="fa-solid fa-xmark"></i></a>
                </div>
            </form>
        </div>
    </div>

    <!-- KPI Summary Cards -->
    <div class="stats-grid no-print" style="margin-bottom: 24px;">
        <div class="stat-card">
            <div class="stat-icon stat-icon-blue"><i class="fa-solid fa-chalkboard-user"></i></div>
            <div>
                <span>Total Data Dosen</span>
                <strong><?= number_format($totalDosen, 0, ',', '.') ?></strong>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-icon-green"><i class="fa-solid fa-user-check"></i></div>
            <div>
                <span>Dosen Status Aktif</span>
                <strong><?= number_format($totAktif, 0, ',', '.') ?></strong>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-icon-orange"><i class="fa-solid fa-award"></i></div>
            <div>
                <span>Tersertifikasi (Serdos)</span>
                <strong><?= number_format($totSerdos, 0, ',', '.') ?></strong>
            </div>
        </div>
    </div>

    <!-- Official Report Header (Visible on Print) -->
    <div class="report-header">
        <h2>UNIVERSITAS MUHAMMADIYAH BENGKULU</h2>
        <h3>LAPORAN DATA POKOK DOSEN & TENAGA PENDIDIK</h3>
        <p>Alamat: Jl. Bali, Kota Bengkulu, Indonesia | Portal: https://umb.ac.id</p>
        <p style="margin-top: 6px; font-weight: 600; font-size: 0.9rem;">
            <?= $filterFakultas ? "Fakultas: $filterFakultas | " : "" ?>
            <?= $filterProdi ? "Program Studi: $filterProdi | " : "" ?>
            Tanggal Rekap: <?= date('d F Y') ?>
        </p>
    </div>

    <!-- Report Table -->
    <div class="dashboard-panel">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 40px;">No</th>
                        <th>Nama Dosen & Gelar</th>
                        <th>NIP / NIDN</th>
                        <th>Jabatan Fungsional</th>
                        <th>Pangkat / Golongan</th>
                        <th>Program Studi</th>
                        <th>Pendidikan</th>
                        <th>Serdos</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($list)): ?>
                    <tr><td colspan="9" class="empty-state">Tidak ada data dosen yang sesuai dengan kriteria filter.</td></tr>
                <?php else: ?>
                    <?php foreach ($list as $i => $d): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><strong><?= htmlspecialchars($d['nama']) ?></strong></td>
                        <td style="font-family: monospace; font-size: 0.85rem;">
                            <?= htmlspecialchars($d['nip'] ?: ($d['nomor_registrasi'] ?: '-')) ?>
                        </td>
                        <td><?= htmlspecialchars($d['nama_jabatan'] ?? '-') ?></td>
                        <td>
                            <?php if ($d['nama_pangkat']): ?>
                                <?= htmlspecialchars($d['nama_pangkat']) ?> (<?= htmlspecialchars($d['golongan']) ?>/<?= htmlspecialchars($d['ruang']) ?>)
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($d['program_studi'] ?? '-') ?></td>
                        <td><span class="badge badge-info"><?= htmlspecialchars($d['jenjang'] ?? '-') ?></span></td>
                        <td>
                            <span class="badge <?= $d['status_serdos'] === 'Sudah' ? 'badge-success' : 'badge-danger' ?>">
                                <?= htmlspecialchars($d['status_serdos']) ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge <?= $d['status_aktivitas'] === 'Aktif' ? 'badge-success' : 'badge-warning' ?>">
                                <?= htmlspecialchars($d['status_aktivitas']) ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Signature Section (Visible on Print) -->
    <div class="signature-section">
        <div class="signature-box">
            <p>Mengetahui,<br><strong>Kepala BAAK UMB</strong></p>
            <div class="signature-space"></div>
            <p><strong>( ............................................ )</strong><br>NIP. .....................................</p>
        </div>
        <div class="signature-box">
            <p>Bengkulu, <?= date('d F Y') ?><br><strong>Wakil Rektor I (Akademik)</strong></p>
            <div class="signature-space"></div>
            <p><strong>( ............................................ )</strong><br>NIP. .....................................</p>
        </div>
    </div>
</main>

</body>
</html>

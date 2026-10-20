<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/koneksi.php';
require_once __DIR__ . '/includes/helpers.php';

require_login();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    redirect('dosen.php');
}

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
        WHERE d.id_dosen = ?
        LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $id);
$stmt->execute();
$dosen = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$dosen) {
    $_SESSION['flash_error'] = 'Data dosen tidak ditemukan.';
    redirect('dosen.php');
}

// Generate initials for avatar
$words = explode(' ', preg_replace('/[.,]/', '', $dosen['nama']));
$initials = '';
foreach (array_slice($words, 0, 2) as $w) {
    if (!empty($w)) $initials .= strtoupper($w[0]);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Dosen: <?= htmlspecialchars($dosen['nama']) ?> — SIM Dosen UMB</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .profile-hero {
            background: linear-gradient(135deg, #134e4a 0%, #0d9488 100%);
            border-radius: var(--radius-lg);
            padding: 32px 36px;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 28px;
            margin-bottom: 28px;
            box-shadow: 0 12px 35px rgba(13, 148, 136, 0.2);
            flex-wrap: wrap;
        }
        .avatar-circle {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            background: linear-gradient(135deg, #f2b544, #e29b1b);
            color: #134e4a;
            font-size: 2.2rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 4px solid rgba(255,255,255,0.3);
            flex-shrink: 0;
        }
        .profile-info h1 {
            color: #fff;
            margin: 0 0 6px 0;
            font-size: 1.85rem;
            font-weight: 800;
        }
        .profile-meta {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
            font-size: 0.95rem;
            color: rgba(255,255,255,0.85);
            margin-bottom: 12px;
        }
        .profile-badges {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(360px, 1fr));
            gap: 24px;
            margin-bottom: 28px;
        }
        .info-card {
            background: #fff;
            border-radius: var(--radius-md);
            border: 1px solid var(--border);
            padding: 24px 28px;
            box-shadow: var(--shadow-sm);
        }
        .info-card h3 {
            font-size: 1.15rem;
            font-weight: 700;
            margin: 0 0 18px 0;
            padding-bottom: 12px;
            border-bottom: 2px solid var(--gray-100);
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--navy);
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px dashed var(--gray-200);
            font-size: 0.94rem;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            color: var(--gray-600);
            font-weight: 500;
        }
        .info-value {
            color: var(--gray-900);
            font-weight: 600;
            text-align: right;
            max-width: 60%;
        }
        @media print {
            .topbar, .sidebar, .btn, .page-heading-flex .btn-group {
                display: none !important;
            }
            .main-content {
                margin: 0 !important;
                padding: 0 !important;
            }
            .profile-hero {
                background: #f8fafc !important;
                color: #000 !important;
                border: 1px solid #ccc;
            }
            .profile-info h1 {
                color: #000 !important;
            }
        }
    </style>
</head>
<body class="app-body">

<?php include 'topbar.php'; ?>
<?php include 'sidebar.php'; ?>

<main class="main-content animate-in">
    <div class="page-heading-flex">
        <div class="page-heading">
            <span class="eyebrow">PROFIL DOSEN</span>
            <h1>Biodata Dosen</h1>
            <p>Universitas Muhammadiyah Bengkulu</p>
        </div>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <button onclick="window.print()" class="btn btn-outline">🖨 Cetak Profil</button>
            <a href="dosen_edit.php?id=<?= $dosen['id_dosen'] ?>" class="btn btn-primary">✏️ Edit Dosen</a>
            <a href="dosen.php" class="btn btn-outline">⬅ Kembali</a>
        </div>
    </div>

    <!-- Hero Card -->
    <div class="profile-hero">
        <div class="avatar-circle"><?= htmlspecialchars($initials) ?></div>
        <div class="profile-info">
            <h1><?= htmlspecialchars($dosen['nama']) ?></h1>
            <div class="profile-meta">
                <span><strong>NIP:</strong> <?= htmlspecialchars($dosen['nip'] ?: '-') ?></span>
                <span>•</span>
                <span><strong>No. Registrasi / NIDN:</strong> <?= htmlspecialchars($dosen['nomor_registrasi'] ?: '-') ?></span>
                <span>•</span>
                <span><strong>NUPTK:</strong> <?= htmlspecialchars($dosen['nuptk'] ?: '-') ?></span>
            </div>
            <div class="profile-badges">
                <span class="badge <?= $dosen['status_aktivitas'] === 'Aktif' ? 'badge-success' : 'badge-warning' ?>">
                    Aktivitas: <?= htmlspecialchars($dosen['status_aktivitas']) ?>
                </span>
                <span class="badge <?= $dosen['status_serdos'] === 'Sudah' ? 'badge-info' : 'badge-danger' ?>">
                    Sertifikasi Dosen: <?= htmlspecialchars($dosen['status_serdos']) ?>
                </span>
                <?php if ($dosen['nama_jabatan']): ?>
                    <span class="badge" style="background:#e0e7ff; color:#3730a3;">
                        <?= htmlspecialchars($dosen['nama_jabatan']) ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Information Cards -->
    <div class="info-grid">
        <!-- Identitas & Akademik -->
        <div class="info-card">
            <h3>🏛 Penempatan & Rumpun Ilmu</h3>
            <div class="info-row">
                <span class="info-label">Perguruan Tinggi</span>
                <span class="info-value"><?= htmlspecialchars($dosen['universitas'] ?? 'Universitas Muhammadiyah Bengkulu') ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Fakultas</span>
                <span class="info-value"><?= htmlspecialchars($dosen['fakultas'] ?? '-') ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Program Studi</span>
                <span class="info-value"><?= htmlspecialchars($dosen['program_studi'] ?? '-') ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Rumpun Ilmu</span>
                <span class="info-value"><?= htmlspecialchars($dosen['nama_rumpun'] ?? '-') ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Pendidikan Terakhir</span>
                <span class="info-value"><?= htmlspecialchars($dosen['jenjang'] ?? '-') ?></span>
            </div>
        </div>

        <!-- Fungsional & Kepegawaian -->
        <div class="info-card">
            <h3>🎖 Fungsional & Kepegawaian</h3>
            <div class="info-row">
                <span class="info-label">Jabatan Fungsional</span>
                <span class="info-value"><?= htmlspecialchars($dosen['nama_jabatan'] ?? '-') ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Pangkat & Golongan</span>
                <span class="info-value">
                    <?php if ($dosen['nama_pangkat']): ?>
                        <?= htmlspecialchars($dosen['nama_pangkat']) ?> (<?= htmlspecialchars($dosen['golongan']) ?>/<?= htmlspecialchars($dosen['ruang']) ?>)
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </span>
            </div>
            <div class="info-row">
                <span class="info-label">Status Kepegawaian</span>
                <span class="info-value"><?= htmlspecialchars($dosen['status_pegawai'] ?? '-') ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Ikatan Kerja</span>
                <span class="info-value"><?= htmlspecialchars($dosen['ikatan_kerja'] ?? '-') ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Status Serdos</span>
                <span class="info-value"><?= htmlspecialchars($dosen['status_serdos']) ?></span>
            </div>
        </div>
    </div>

    <!-- Catatan & Riwayat -->
    <div class="info-card" style="margin-bottom: 24px;">
        <h3>📝 Catatan & Keterangan</h3>
        <p style="margin: 0; line-height: 1.7; color: var(--gray-700);">
            <?= !empty($dosen['keterangan']) ? nl2br(htmlspecialchars($dosen['keterangan'])) : '<em>Tidak ada catatan tambahan untuk dosen ini.</em>' ?>
        </p>
        <div style="margin-top: 18px; padding-top: 14px; border-top: 1px solid var(--gray-200); font-size: 0.85rem; color: var(--gray-500); display: flex; justify-content: space-between;">
            <span>Dibuat: <?= htmlspecialchars($dosen['created_at']) ?></span>
            <span>Diperbarui: <?= htmlspecialchars($dosen['updated_at']) ?></span>
        </div>
    </div>
</main>

</body>
</html>

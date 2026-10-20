<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/koneksi.php';

require_login();

$namaLengkap = $_SESSION['nama_lengkap'] ?? 'Pengguna';
$role         = $_SESSION['role'] ?? '';

// ─── Statistik Dashboard ──────────────────────────
$totalDosen       = 0;
$totalPenempatan  = 0;
$totalAktif       = 0;
$totalSerdos      = 0;

$res = $conn->query("SELECT COUNT(*) AS total FROM dosen");
if ($res) $totalDosen = (int)$res->fetch_assoc()['total'];

$res = $conn->query("SELECT COUNT(*) AS total FROM penempatan WHERE aktif = 1");
if ($res) $totalPenempatan = (int)$res->fetch_assoc()['total'];

$res = $conn->query("SELECT COUNT(*) AS total FROM dosen WHERE status_aktivitas = 'Aktif'");
if ($res) $totalAktif = (int)$res->fetch_assoc()['total'];

$res = $conn->query("SELECT COUNT(*) AS total FROM dosen WHERE status_serdos = 'Sudah'");
if ($res) $totalSerdos = (int)$res->fetch_assoc()['total'];

// ─── 5 dosen terakhir ditambahkan ────────────────
$dosenTerbaru = [];
$sql = "SELECT d.id_dosen, d.nama, d.status_aktivitas, d.created_at,
               p.program_studi, jf.nama_jabatan
        FROM dosen d
        LEFT JOIN penempatan p ON d.id_penempatan = p.id_penempatan
        LEFT JOIN jabatan_fungsional jf ON d.id_jabatan = jf.id_jabatan
        ORDER BY d.created_at DESC
        LIMIT 5";
$res = $conn->query($sql);
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $dosenTerbaru[] = $row;
    }
}

// ─── Data Visualisasi Chart ───────────────────────
$chartJabatanLabels = [];
$chartJabatanValues = [];
$resJ = $conn->query("SELECT COALESCE(jf.nama_jabatan, 'Belum Ditentukan') AS jabatan, COUNT(d.id_dosen) AS total 
                      FROM dosen d 
                      LEFT JOIN jabatan_fungsional jf ON d.id_jabatan = jf.id_jabatan 
                      GROUP BY jf.nama_jabatan ORDER BY total DESC");
if ($resJ) {
    while ($r = $resJ->fetch_assoc()) {
        $chartJabatanLabels[] = $r['jabatan'];
        $chartJabatanValues[] = (int)$r['total'];
    }
}

$chartProdiLabels = [];
$chartProdiValues = [];
$resP = $conn->query("SELECT COALESCE(p.program_studi, 'Belum Ditempatkan') AS prodi, COUNT(d.id_dosen) AS total 
                      FROM dosen d 
                      LEFT JOIN penempatan p ON d.id_penempatan = p.id_penempatan 
                      GROUP BY p.program_studi ORDER BY total DESC LIMIT 6");
if ($resP) {
    while ($r = $resP->fetch_assoc()) {
        $chartProdiLabels[] = $r['prodi'];
        $chartProdiValues[] = (int)$r['total'];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — SIM Dosen UMB</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="app-body">

<?php include 'topbar.php'; ?>
<?php include 'sidebar.php'; ?>

<main class="main-content animate-in">
    <!-- Welcome Card -->
    <div class="welcome-card">
        <div>
            <span class="eyebrow"><i class="fa-solid fa-sparkles"></i> DASHBOARD SIM DOSEN</span>
            <h2>Selamat datang, <?= htmlspecialchars($namaLengkap) ?>!</h2>
            <p>Kelola data dosen Universitas Muhammadiyah Bengkulu dari satu portal terintegrasi Web Semantik & REST API.</p>
        </div>
        <div style="display: flex; gap: 10px; flex-wrap: wrap; z-index: 2;">
            <a href="dosen_tambah.php" class="btn" style="background: rgba(255,255,255,0.18); border: 1px solid rgba(255,255,255,0.3); color: white;">
                <i class="fa-solid fa-user-plus"></i> Tambah Dosen
            </a>
            <a href="laporan_dosen.php" class="btn" style="background: rgba(255,255,255,0.18); border: 1px solid rgba(255,255,255,0.3); color: white;">
                <i class="fa-solid fa-file-invoice"></i> Rekap Laporan
            </a>
            <a href="dosen.php" class="btn btn-primary" style="background: #ffffff; color: var(--primary-blue); box-shadow: 0 4px 14px rgba(0,0,0,0.15);">
                <i class="fa-solid fa-chalkboard-user"></i> Data Dosen
            </a>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon stat-icon-blue">
                <i class="fa-solid fa-chalkboard-user"></i>
            </div>
            <div>
                <span>Total Dosen</span>
                <strong><?= number_format($totalDosen, 0, ',', '.') ?></strong>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-icon-green">
                <i class="fa-solid fa-user-check"></i>
            </div>
            <div>
                <span>Dosen Aktif</span>
                <strong><?= number_format($totalAktif, 0, ',', '.') ?></strong>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-icon-orange">
                <i class="fa-solid fa-award"></i>
            </div>
            <div>
                <span>Tersertifikasi (Serdos)</span>
                <strong><?= number_format($totalSerdos, 0, ',', '.') ?></strong>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-icon-purple">
                <i class="fa-solid fa-building-columns"></i>
            </div>
            <div>
                <span>Penempatan Aktif</span>
                <strong><?= number_format($totalPenempatan, 0, ',', '.') ?></strong>
            </div>
        </div>
    </div>

    <!-- Visualisasi Chart -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap: 24px; margin-bottom: 24px;">
        <div class="dashboard-panel">
            <div class="panel-heading">
                <h2><i class="fa-solid fa-chart-pie text-primary"></i> Sebaran Jabatan Fungsional</h2>
            </div>
            <div style="padding: 20px; position: relative; height: 260px; display: flex; align-items: center; justify-content: center;">
                <canvas id="chartJabatan"></canvas>
            </div>
        </div>

        <div class="dashboard-panel">
            <div class="panel-heading">
                <h2><i class="fa-solid fa-chart-simple text-primary"></i> Dosen per Program Studi</h2>
            </div>
            <div style="padding: 20px; position: relative; height: 260px;">
                <canvas id="chartProdi"></canvas>
            </div>
        </div>
    </div>

    <!-- Dosen Terbaru -->
    <div class="dashboard-panel">
        <div class="panel-heading">
            <h2><i class="fa-solid fa-clock-rotate-left text-primary"></i> Dosen Terakhir Ditambahkan</h2>
            <a href="dosen.php" class="btn btn-sm btn-outline">Lihat Semua Dosen <i class="fa-solid fa-arrow-right"></i></a>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Nama Dosen</th>
                        <th>Jabatan Fungsional</th>
                        <th>Program Studi</th>
                        <th>Status</th>
                        <th>Ditambahkan</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($dosenTerbaru)): ?>
                    <tr>
                        <td colspan="5" class="empty-state">
                            <div class="icon"><i class="fa-regular fa-folder-open"></i></div>
                            Belum ada data dosen tersimpan.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($dosenTerbaru as $d): ?>
                    <tr>
                        <td>
                            <a href="dosen_detail.php?id=<?= $d['id_dosen'] ?>" style="color: var(--primary-blue); font-weight: 700; text-decoration: none;">
                                <?= htmlspecialchars($d['nama']) ?> <i class="fa-solid fa-arrow-up-right-from-square" style="font-size: 0.75rem;"></i>
                            </a>
                        </td>
                        <td><?= htmlspecialchars($d['nama_jabatan'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($d['program_studi'] ?? '-') ?></td>
                        <td>
                            <?php
                                $st = $d['status_aktivitas'];
                                $cls = $st === 'Aktif' ? 'badge-success' : ($st === 'Cuti' ? 'badge-warning' : 'badge-danger');
                            ?>
                            <span class="badge <?= $cls ?>"><?= htmlspecialchars($st) ?></span>
                        </td>
                        <td class="text-muted"><?= date('d M Y', strtotime($d['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Informasi Sistem -->
    <div class="dashboard-panel" style="margin-top: 20px;">
        <div class="panel-heading">
            <h2><i class="fa-solid fa-circle-info text-primary"></i> Informasi Sistem & Status Akun</h2>
        </div>
        <div class="info-grid">
            <div>
                <span>Institusi</span>
                <strong>Universitas Muhammadiyah Bengkulu</strong>
            </div>
            <div>
                <span>Hak Akses (Peran)</span>
                <strong><?= htmlspecialchars($role) ?></strong>
            </div>
            <div>
                <span>Username Pengguna</span>
                <strong><?= htmlspecialchars($_SESSION['username'] ?? '') ?></strong>
            </div>
            <div>
                <span>Status Akun</span>
                <strong class="status-active"><i class="fa-solid fa-circle-check"></i> Aktif Terotentikasi</strong>
            </div>
        </div>
    </div>
</main>

<script>
// Chart Jabatan Fungsional
const ctxJabatan = document.getElementById('chartJabatan');
if (ctxJabatan) {
    new Chart(ctxJabatan, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($chartJabatanLabels) ?>,
            datasets: [{
                data: <?= json_encode($chartJabatanValues) ?>,
                backgroundColor: [
                    '#0d9488', '#2dd4bf', '#059669', '#f59e0b', '#8b5cf6', '#ec4899'
                ],
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { boxWidth: 12, font: { family: 'Plus Jakarta Sans', size: 11 } }
                }
            }
        }
    });
}

// Chart Dosen per Prodi
const ctxProdi = document.getElementById('chartProdi');
if (ctxProdi) {
    new Chart(ctxProdi, {
        type: 'bar',
        data: {
            labels: <?= json_encode($chartProdiLabels) ?>,
            datasets: [{
                label: 'Jumlah Dosen',
                data: <?= json_encode($chartProdiValues) ?>,
                backgroundColor: '#0d9488',
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { precision: 0 }
                },
                x: {
                    ticks: {
                        font: { family: 'Plus Jakarta Sans', size: 10 },
                        maxRotation: 25,
                        minRotation: 0
                    }
                }
            }
        }
    });
}
</script>

</body>
</html>

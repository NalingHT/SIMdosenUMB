<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/koneksi.php';
require_once __DIR__ . '/includes/helpers.php';

require_login();
if (($_SESSION['role'] ?? '') !== 'ADMIN') {
    redirect('dashboard.php');
}

$error = '';
$sukses = $_SESSION['flash_success'] ?? '';
unset($_SESSION['flash_success']);

$defaultBaseUrl = 'http://localhost/SImdosen/simmahasiswa';
$baseUrl = $_POST['base_url'] ?? $defaultBaseUrl;

$apiFakultasUrl = rtrim($baseUrl, '/') . '/api/fakultas.php';
$apiProdiUrl    = rtrim($baseUrl, '/') . '/api/program_studi.php';

$fetchedFakultas = [];
$fetchedProdi    = [];
$statusKoneksi   = null;

// Fungsi helper HTTP Fetch dengan cURL / file_get_contents
function fetchApi($url) {
    $ctx = stream_context_create([
        'http' => [
            'timeout' => 3,
            'header'  => "Accept: application/json\r\n"
        ]
    ]);
    $raw = @file_get_contents($url, false, $ctx);
    if ($raw === false) return null;
    return json_decode($raw, true);
}

// ─── Aksi: Tes Koneksi atau Sinkronisasi ──────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        $error = 'Token keamanan tidak valid.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'test' || $action === 'sync') {
            $dataFak = fetchApi($apiFakultasUrl);
            $dataPro = fetchApi($apiProdiUrl);

            if ($dataFak && ($dataFak['success'] ?? false)) {
                $fetchedFakultas = $dataFak['data'] ?? [];
            }
            if ($dataPro && ($dataPro['success'] ?? false)) {
                $fetchedProdi = $dataPro['data'] ?? [];
            }

            if ($dataFak !== null || $dataPro !== null) {
                $statusKoneksi = true;
            } else {
                $statusKoneksi = false;
                $error = "Gagal terhubung ke endpoint API SIM Mahasiswa pada $baseUrl. Pastikan Apache aktif.";
            }

            // Jika aksi adalah SINKRONISASI
            if ($action === 'sync' && $statusKoneksi && !empty($fetchedProdi)) {
                $inserted = 0;
                $skipped  = 0;

                foreach ($fetchedProdi as $p) {
                    $univ     = $p['nama_universitas'] ?? 'Universitas Muhammadiyah Bengkulu';
                    $fakultas = $p['nama_fakultas'] ?? 'Fakultas Teknik';
                    $prodi    = $p['nama_program_studi'] ?? '';

                    if ($prodi) {
                        $stmt = $conn->prepare("INSERT INTO penempatan (universitas, fakultas, program_studi, aktif) 
                                                VALUES (?, ?, ?, 1) 
                                                ON DUPLICATE KEY UPDATE aktif=1");
                        $stmt->bind_param('sss', $univ, $fakultas, $prodi);
                        if ($stmt->execute()) {
                            if ($stmt->affected_rows === 1) $inserted++;
                            else $skipped++;
                        }
                        $stmt->close();
                    }
                }

                $_SESSION['flash_success'] = "Sinkronisasi berhasil! $inserted program studi baru ditambahkan, $skipped sudah tersinkronisasi.";
                redirect('sinkronisasi.php');
            }
        }
    }
} else {
    // Default: cek koneksi secara otomatis
    $dataFak = fetchApi($apiFakultasUrl);
    $dataPro = fetchApi($apiProdiUrl);
    if ($dataFak && ($dataFak['success'] ?? false)) $fetchedFakultas = $dataFak['data'] ?? [];
    if ($dataPro && ($dataPro['success'] ?? false)) $fetchedProdi = $dataPro['data'] ?? [];
    $statusKoneksi = ($dataFak !== null);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Integrasi & Sinkronisasi API SIM Mahasiswa — SIM Dosen UMB</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .integration-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 700;
        }
        .flow-card {
            background: #fff;
            border-radius: 12px;
            border: 1px solid var(--border);
            padding: 20px;
            box-shadow: var(--shadow-sm);
        }
    </style>
</head>
<body class="app-body">

<?php include 'topbar.php'; ?>
<?php include 'sidebar.php'; ?>

<main class="main-content animate-in">
    <div class="page-heading-flex">
        <div class="page-heading">
            <span class="eyebrow">WEB SERVICE & KONSUMSI API</span>
            <h1>Integrasi API SIM Mahasiswa</h1>
            <p>Konsumsi layanan web eksternal untuk sinkronisasi fakultas dan program studi secara otomatis.</p>
        </div>
        <div style="display:flex; gap:10px;">
            <a href="penempatan.php" class="btn btn-outline">🏛 Lihat Penempatan Dosen</a>
        </div>
    </div>

    <?php if ($sukses): ?>
        <div class="alert alert-success">✅ <?= htmlspecialchars($sukses) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Status Koneksi Box -->
    <div class="dashboard-panel" style="margin-bottom: 24px;">
        <div style="padding: 24px;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 20px;">
                <div>
                    <h3 style="margin: 0 0 4px 0; color: var(--navy); font-size: 1.15rem;">Status Koneksi Web Service (SIM Mahasiswa)</h3>
                    <p style="margin: 0; font-size: 0.88rem; color: var(--gray-600);">Endpoint: <code><?= htmlspecialchars($baseUrl) ?></code></p>
                </div>
                <div>
                    <?php if ($statusKoneksi): ?>
                        <span class="integration-badge badge-success">● TERHUBUNG KE API</span>
                    <?php else: ?>
                        <span class="integration-badge badge-danger">● GAGAL TERHUBUNG</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Form Pengaturan URL -->
            <form method="post" action="sinkronisasi.php" style="display: flex; gap: 10px; flex-wrap: wrap; align-items: flex-end;">
                <?= csrf_field() ?>
                <div style="flex: 1; min-width: 280px;">
                    <label style="font-size: 0.85rem; font-weight: 600; color: var(--gray-700); margin-bottom: 4px; display: block;">Base URL SIM Mahasiswa:</label>
                    <input type="url" name="base_url" class="form-control" value="<?= htmlspecialchars($baseUrl) ?>" required>
                </div>
                <button type="submit" name="action" value="test" class="btn btn-outline">🔄 Cek Koneksi</button>
                <button type="submit" name="action" value="sync" class="btn btn-primary" <?= !$statusKoneksi ? 'disabled' : '' ?>>⚡ Sinkronisasikan Sekarang</button>
            </form>
        </div>
    </div>

    <!-- Data yang Diterima dari API -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; align-items: start;">
        <!-- Fakultas yang Diterima -->
        <div class="dashboard-panel">
            <div class="panel-heading">
                <h2>🏛 Fakultas Diterima dari API (<?= count($fetchedFakultas) ?>)</h2>
                <code style="font-size: 0.8rem;"><?= htmlspecialchars(basename($apiFakultasUrl)) ?></code>
            </div>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Nama Fakultas</th>
                            <th>Universitas</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($fetchedFakultas)): ?>
                        <tr><td colspan="3" class="empty-state">Belum ada respon dari API fakultas.</td></tr>
                    <?php else: ?>
                        <?php foreach ($fetchedFakultas as $f): ?>
                        <tr>
                            <td><span class="badge badge-info"><?= htmlspecialchars($f['kode_fakultas'] ?? '-') ?></span></td>
                            <td><strong><?= htmlspecialchars($f['nama_fakultas'] ?? '-') ?></strong></td>
                            <td class="text-muted" style="font-size: 0.85rem;"><?= htmlspecialchars($f['nama_universitas'] ?? '-') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Program Studi yang Diterima -->
        <div class="dashboard-panel">
            <div class="panel-heading">
                <h2>🎓 Program Studi Diterima dari API (<?= count($fetchedProdi) ?>)</h2>
                <code style="font-size: 0.8rem;"><?= htmlspecialchars(basename($apiProdiUrl)) ?></code>
            </div>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Program Studi</th>
                            <th>Jenjang</th>
                            <th>Fakultas</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($fetchedProdi)): ?>
                        <tr><td colspan="4" class="empty-state">Belum ada respon dari API prodi.</td></tr>
                    <?php else: ?>
                        <?php foreach ($fetchedProdi as $p): ?>
                        <tr>
                            <td><span class="badge badge-info"><?= htmlspecialchars($p['kode_program_studi'] ?? '-') ?></span></td>
                            <td><strong><?= htmlspecialchars($p['nama_program_studi'] ?? '-') ?></strong></td>
                            <td><?= htmlspecialchars($p['jenjang'] ?? '-') ?></td>
                            <td class="text-muted" style="font-size: 0.85rem;"><?= htmlspecialchars($p['nama_fakultas'] ?? '-') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

</body>
</html>

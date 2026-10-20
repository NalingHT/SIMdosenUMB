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

// Tambah Kepangkatan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'tambah') {
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        $error = 'Token keamanan tidak valid.';
    } else {
        $pangkat = trim($_POST['nama_pangkat'] ?? '');
        $gol = trim($_POST['golongan'] ?? '');
        $ruang = trim($_POST['ruang'] ?? '');

        if (!$pangkat || !$gol || !$ruang) {
            $error = 'Semua field wajib diisi.';
        } else {
            $stmt = $conn->prepare("INSERT INTO kepangkatan (nama_pangkat, golongan, ruang) VALUES (?, ?, ?)");
            $stmt->bind_param('sss', $pangkat, $gol, $ruang);
            if ($stmt->execute()) {
                $_SESSION['flash_success'] = 'Kepangkatan berhasil ditambahkan.';
                redirect('ref_kepangkatan.php');
            } else {
                $error = 'Gagal menyimpan. Kombinasi pangkat, golongan, dan ruang mungkin sudah ada.';
            }
            $stmt->close();
        }
    }
}

// Hapus Kepangkatan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'hapus') {
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        $error = 'Token keamanan tidak valid.';
    } else {
        $id = (int)($_POST['id'] ?? 0);
        $chk = $conn->prepare("SELECT COUNT(*) as c FROM dosen WHERE id_kepangkatan = ?");
        $chk->bind_param('i', $id);
        $chk->execute();
        $hasDosen = (int)$chk->get_result()->fetch_assoc()['c'];
        $chk->close();

        if ($hasDosen > 0) {
            $error = "Tidak dapat menghapus pangkat ini karena masih digunakan oleh $hasDosen dosen.";
        } else {
            $stmt = $conn->prepare("DELETE FROM kepangkatan WHERE id_kepangkatan = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();
            $_SESSION['flash_success'] = 'Kepangkatan berhasil dihapus.';
            redirect('ref_kepangkatan.php');
        }
    }
}

// Ambil data kepangkatan dan jumlah dosen
$sql = "SELECT k.*, COUNT(d.id_dosen) AS jumlah_dosen 
        FROM kepangkatan k
        LEFT JOIN dosen d ON k.id_kepangkatan = d.id_kepangkatan
        GROUP BY k.id_kepangkatan
        ORDER BY k.golongan ASC, k.ruang ASC";
$res = $conn->query($sql);
$list = [];
if ($res) {
    while ($r = $res->fetch_assoc()) {
        $list[] = $r;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kepangkatan & Golongan — SIM Dosen UMB</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="app-body">

<?php include 'topbar.php'; ?>
<?php include 'sidebar.php'; ?>

<main class="main-content animate-in">
    <div class="page-heading-flex">
        <div class="page-heading">
            <span class="eyebrow">REFERENSI AKADEMIK</span>
            <h1>Kepangkatan & Golongan</h1>
            <p>Kelola jenjang kepangkatan, golongan, dan ruang pegawai/dosen.</p>
        </div>
    </div>

    <?php if ($sukses): ?>
        <div class="alert alert-success">✅ <?= htmlspecialchars($sukses) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 1fr 340px; gap: 24px; align-items: start;">
        <!-- List Kepangkatan -->
        <div class="dashboard-panel">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Pangkat</th>
                            <th>Golongan / Ruang</th>
                            <th>Jumlah Dosen</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($list)): ?>
                        <tr><td colspan="5" class="empty-state">Belum ada data kepangkatan.</td></tr>
                    <?php else: ?>
                        <?php foreach ($list as $i => $item): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><strong><?= htmlspecialchars($item['nama_pangkat']) ?></strong></td>
                            <td>
                                <span class="badge badge-info"><?= htmlspecialchars($item['golongan']) ?>/<?= htmlspecialchars($item['ruang']) ?></span>
                            </td>
                            <td>
                                <span class="badge" style="background:#f1f5f9; color:#334155;"><?= $item['jumlah_dosen'] ?> Dosen</span>
                            </td>
                            <td>
                                <form method="post" action="ref_kepangkatan.php" onsubmit="return confirm('Yakin hapus kepangkatan ini?');" style="display:inline;">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="hapus">
                                    <input type="hidden" name="id" value="<?= $item['id_kepangkatan'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger" <?= $item['jumlah_dosen'] > 0 ? 'disabled title="Masih digunakan dosen"' : '' ?>>🗑</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Form Tambah -->
        <div class="dashboard-panel">
            <div style="padding: 24px;">
                <h3 style="margin-top:0; font-size: 1.15rem; color: var(--navy);">➕ Tambah Pangkat</h3>
                <p style="font-size: 0.88rem; color: var(--gray-600); margin-bottom: 20px;">Daftarkan kepangkatan dan ruang baru.</p>

                <form method="post" action="ref_kepangkatan.php">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="tambah">

                    <div class="form-group">
                        <label>Nama Pangkat <span class="required">*</span></label>
                        <input type="text" name="nama_pangkat" class="form-control" placeholder="Contoh: Penata Muda" required>
                    </div>

                    <div class="form-group">
                        <label>Golongan <span class="required">*</span></label>
                        <input type="text" name="golongan" class="form-control" placeholder="Contoh: III" required>
                    </div>

                    <div class="form-group">
                        <label>Ruang <span class="required">*</span></label>
                        <input type="text" name="ruang" class="form-control" placeholder="Contoh: a" required>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%;">Simpan Pangkat</button>
                </form>
            </div>
        </div>
    </div>
</main>

</body>
</html>

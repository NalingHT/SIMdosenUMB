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

// Tambah Pendidikan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'tambah') {
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        $error = 'Token keamanan tidak valid.';
    } else {
        $jenjang = trim($_POST['jenjang'] ?? '');
        $urutan = (int)($_POST['urutan'] ?? 0);
        if (!$jenjang) {
            $error = 'Nama jenjang pendidikan wajib diisi.';
        } else {
            $stmt = $conn->prepare("INSERT INTO pendidikan (jenjang, urutan) VALUES (?, ?)");
            $stmt->bind_param('si', $jenjang, $urutan);
            if ($stmt->execute()) {
                $_SESSION['flash_success'] = 'Jenjang pendidikan berhasil ditambahkan.';
                redirect('ref_pendidikan.php');
            } else {
                $error = 'Gagal menyimpan. Jenjang pendidikan mungkin sudah ada.';
            }
            $stmt->close();
        }
    }
}

// Hapus Pendidikan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'hapus') {
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        $error = 'Token keamanan tidak valid.';
    } else {
        $id = (int)($_POST['id'] ?? 0);
        $chk = $conn->prepare("SELECT COUNT(*) as c FROM dosen WHERE id_pendidikan = ?");
        $chk->bind_param('i', $id);
        $chk->execute();
        $hasDosen = (int)$chk->get_result()->fetch_assoc()['c'];
        $chk->close();

        if ($hasDosen > 0) {
            $error = "Tidak dapat menghapus jenjang ini karena masih digunakan oleh $hasDosen dosen.";
        } else {
            $stmt = $conn->prepare("DELETE FROM pendidikan WHERE id_pendidikan = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();
            $_SESSION['flash_success'] = 'Jenjang pendidikan berhasil dihapus.';
            redirect('ref_pendidikan.php');
        }
    }
}

// Ambil data pendidikan dan jumlah dosen
$sql = "SELECT p.*, COUNT(d.id_dosen) AS jumlah_dosen 
        FROM pendidikan p
        LEFT JOIN dosen d ON p.id_pendidikan = d.id_pendidikan
        GROUP BY p.id_pendidikan
        ORDER BY p.urutan ASC, p.id_pendidikan ASC";
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
    <title>Jenjang Pendidikan — SIM Dosen UMB</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="app-body">

<?php include 'topbar.php'; ?>
<?php include 'sidebar.php'; ?>

<main class="main-content animate-in">
    <div class="page-heading-flex">
        <div class="page-heading">
            <span class="eyebrow">REFERENSI AKADEMIK</span>
            <h1>Jenjang Pendidikan Terakhir</h1>
            <p>Kelola jenjang pendidikan kualifikasi akademik dosen (S1, S2, S3, Spesialis, Profesi).</p>
        </div>
    </div>

    <?php if ($sukses): ?>
        <div class="alert alert-success">✅ <?= htmlspecialchars($sukses) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 1fr 340px; gap: 24px; align-items: start;">
        <!-- List Pendidikan -->
        <div class="dashboard-panel">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Urutan</th>
                            <th>Jenjang Pendidikan</th>
                            <th>Jumlah Dosen</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($list)): ?>
                        <tr><td colspan="4" class="empty-state">Belum ada data jenjang pendidikan.</td></tr>
                    <?php else: ?>
                        <?php foreach ($list as $item): ?>
                        <tr>
                            <td><span class="badge" style="background:#f1f5f9; color:#475569; font-weight:700;">Tingkat <?= $item['urutan'] ?></span></td>
                            <td><strong><?= htmlspecialchars($item['jenjang']) ?></strong></td>
                            <td>
                                <span class="badge badge-info"><?= $item['jumlah_dosen'] ?> Dosen</span>
                            </td>
                            <td>
                                <form method="post" action="ref_pendidikan.php" onsubmit="return confirm('Yakin hapus jenjang ini?');" style="display:inline;">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="hapus">
                                    <input type="hidden" name="id" value="<?= $item['id_pendidikan'] ?>">
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
                <h3 style="margin-top:0; font-size: 1.15rem; color: var(--navy);">➕ Tambah Jenjang</h3>
                <p style="font-size: 0.88rem; color: var(--gray-600); margin-bottom: 20px;">Daftarkan jenjang pendidikan baru.</p>

                <form method="post" action="ref_pendidikan.php">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="tambah">

                    <div class="form-group">
                        <label>Nama Jenjang <span class="required">*</span></label>
                        <input type="text" name="jenjang" class="form-control" placeholder="Contoh: S3 / Doktor" required>
                    </div>

                    <div class="form-group">
                        <label>Urutan Tingkat <span class="required">*</span></label>
                        <input type="number" name="urutan" class="form-control" value="1" min="1" max="10" required>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%;">Simpan Jenjang</button>
                </form>
            </div>
        </div>
    </div>
</main>

</body>
</html>

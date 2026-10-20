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

// Tambah Jabatan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'tambah') {
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        $error = 'Token keamanan tidak valid.';
    } else {
        $nama = trim($_POST['nama_jabatan'] ?? '');
        if (!$nama) {
            $error = 'Nama jabatan wajib diisi.';
        } else {
            $stmt = $conn->prepare("INSERT INTO jabatan_fungsional (nama_jabatan, aktif) VALUES (?, 1)");
            $stmt->bind_param('s', $nama);
            if ($stmt->execute()) {
                $_SESSION['flash_success'] = 'Jabatan fungsional berhasil ditambahkan.';
                redirect('ref_jabatan.php');
            } else {
                $error = 'Gagal menyimpan. Nama jabatan mungkin sudah ada.';
            }
            $stmt->close();
        }
    }
}

// Toggle Aktif
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle') {
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        $error = 'Token keamanan tidak valid.';
    } else {
        $id = (int)($_POST['id'] ?? 0);
        $aktif = (int)($_POST['aktif'] ?? 0);
        $stmt = $conn->prepare("UPDATE jabatan_fungsional SET aktif = ? WHERE id_jabatan = ?");
        $stmt->bind_param('ii', $aktif, $id);
        $stmt->execute();
        $stmt->close();
        $_SESSION['flash_success'] = 'Status jabatan berhasil diperbarui.';
        redirect('ref_jabatan.php');
    }
}

// Hapus Jabatan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'hapus') {
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        $error = 'Token keamanan tidak valid.';
    } else {
        $id = (int)($_POST['id'] ?? 0);
        $chk = $conn->prepare("SELECT COUNT(*) as c FROM dosen WHERE id_jabatan = ?");
        $chk->bind_param('i', $id);
        $chk->execute();
        $hasDosen = (int)$chk->get_result()->fetch_assoc()['c'];
        $chk->close();

        if ($hasDosen > 0) {
            $error = "Tidak dapat menghapus jabatan ini karena masih digunakan oleh $hasDosen dosen.";
        } else {
            $stmt = $conn->prepare("DELETE FROM jabatan_fungsional WHERE id_jabatan = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();
            $_SESSION['flash_success'] = 'Jabatan fungsional berhasil dihapus.';
            redirect('ref_jabatan.php');
        }
    }
}

// Ambil daftar jabatan dan jumlah dosen
$sql = "SELECT j.*, COUNT(d.id_dosen) AS jumlah_dosen 
        FROM jabatan_fungsional j
        LEFT JOIN dosen d ON j.id_jabatan = d.id_jabatan
        GROUP BY j.id_jabatan
        ORDER BY j.id_jabatan ASC";
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
    <title>Jabatan Fungsional — SIM Dosen UMB</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="app-body">

<?php include 'topbar.php'; ?>
<?php include 'sidebar.php'; ?>

<main class="main-content animate-in">
    <div class="page-heading-flex">
        <div class="page-heading">
            <span class="eyebrow">REFERENSI AKADEMIK</span>
            <h1>Jabatan Fungsional Dosen</h1>
            <p>Kelola jenjang karir fungsional dosen (Asisten Ahli, Lektor, Lektor Kepala, Guru Besar).</p>
        </div>
    </div>

    <?php if ($sukses): ?>
        <div class="alert alert-success">✅ <?= htmlspecialchars($sukses) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 1fr 340px; gap: 24px; align-items: start;">
        <!-- List Jabatan -->
        <div class="dashboard-panel">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nama Jabatan Fungsional</th>
                            <th>Jumlah Dosen</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($list)): ?>
                        <tr><td colspan="5" class="empty-state">Belum ada data jabatan fungsional.</td></tr>
                    <?php else: ?>
                        <?php foreach ($list as $item): ?>
                        <tr>
                            <td>#<?= $item['id_jabatan'] ?></td>
                            <td><strong><?= htmlspecialchars($item['nama_jabatan']) ?></strong></td>
                            <td>
                                <span class="badge badge-info"><?= $item['jumlah_dosen'] ?> Dosen</span>
                            </td>
                            <td>
                                <form method="post" action="ref_jabatan.php" style="display:inline;">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="id" value="<?= $item['id_jabatan'] ?>">
                                    <input type="hidden" name="aktif" value="<?= $item['aktif'] ? 0 : 1 ?>">
                                    <button type="submit" class="badge <?= $item['aktif'] ? 'badge-success' : 'badge-danger' ?>" style="cursor:pointer; border:none;">
                                        <?= $item['aktif'] ? 'Aktif' : 'Nonaktif' ?>
                                    </button>
                                </form>
                            </td>
                            <td>
                                <form method="post" action="ref_jabatan.php" onsubmit="return confirm('Yakin hapus jabatan ini?');" style="display:inline;">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="hapus">
                                    <input type="hidden" name="id" value="<?= $item['id_jabatan'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger" <?= $item['jumlah_dosen'] > 0 ? 'disabled title="Masih ada dosen dengan jabatan ini"' : '' ?>>🗑</button>
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
                <h3 style="margin-top:0; font-size: 1.15rem; color: var(--navy);">➕ Tambah Jabatan</h3>
                <p style="font-size: 0.88rem; color: var(--gray-600); margin-bottom: 20px;">Daftarkan nama jabatan fungsional baru.</p>

                <form method="post" action="ref_jabatan.php">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="tambah">

                    <div class="form-group">
                        <label>Nama Jabatan <span class="required">*</span></label>
                        <input type="text" name="nama_jabatan" class="form-control" placeholder="Contoh: Lektor Kepala" required>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%;">Simpan Jabatan</button>
                </form>
            </div>
        </div>
    </div>
</main>

</body>
</html>

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

// Tambah Penempatan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'tambah') {
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        $error = 'Token keamanan tidak valid.';
    } else {
        $univ = trim($_POST['universitas'] ?? '');
        $fak = trim($_POST['fakultas'] ?? '');
        $prodi = trim($_POST['program_studi'] ?? '');

        if (!$univ || !$fak || !$prodi) {
            $error = 'Semua field wajib diisi.';
        } else {
            $stmt = $conn->prepare("INSERT INTO penempatan (universitas, fakultas, program_studi, aktif) VALUES (?, ?, ?, 1)");
            $stmt->bind_param('sss', $univ, $fak, $prodi);
            if ($stmt->execute()) {
                $_SESSION['flash_success'] = 'Penempatan akademik berhasil ditambahkan.';
                redirect('penempatan.php');
            } else {
                $error = 'Gagal menyimpan penempatan. Kombinasi tersebut mungkin sudah ada.';
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
        $stmt = $conn->prepare("UPDATE penempatan SET aktif = ? WHERE id_penempatan = ?");
        $stmt->bind_param('ii', $aktif, $id);
        $stmt->execute();
        $stmt->close();
        $_SESSION['flash_success'] = 'Status penempatan berhasil diperbarui.';
        redirect('penempatan.php');
    }
}

// Hapus Penempatan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'hapus') {
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        $error = 'Token keamanan tidak valid.';
    } else {
        $id = (int)($_POST['id'] ?? 0);
        // Cek apakah ada dosen yang menggunakan penempatan ini
        $chk = $conn->prepare("SELECT COUNT(*) as c FROM dosen WHERE id_penempatan = ?");
        $chk->bind_param('i', $id);
        $chk->execute();
        $hasDosen = (int)$chk->get_result()->fetch_assoc()['c'];
        $chk->close();

        if ($hasDosen > 0) {
            $error = "Tidak dapat menghapus penempatan ini karena masih digunakan oleh $hasDosen dosen.";
        } else {
            $stmt = $conn->prepare("DELETE FROM penempatan WHERE id_penempatan = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();
            $_SESSION['flash_success'] = 'Data penempatan berhasil dihapus.';
            redirect('penempatan.php');
        }
    }
}

// Ambil daftar penempatan dan jumlah dosen
$sql = "SELECT p.*, COUNT(d.id_dosen) AS jumlah_dosen 
        FROM penempatan p
        LEFT JOIN dosen d ON p.id_penempatan = d.id_penempatan
        GROUP BY p.id_penempatan
        ORDER BY p.fakultas ASC, p.program_studi ASC";
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
    <title>Penempatan Akademik — SIM Dosen UMB</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="app-body">

<?php include 'topbar.php'; ?>
<?php include 'sidebar.php'; ?>

<main class="main-content animate-in">
    <div class="page-heading-flex">
        <div class="page-heading">
            <span class="eyebrow">STRUKTUR AKADEMIK</span>
            <h1>Penempatan Dosen</h1>
            <p>Kelola fakultas dan program studi tempat dosen mengabdi.</p>
        </div>
    </div>

    <?php if ($sukses): ?>
        <div class="alert alert-success">✅ <?= htmlspecialchars($sukses) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 1fr 340px; gap: 24px; align-items: start;">
        <!-- List Penempatan -->
        <div class="dashboard-panel">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Fakultas</th>
                            <th>Program Studi</th>
                            <th>Total Dosen</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($list)): ?>
                        <tr><td colspan="6" class="empty-state">Belum ada data penempatan.</td></tr>
                    <?php else: ?>
                        <?php foreach ($list as $i => $item): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><strong><?= htmlspecialchars($item['fakultas']) ?></strong></td>
                            <td><?= htmlspecialchars($item['program_studi']) ?></td>
                            <td>
                                <span class="badge badge-info"><?= $item['jumlah_dosen'] ?> Dosen</span>
                            </td>
                            <td>
                                <form method="post" action="penempatan.php" style="display:inline;">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="id" value="<?= $item['id_penempatan'] ?>">
                                    <input type="hidden" name="aktif" value="<?= $item['aktif'] ? 0 : 1 ?>">
                                    <button type="submit" class="badge <?= $item['aktif'] ? 'badge-success' : 'badge-danger' ?>" style="cursor:pointer; border:none;">
                                        <?= $item['aktif'] ? 'Aktif' : 'Nonaktif' ?>
                                    </button>
                                </form>
                            </td>
                            <td>
                                <form method="post" action="penempatan.php" onsubmit="return confirm('Yakin hapus data penempatan ini?');" style="display:inline;">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="hapus">
                                    <input type="hidden" name="id" value="<?= $item['id_penempatan'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger" <?= $item['jumlah_dosen'] > 0 ? 'disabled title="Masih ada dosen terdaftar"' : '' ?>>🗑</button>
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
                <h3 style="margin-top:0; font-size: 1.15rem; color: var(--navy);">➕ Tambah Penempatan</h3>
                <p style="font-size: 0.88rem; color: var(--gray-600); margin-bottom: 20px;">Daftarkan program studi dan fakultas baru di kampus.</p>

                <form method="post" action="penempatan.php">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="tambah">

                    <div class="form-group">
                        <label>Perguruan Tinggi <span class="required">*</span></label>
                        <input type="text" name="universitas" class="form-control" value="Universitas Muhammadiyah Bengkulu" required>
                    </div>

                    <div class="form-group">
                        <label>Fakultas <span class="required">*</span></label>
                        <input type="text" name="fakultas" class="form-control" placeholder="Contoh: Teknik" required>
                    </div>

                    <div class="form-group">
                        <label>Program Studi <span class="required">*</span></label>
                        <input type="text" name="program_studi" class="form-control" placeholder="Contoh: Sistem Informasi" required>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%;">Simpan Penempatan</button>
                </form>
            </div>
        </div>
    </div>
</main>

</body>
</html>

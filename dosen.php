<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/koneksi.php';

require_login();

// ─── Pencarian & Pagination ─────────────────────
$search = trim($_GET['q'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 10;
$offset = ($page - 1) * $limit;

$where  = '';
$params = [];
$types  = '';

if ($search !== '') {
    $where  = "WHERE d.nama LIKE ? OR d.nip LIKE ? OR d.nomor_registrasi LIKE ?";
    $like   = "%$search%";
    $params = [$like, $like, $like];
    $types  = 'sss';
}

// Total
$sqlCount = "SELECT COUNT(*) AS total FROM dosen d $where";
$stmt = $conn->prepare($sqlCount);
if ($types) $stmt->bind_param($types, ...$params);
$stmt->execute();
$total = (int)$stmt->get_result()->fetch_assoc()['total'];
$stmt->close();
$totalPages = max(1, ceil($total / $limit));

// Data
$sql = "SELECT d.*, jf.nama_jabatan, p.program_studi, p.fakultas,
               pend.jenjang, sk.status_pegawai
        FROM dosen d
        LEFT JOIN jabatan_fungsional jf ON d.id_jabatan = jf.id_jabatan
        LEFT JOIN penempatan p ON d.id_penempatan = p.id_penempatan
        LEFT JOIN pendidikan pend ON d.id_pendidikan = pend.id_pendidikan
        LEFT JOIN status_kepegawaian sk ON d.id_status_kepegawaian = sk.id_status_kepegawaian
        $where
        ORDER BY d.nama ASC
        LIMIT $limit OFFSET $offset";
$stmt = $conn->prepare($sql);
if ($types) $stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$dosenList = [];
while ($row = $result->fetch_assoc()) {
    $dosenList[] = $row;
}
$stmt->close();

// Flash message
$sukses = $_SESSION['flash_success'] ?? '';
unset($_SESSION['flash_success']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Dosen — SIM Dosen UMB</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="app-body">

<?php include 'topbar.php'; ?>
<?php include 'sidebar.php'; ?>

<main class="main-content animate-in">
    <div class="page-heading-flex">
        <div class="page-heading">
            <span class="eyebrow"><i class="fa-solid fa-graduation-cap"></i> DATA AKADEMIK</span>
            <h1>Data Pokok Dosen</h1>
            <p>Total <?= number_format($total, 0, ',', '.') ?> dosen terdaftar dalam sistem</p>
        </div>
        <div style="display:flex; gap:10px; flex-wrap: wrap;">
            <a href="laporan_dosen.php" class="btn btn-outline btn-lg">
                <i class="fa-solid fa-file-invoice"></i> Rekap & Laporan
            </a>
            <a href="dosen_tambah.php" class="btn btn-primary btn-lg">
                <i class="fa-solid fa-user-plus"></i> Tambah Dosen
            </a>
        </div>
    </div>

    <?php if ($sukses): ?>
        <div class="alert alert-success">
            <i class="fa-solid fa-circle-check"></i>
            <span><?= htmlspecialchars($sukses) ?></span>
        </div>
    <?php endif; ?>

    <!-- Search Form -->
    <div class="dashboard-panel" style="margin-bottom: 20px;">
        <div style="padding: 16px 22px;">
            <form method="get" style="display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
                <div style="position: relative; flex: 1; min-width: 240px;">
                    <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--text-light); font-size: 0.9rem;"></i>
                    <input type="text" name="q" value="<?= htmlspecialchars($search) ?>"
                           placeholder="Cari nama dosen, NIP, atau Nomor Registrasi..."
                           style="width: 100%; padding: 10px 14px 10px 38px; border: 1.5px solid var(--border); border-radius: 9px; font-size: 0.9rem; outline: none;">
                </div>
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="fa-solid fa-magnifying-glass"></i> Cari
                </button>
                <?php if ($search): ?>
                    <a href="dosen.php" class="btn btn-outline btn-sm">
                        <i class="fa-solid fa-xmark"></i> Reset
                    </a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Table -->
    <div class="dashboard-panel">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">No</th>
                        <th>Nama Dosen</th>
                        <th>NIP</th>
                        <th>Jabatan Fungsional</th>
                        <th>Pendidikan</th>
                        <th>Program Studi</th>
                        <th>Status</th>
                        <th style="text-align: center; width: 130px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($dosenList)): ?>
                    <tr>
                        <td colspan="8" class="empty-state">
                            <div class="icon"><i class="fa-regular fa-folder-open"></i></div>
                            <?= $search ? 'Tidak ditemukan dosen dengan kata kunci tersebut.' : 'Belum ada data dosen tersimpan.' ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($dosenList as $i => $d): ?>
                    <tr>
                        <td><?= $offset + $i + 1 ?></td>
                        <td>
                            <a href="dosen_detail.php?id=<?= $d['id_dosen'] ?>" style="color: var(--primary-blue); font-weight: 700;">
                                <?= htmlspecialchars($d['nama']) ?>
                            </a>
                        </td>
                        <td class="text-muted"><?= htmlspecialchars($d['nip'] ?: '-') ?></td>
                        <td><?= htmlspecialchars($d['nama_jabatan'] ?? '-') ?></td>
                        <td><span class="badge badge-info"><?= htmlspecialchars($d['jenjang'] ?? '-') ?></span></td>
                        <td><?= htmlspecialchars($d['program_studi'] ?? '-') ?></td>
                        <td>
                            <?php
                                $st = $d['status_aktivitas'];
                                $cls = match($st) {
                                    'Aktif' => 'badge-success',
                                    'Cuti' => 'badge-warning',
                                    default => 'badge-danger'
                                };
                            ?>
                            <span class="badge <?= $cls ?>"><?= htmlspecialchars($st) ?></span>
                        </td>
                        <td>
                            <div class="action-group" style="justify-content: center;">
                                <a href="dosen_detail.php?id=<?= $d['id_dosen'] ?>" class="btn btn-sm btn-outline" title="Lihat Profil">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                                <a href="dosen_edit.php?id=<?= $d['id_dosen'] ?>" class="btn btn-sm btn-outline" title="Edit Data">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>
                                <form method="post" action="dosen_hapus.php" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data dosen ini?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= $d['id_dosen'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger" title="Hapus Data">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="pagination">
        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
            <?php
                $qs = http_build_query(array_filter(['q' => $search, 'page' => $p]));
            ?>
            <?php if ($p === $page): ?>
                <span class="active"><?= $p ?></span>
            <?php else: ?>
                <a href="?<?= $qs ?>"><?= $p ?></a>
            <?php endif; ?>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</main>

</body>
</html>

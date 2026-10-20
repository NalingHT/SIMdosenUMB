<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/koneksi.php';
require_once __DIR__ . '/includes/helpers.php';

require_login();

$errors = [];

// ─── Referensi dropdown ──────────────────────────
$jabatanList     = $conn->query("SELECT * FROM jabatan_fungsional WHERE aktif=1 ORDER BY nama_jabatan")->fetch_all(MYSQLI_ASSOC);
$pendidikanList  = $conn->query("SELECT * FROM pendidikan ORDER BY urutan")->fetch_all(MYSQLI_ASSOC);
$kepangkatanList = $conn->query("SELECT * FROM kepangkatan ORDER BY nama_pangkat")->fetch_all(MYSQLI_ASSOC);
$rumpunList      = $conn->query("SELECT * FROM rumpun_ilmu ORDER BY nama_rumpun")->fetch_all(MYSQLI_ASSOC);
$statusList      = $conn->query("SELECT * FROM status_kepegawaian ORDER BY status_pegawai")->fetch_all(MYSQLI_ASSOC);
$penempatanList  = $conn->query("SELECT * FROM penempatan WHERE aktif=1 ORDER BY program_studi")->fetch_all(MYSQLI_ASSOC);

// Defaults
$data = [
    'nama' => '', 'nip' => '', 'nuptk' => '', 'nomor_registrasi' => '',
    'id_jabatan' => '', 'id_kepangkatan' => '', 'id_pendidikan' => '',
    'id_rumpun_ilmu' => '', 'id_status_kepegawaian' => '', 'id_penempatan' => '',
    'status_serdos' => 'Belum Diketahui', 'status_aktivitas' => 'Aktif', 'keterangan' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? null;
    if (!csrf_validate(is_string($token) ? $token : null)) {
        $errors[] = 'Token CSRF tidak valid. Muat ulang halaman.';
    }

    $data['nama']                  = trim($_POST['nama'] ?? '');
    $data['nip']                   = trim($_POST['nip'] ?? '') ?: null;
    $data['nuptk']                 = trim($_POST['nuptk'] ?? '') ?: null;
    $data['nomor_registrasi']      = trim($_POST['nomor_registrasi'] ?? '') ?: null;
    $data['id_jabatan']            = ($_POST['id_jabatan'] ?? '') ?: null;
    $data['id_kepangkatan']        = ($_POST['id_kepangkatan'] ?? '') ?: null;
    $data['id_pendidikan']         = ($_POST['id_pendidikan'] ?? '') ?: null;
    $data['id_rumpun_ilmu']        = ($_POST['id_rumpun_ilmu'] ?? '') ?: null;
    $data['id_status_kepegawaian'] = ($_POST['id_status_kepegawaian'] ?? '') ?: null;
    $data['id_penempatan']         = ($_POST['id_penempatan'] ?? '') ?: null;
    $data['status_serdos']         = $_POST['status_serdos'] ?? 'Belum Diketahui';
    $data['status_aktivitas']      = $_POST['status_aktivitas'] ?? 'Aktif';
    $data['keterangan']            = trim($_POST['keterangan'] ?? '') ?: null;

    if ($data['nama'] === '') $errors[] = 'Nama dosen wajib diisi.';

    if (empty($errors)) {
        $sql = "INSERT INTO dosen (nama, nip, nuptk, nomor_registrasi, id_jabatan, id_kepangkatan,
                id_pendidikan, id_rumpun_ilmu, id_status_kepegawaian, id_penempatan,
                status_serdos, status_aktivitas, keterangan)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssssiiiiiisss',
            $data['nama'], $data['nip'], $data['nuptk'], $data['nomor_registrasi'],
            $data['id_jabatan'], $data['id_kepangkatan'], $data['id_pendidikan'],
            $data['id_rumpun_ilmu'], $data['id_status_kepegawaian'], $data['id_penempatan'],
            $data['status_serdos'], $data['status_aktivitas'], $data['keterangan']
        );

        if ($stmt->execute()) {
            $_SESSION['flash_success'] = 'Data dosen berhasil ditambahkan.';
            $stmt->close();
            redirect('dosen.php');
        } else {
            $errors[] = 'Gagal menyimpan: ' . $stmt->error;
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Dosen — SIM Dosen UMB</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="app-body">

<?php include 'topbar.php'; ?>
<?php include 'sidebar.php'; ?>

<main class="main-content animate-in">
    <div class="page-heading">
        <span class="eyebrow">DATA AKADEMIK</span>
        <h1>Tambah Dosen Baru</h1>
        <p>Isi data dosen di bawah ini. Kolom bertanda * wajib diisi.</p>
    </div>

    <?php if ($errors): ?>
        <div class="alert alert-danger">
            <div>⚠️ <strong>Periksa kembali:</strong>
                <ul style="margin:6px 0 0; padding-left:20px;">
                    <?php foreach ($errors as $err): ?>
                        <li><?= htmlspecialchars($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    <?php endif; ?>

    <div class="form-panel">
        <form method="post" autocomplete="off">
            <?= csrf_field() ?>

            <h3 style="margin:0 0 20px; font-weight:750; color:var(--primary-dark);">📝 Identitas Dosen</h3>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:0 20px;">
                <div class="form-group">
                    <label>Nama Dosen *</label>
                    <input type="text" name="nama" value="<?= e($data['nama']) ?>" required maxlength="150">
                </div>
                <div class="form-group">
                    <label>NIP</label>
                    <input type="text" name="nip" value="<?= e($data['nip'] ?? '') ?>" maxlength="30">
                </div>
                <div class="form-group">
                    <label>NUPTK</label>
                    <input type="text" name="nuptk" value="<?= e($data['nuptk'] ?? '') ?>" maxlength="30">
                </div>
                <div class="form-group">
                    <label>Nomor Registrasi</label>
                    <input type="text" name="nomor_registrasi" value="<?= e($data['nomor_registrasi'] ?? '') ?>" maxlength="50">
                </div>
            </div>

            <h3 style="margin:28px 0 20px; font-weight:750; color:var(--primary-dark);">🎓 Data Akademik</h3>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:0 20px;">
                <div class="form-group">
                    <label>Jabatan Fungsional</label>
                    <select name="id_jabatan">
                        <option value="">— Pilih —</option>
                        <?php foreach ($jabatanList as $j): ?>
                            <option value="<?= $j['id_jabatan'] ?>" <?= $data['id_jabatan'] == $j['id_jabatan'] ? 'selected' : '' ?>><?= e($j['nama_jabatan']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Pendidikan Terakhir</label>
                    <select name="id_pendidikan">
                        <option value="">— Pilih —</option>
                        <?php foreach ($pendidikanList as $p): ?>
                            <option value="<?= $p['id_pendidikan'] ?>" <?= $data['id_pendidikan'] == $p['id_pendidikan'] ? 'selected' : '' ?>><?= e($p['jenjang']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Kepangkatan</label>
                    <select name="id_kepangkatan">
                        <option value="">— Pilih —</option>
                        <?php foreach ($kepangkatanList as $k): ?>
                            <option value="<?= $k['id_kepangkatan'] ?>" <?= $data['id_kepangkatan'] == $k['id_kepangkatan'] ? 'selected' : '' ?>>
                                <?= e($k['nama_pangkat']) ?> <?= $k['golongan'] ? '(' . e($k['golongan']) . '/' . e($k['ruang']) . ')' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Rumpun Ilmu</label>
                    <select name="id_rumpun_ilmu">
                        <option value="">— Pilih —</option>
                        <?php foreach ($rumpunList as $r): ?>
                            <option value="<?= $r['id_rumpun_ilmu'] ?>" <?= $data['id_rumpun_ilmu'] == $r['id_rumpun_ilmu'] ? 'selected' : '' ?>><?= e($r['nama_rumpun']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Status Kepegawaian</label>
                    <select name="id_status_kepegawaian">
                        <option value="">— Pilih —</option>
                        <?php foreach ($statusList as $s): ?>
                            <option value="<?= $s['id_status_kepegawaian'] ?>" <?= $data['id_status_kepegawaian'] == $s['id_status_kepegawaian'] ? 'selected' : '' ?>>
                                <?= e($s['status_pegawai']) ?> (<?= e($s['ikatan_kerja']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Penempatan (Prodi)</label>
                    <select name="id_penempatan">
                        <option value="">— Pilih —</option>
                        <?php foreach ($penempatanList as $pt): ?>
                            <option value="<?= $pt['id_penempatan'] ?>" <?= $data['id_penempatan'] == $pt['id_penempatan'] ? 'selected' : '' ?>>
                                <?= e($pt['program_studi']) ?> — <?= e($pt['fakultas']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <h3 style="margin:28px 0 20px; font-weight:750; color:var(--primary-dark);">📋 Status</h3>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:0 20px;">
                <div class="form-group">
                    <label>Status Serdos</label>
                    <select name="status_serdos">
                        <?php foreach (['Sudah','Belum','Tidak Berlaku','Belum Diketahui'] as $ss): ?>
                            <option value="<?= $ss ?>" <?= $data['status_serdos'] === $ss ? 'selected' : '' ?>><?= $ss ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Status Aktivitas</label>
                    <select name="status_aktivitas">
                        <?php foreach (['Aktif','Tidak Aktif','Cuti','Pensiun','Belum Diketahui'] as $sa): ?>
                            <option value="<?= $sa ?>" <?= $data['status_aktivitas'] === $sa ? 'selected' : '' ?>><?= $sa ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>Keterangan</label>
                <textarea name="keterangan" rows="3"><?= e($data['keterangan'] ?? '') ?></textarea>
            </div>

            <div class="form-actions">
                <a href="dosen.php" class="btn btn-outline">← Kembali</a>
                <button type="submit" class="btn btn-primary">💾 Simpan Dosen</button>
            </div>
        </form>
    </div>
</main>

</body>
</html>

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

// Tambah Pengguna Baru
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'tambah') {
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        $error = 'Token keamanan tidak valid.';
    } else {
        $nama     = trim($_POST['nama_tampilan'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = (string)($_POST['password'] ?? '');
        $idRole   = (int)($_POST['id_role'] ?? 1);

        if (!$nama || !$username || !$password) {
            $error = 'Nama, username, dan password wajib diisi.';
        } elseif (strlen($password) < 6) {
            $error = 'Password minimal 6 karakter.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO pengguna (id_role, username, password_hash, nama_tampilan, email, aktif) VALUES (?, ?, ?, ?, NULLIF(?, ''), 1)");
            $stmt->bind_param('issss', $idRole, $username, $hash, $nama, $email);
            if ($stmt->execute()) {
                $_SESSION['flash_success'] = 'Pengguna baru berhasil ditambahkan.';
                redirect('pengguna.php');
            } else {
                $error = 'Gagal menyimpan. Username atau email mungkin sudah digunakan: ' . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// Toggle Aktif/Nonaktif
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle') {
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        $error = 'Token keamanan tidak valid.';
    } else {
        $id = (int)($_POST['id'] ?? 0);
        $aktif = (int)($_POST['aktif'] ?? 0);

        // Jangan nonaktifkan akun sendiri
        if ($id === (int)($_SESSION['user_id'] ?? 0)) {
            $error = 'Anda tidak dapat menonaktifkan akun yang sedang digunakan.';
        } else {
            $stmt = $conn->prepare("UPDATE pengguna SET aktif = ? WHERE id_pengguna = ?");
            $stmt->bind_param('ii', $aktif, $id);
            $stmt->execute();
            $stmt->close();
            $_SESSION['flash_success'] = 'Status akun berhasil diperbarui.';
            redirect('pengguna.php');
        }
    }
}

// Reset Password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reset_password') {
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        $error = 'Token keamanan tidak valid.';
    } else {
        $id = (int)($_POST['id'] ?? 0);
        $newPass = (string)($_POST['new_password'] ?? '');

        if (strlen($newPass) < 6) {
            $error = 'Password baru minimal 6 karakter.';
        } else {
            $hash = password_hash($newPass, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE pengguna SET password_hash = ? WHERE id_pengguna = ?");
            $stmt->bind_param('si', $hash, $id);
            $stmt->execute();
            $stmt->close();
            $_SESSION['flash_success'] = 'Password pengguna berhasil direset.';
            redirect('pengguna.php');
        }
    }
}

// Hapus Pengguna
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'hapus') {
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        $error = 'Token keamanan tidak valid.';
    } else {
        $id = (int)($_POST['id'] ?? 0);
        if ($id === (int)($_SESSION['user_id'] ?? 0)) {
            $error = 'Anda tidak dapat menghapus akun Anda sendiri.';
        } else {
            $stmt = $conn->prepare("DELETE FROM pengguna WHERE id_pengguna = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();
            $_SESSION['flash_success'] = 'Akun pengguna berhasil dihapus.';
            redirect('pengguna.php');
        }
    }
}

// Query list pengguna
$sql = "SELECT p.*, r.nama_role, r.keterangan as keterangan_role, d.nama AS nama_dosen
        FROM pengguna p
        LEFT JOIN roles r ON p.id_role = r.id_role
        LEFT JOIN dosen d ON p.id_dosen = d.id_dosen
        ORDER BY p.id_pengguna ASC";
$res = $conn->query($sql);
$userList = [];
if ($res) {
    while ($r = $res->fetch_assoc()) {
        $userList[] = $r;
    }
}

// Roles for dropdown
$roles = $conn->query("SELECT * FROM roles ORDER BY id_role ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Pengguna — SIM Dosen UMB</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="app-body">

<?php include 'topbar.php'; ?>
<?php include 'sidebar.php'; ?>

<main class="main-content animate-in">
    <div class="page-heading-flex">
        <div class="page-heading">
            <span class="eyebrow">HAK AKSES & KEAMANAN</span>
            <h1>Manajemen Pengguna</h1>
            <p>Kelola akun sistem, peran pengguna (RBAC), dan kredensial login.</p>
        </div>
    </div>

    <?php if ($sukses): ?>
        <div class="alert alert-success">✅ <?= htmlspecialchars($sukses) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 1fr 360px; gap: 24px; align-items: start;">
        <!-- List Pengguna -->
        <div class="dashboard-panel">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Pengguna</th>
                            <th>Username</th>
                            <th>Role / Peran</th>
                            <th>Status</th>
                            <th>Login Terakhir</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($userList)): ?>
                        <tr><td colspan="6" class="empty-state">Belum ada akun pengguna terdaftar.</td></tr>
                    <?php else: ?>
                        <?php foreach ($userList as $u): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($u['nama_tampilan'] ?: $u['username']) ?></strong>
                                <?php if ($u['email']): ?>
                                    <div style="font-size: 0.82rem; color: var(--gray-500);"><?= htmlspecialchars($u['email']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td><code><?= htmlspecialchars($u['username']) ?></code></td>
                            <td>
                                <span class="badge badge-info"><?= htmlspecialchars($u['nama_role'] ?? 'ADMIN') ?></span>
                            </td>
                            <td>
                                <form method="post" action="pengguna.php" style="display:inline;">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="id" value="<?= $u['id_pengguna'] ?>">
                                    <input type="hidden" name="aktif" value="<?= $u['aktif'] ? 0 : 1 ?>">
                                    <button type="submit" class="badge <?= $u['aktif'] ? 'badge-success' : 'badge-danger' ?>" 
                                            style="cursor:pointer; border:none;" 
                                            <?= $u['id_pengguna'] == ($_SESSION['user_id'] ?? 0) ? 'disabled title="Akun Anda"' : '' ?>>
                                        <?= $u['aktif'] ? 'Aktif' : 'Nonaktif' ?>
                                    </button>
                                </form>
                            </td>
                            <td class="text-muted" style="font-size: 0.85rem;">
                                <?= $u['login_terakhir'] ? date('d M Y H:i', strtotime($u['login_terakhir'])) : 'Belum pernah' ?>
                            </td>
                            <td>
                                <div style="display:flex; gap:6px;">
                                    <button onclick="promptReset(<?= $u['id_pengguna'] ?>, '<?= htmlspecialchars(addslashes($u['username'])) ?>')" class="btn btn-sm btn-outline" title="Reset Password">🔑</button>
                                    <?php if ($u['id_pengguna'] != ($_SESSION['user_id'] ?? 0)): ?>
                                        <form method="post" action="pengguna.php" onsubmit="return confirm('Yakin hapus akun pengguna ini?');" style="display:inline;">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="hapus">
                                            <input type="hidden" name="id" value="<?= $u['id_pengguna'] ?>">
                                            <button type="submit" class="btn btn-sm btn-danger" title="Hapus Akun">🗑</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Form Tambah Pengguna -->
        <div class="dashboard-panel">
            <div style="padding: 24px;">
                <h3 style="margin-top:0; font-size: 1.15rem; color: var(--navy);">➕ Tambah Akun Baru</h3>
                <p style="font-size: 0.88rem; color: var(--gray-600); margin-bottom: 20px;">Daftarkan akun operator atau dosen untuk akses portal.</p>

                <form method="post" action="pengguna.php">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="tambah">

                    <div class="form-group">
                        <label>Nama Lengkap / Tampilan <span class="required">*</span></label>
                        <input type="text" name="nama_tampilan" class="form-control" placeholder="Contoh: Budi Operator Prodi" required>
                    </div>

                    <div class="form-group">
                        <label>Username <span class="required">*</span></label>
                        <input type="text" name="username" class="form-control" placeholder="Contoh: op_teknik" required>
                    </div>

                    <div class="form-group">
                        <label>Email Pengguna</label>
                        <input type="email" name="email" class="form-control" placeholder="Contoh: op@umb.ac.id">
                    </div>

                    <div class="form-group">
                        <label>Password Akun <span class="required">*</span></label>
                        <input type="password" name="password" class="form-control" placeholder="Minimal 6 karakter" required>
                    </div>

                    <div class="form-group">
                        <label>Hak Akses / Peran (Role) <span class="required">*</span></label>
                        <select name="id_role" class="form-control" required>
                            <?php while ($r = $roles->fetch_assoc()): ?>
                                <option value="<?= $r['id_role'] ?>"><?= htmlspecialchars($r['nama_role']) ?> (<?= htmlspecialchars($r['keterangan']) ?>)</option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%;">Daftarkan Pengguna</button>
                </form>
            </div>
        </div>
    </div>
</main>

<!-- Hidden form for Reset Password -->
<form id="resetForm" method="post" action="pengguna.php" style="display:none;">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="reset_password">
    <input type="hidden" name="id" id="resetUserId">
    <input type="hidden" name="new_password" id="resetPasswordVal">
</form>

<script>
function promptReset(id, username) {
    const newP = prompt('Masukkan password baru untuk akun ' + username + ' (minimal 6 karakter):');
    if (newP !== null) {
        if (newP.trim().length < 6) {
            alert('Password minimal 6 karakter!');
            return;
        }
        document.getElementById('resetUserId').value = id;
        document.getElementById('resetPasswordVal').value = newP.trim();
        document.getElementById('resetForm').submit();
    }
}
</script>

</body>
</html>

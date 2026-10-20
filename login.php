<?php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/koneksi.php';

if (!isset($conn) || !($conn instanceof mysqli)) {
    http_response_code(500);
    exit('Koneksi database belum tersedia. Periksa koneksi.php.');
}
$conn->set_charset('utf8mb4');

// Jika sudah login, langsung arahkan ke dashboard
if (isset($_SESSION['user_id'], $_SESSION['role'])) {
    redirect('dashboard.php');
}

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $token = $_POST['csrf_token'] ?? null;

    if (!csrf_validate(is_string($token) ? $token : null)) {
        $error = 'Sesi formulir tidak valid atau sudah kedaluwarsa. Silakan muat ulang halaman.';
    } elseif ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } else {
        $stmt = $conn->prepare(
            "SELECT p.id_pengguna, p.nama_tampilan, p.username, p.password_hash, r.nama_role AS peran, p.aktif
             FROM pengguna p
             LEFT JOIN roles r ON p.id_role = r.id_role
             WHERE p.username = ? OR p.email = ?
             LIMIT 1"
        );

        if (!$stmt) {
            error_log('SIM Dosen login prepare error: ' . $conn->error);
            $error = 'Login belum dapat diproses. Silakan coba lagi.';
        } else {
            $stmt->bind_param('ss', $username, $username);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result ? $result->fetch_assoc() : null;
            $stmt->close();

            if (!$user || !password_verify($password, $user['password_hash']) || (int)$user['aktif'] !== 1) {
                $error = 'Username atau password tidak sesuai, atau akun tidak aktif.';
            } else {
                session_regenerate_id(true);
                $_SESSION['user_id'] = (int)$user['id_pengguna'];
                $_SESSION['nama_lengkap'] = $user['nama_tampilan'] ?: $user['username'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['peran'] ?? 'ADMIN';

                // Update waktu login terakhir
                $upd = $conn->prepare("UPDATE pengguna SET login_terakhir = NOW() WHERE id_pengguna = ?");
                if ($upd) {
                    $upd->bind_param('i', $user['id_pengguna']);
                    $upd->execute();
                    $upd->close();
                }

                // Perbarui CSRF token untuk sesi baru
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                redirect('dashboard.php');
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login SIM Dosen — Universitas Muhammadiyah Bengkulu</title>

    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- FontAwesome 6 Free Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --primary-blue: #0d9488;
            --primary-hover: #0f766e;
            --primary-dark: #134e4a;
            --primary-light: #f0fdfa;
            --text-dark: #1e293b;
            --text-muted: #64748b;
            --text-light: #94a3b8;
            --bg-body: #f1f5f9;
            --border-color: #e2e8f0;
            --transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #ecfdf5 0%, #f8fafc 50%, #ccfbf1 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            color: var(--text-dark);
        }

        .login-container {
            width: 100%;
            max-width: 980px;
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(15, 35, 75, 0.12);
            border: 1px solid rgba(226, 232, 240, 0.8);
            overflow: hidden;
            display: grid;
            grid-template-columns: 1.05fr 1fr;
        }

        /* Sisi Kiri (Branding & Semantik Info) */
        .login-sidebar {
            background: linear-gradient(150deg, #082f2a 0%, #115e59 50%, #0d9488 100%);
            color: #ffffff;
            padding: 48px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
        }

        .login-sidebar::before {
            content: '';
            position: absolute;
            top: -80px;
            right: -80px;
            width: 240px;
            height: 240px;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.12) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }

        .login-sidebar::after {
            content: '';
            position: absolute;
            bottom: -60px;
            left: -60px;
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, rgba(13, 148, 136, 0.35) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }

        .brand-header {
            display: flex;
            align-items: center;
            gap: 14px;
            position: relative;
            z-index: 2;
        }

        .logo-emblem {
            width: 52px;
            height: 52px;
            background: linear-gradient(135deg, #0d9488, #0b5f57);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffd700;
            font-size: 1.6rem;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.25);
            border: 2px solid #2dd4bf;
            flex-shrink: 0;
        }

        .brand-text h1 {
            font-size: 1.15rem;
            font-weight: 800;
            line-height: 1.2;
            color: #ffffff;
            letter-spacing: -0.3px;
        }

        .brand-text span {
            font-size: 0.76rem;
            color: #5eead4;
            font-weight: 600;
            display: block;
            margin-top: 2px;
        }

        .sidebar-middle {
            margin: 40px 0;
            position: relative;
            z-index: 2;
        }

        .badge-portal {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #ffd700;
            font-size: 0.72rem;
            font-weight: 800;
            padding: 5px 12px;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 16px;
        }

        .sidebar-middle h2 {
            font-size: 1.75rem;
            font-weight: 800;
            line-height: 1.25;
            margin-bottom: 14px;
            letter-spacing: -0.5px;
        }

        .sidebar-middle p {
            font-size: 0.92rem;
            color: #cbd5e1;
            line-height: 1.65;
            margin-bottom: 24px;
        }

        .feature-bullets {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .feature-bullets li {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.86rem;
            color: #e2e8f0;
        }

        .bullet-icon {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.15);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #2dd4bf;
            font-size: 0.75rem;
            flex-shrink: 0;
        }

        .sidebar-footer {
            position: relative;
            z-index: 2;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #5eead4;
            font-size: 0.85rem;
            font-weight: 600;
            text-decoration: none;
            transition: var(--transition);
        }

        .back-link:hover {
            color: #ffffff;
            transform: translateX(-3px);
        }

        /* Sisi Kanan (Formulir Login) */
        .login-content {
            padding: 48px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .form-header {
            margin-bottom: 26px;
        }

        .form-header h2 {
            font-size: 1.6rem;
            font-weight: 800;
            color: var(--text-dark);
            letter-spacing: -0.4px;
            margin-bottom: 6px;
        }

        .form-header p {
            font-size: 0.88rem;
            color: var(--text-muted);
            line-height: 1.5;
        }

        .alert-error {
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 0.86rem;
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: fadeIn 0.3s ease;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-label {
            display: block;
            font-size: 0.84rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 6px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
            font-size: 0.95rem;
            pointer-events: none;
            transition: var(--transition);
        }

        .form-control {
            width: 100%;
            padding: 12px 14px 12px 42px;
            border: 1.5px solid var(--border-color);
            border-radius: 10px;
            font-size: 0.92rem;
            color: var(--text-dark);
            background: #ffffff;
            outline: none;
            transition: var(--transition);
        }

        .form-control:focus {
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 4px rgba(13, 148, 136, 0.12);
        }

        .form-control:focus + .input-icon,
        .input-wrapper:focus-within .input-icon {
            color: var(--primary-blue);
        }

        .password-toggle-btn {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-light);
            cursor: pointer;
            font-size: 0.95rem;
            padding: 4px;
            transition: var(--transition);
        }

        .password-toggle-btn:hover {
            color: var(--primary-blue);
        }

        .btn-submit {
            width: 100%;
            background-color: var(--primary-blue);
            color: #ffffff;
            border: none;
            padding: 13px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 0.95rem;
            cursor: pointer;
            box-shadow: 0 4px 16px rgba(13, 148, 136, 0.28);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: var(--transition);
            margin-top: 8px;
        }

        .btn-submit:hover {
            background-color: var(--primary-hover);
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(13, 148, 136, 0.35);
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        /* Demo credentials card */
        .demo-card {
            margin-top: 24px;
            padding: 14px 16px;
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 12px;
            font-size: 0.82rem;
        }

        .demo-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 8px;
        }

        .demo-header strong {
            color: var(--primary-blue);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .btn-fill-demo {
            background: #ccfbf1;
            color: var(--primary-blue);
            border: none;
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 0.72rem;
            font-weight: 700;
            cursor: pointer;
            transition: var(--transition);
        }

        .btn-fill-demo:hover {
            background: var(--primary-blue);
            color: #fff;
        }

        .demo-row {
            display: flex;
            justify-content: space-between;
            color: var(--text-muted);
            margin-bottom: 4px;
        }

        .demo-row code {
            font-family: monospace;
            background: #e2e8f0;
            padding: 2px 6px;
            border-radius: 4px;
            color: var(--text-dark);
            font-weight: 700;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-6px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 860px) {
            .login-container {
                grid-template-columns: 1fr;
            }
            .login-sidebar {
                padding: 36px 30px;
            }
            .login-content {
                padding: 36px 30px;
            }
            .sidebar-middle {
                margin: 24px 0;
            }
        }
    </style>
</head>
<body>

<main class="login-container">
    <!-- Sisi Kiri: Branding & Informasi -->
    <aside class="login-sidebar">
        <div>
            <div class="brand-header">
                <div class="logo-emblem">
                    <i class="fa-solid fa-graduation-cap"></i>
                </div>
                <div class="brand-text">
                    <h1>UNIVERSITAS MUHAMMADIYAH BENGKULU</h1>
                    <span>Unggul · Islam · Berkemajuan</span>
                </div>
            </div>

            <div class="sidebar-middle">
                <span class="badge-portal">
                    <i class="fa-solid fa-shield-halved"></i> SIM DOSEN TERPADU
                </span>
                <h2>Satu Portal Data Pokok & Web Semantik Dosen</h2>
                <p>
                    Kelola data akademik, jabatan fungsional, kepangkatan, dan penempatan dosen UMB secara terpusat, transparan, dan terhubung dengan standar RDF/SPARQL.
                </p>

                <ul class="feature-bullets">
                    <li>
                        <span class="bullet-icon"><i class="fa-solid fa-check"></i></span>
                        Akses RBAC terotentikasi & aman (CSRF Protected)
                    </li>
                    <li>
                        <span class="bullet-icon"><i class="fa-solid fa-check"></i></span>
                        Integrasi data akademik dengan SIM Mahasiswa & Skripsi
                    </li>
                    <li>
                        <span class="bullet-icon"><i class="fa-solid fa-check"></i></span>
                        Layanan Web Semantik JSON-LD & REST API siap pakai
                    </li>
                </ul>
            </div>
        </div>

        <div class="sidebar-footer">
            <a href="index.php" class="back-link">
                <i class="fa-solid fa-arrow-left"></i> Kembali ke Halaman Utama
            </a>
        </div>
    </aside>

    <!-- Sisi Kanan: Formulir Login -->
    <section class="login-content">
        <div class="form-header">
            <h2>Masuk ke Dashboard</h2>
            <p>Silakan masukkan akun resmi pengelola atau dosen untuk melanjutkan.</p>
        </div>

        <?php if ($error !== ''): ?>
            <div class="alert-error" role="alert">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span><?= e($error) ?></span>
            </div>
        <?php endif; ?>

        <form method="post" action="login.php" autocomplete="on">
            <?= csrf_field() ?>

            <div class="form-group">
                <label for="username" class="form-label">Username atau Email</label>
                <div class="input-wrapper">
                    <i class="fa-solid fa-user input-icon"></i>
                    <input
                        type="text"
                        class="form-control"
                        id="username"
                        name="username"
                        value="<?= e($username) ?>"
                        maxlength="100"
                        placeholder="Masukkan username atau email"
                        autocomplete="username"
                        required
                        autofocus
                    >
                </div>
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <div class="input-wrapper">
                    <i class="fa-solid fa-lock input-icon"></i>
                    <input
                        type="password"
                        class="form-control"
                        id="password"
                        name="password"
                        placeholder="Masukkan password"
                        autocomplete="current-password"
                        required
                    >
                    <button
                        type="button"
                        class="password-toggle-btn"
                        id="togglePasswordBtn"
                        aria-label="Tampilkan atau sembunyikan password"
                        title="Tampilkan / Sembunyikan Password"
                    >
                        <i class="fa-regular fa-eye-slash" id="eyeIcon"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-submit">
                <i class="fa-solid fa-arrow-right-to-bracket"></i> Masuk Sekarang
            </button>
        </form>

        <!-- Akun Demo & Pengujian -->
        <div class="demo-card">
            <div class="demo-header">
                <strong><i class="fa-solid fa-key"></i> Akun Pengujian Sistem</strong>
                <button type="button" class="btn-fill-demo" onclick="fillAdminDemo()">
                    <i class="fa-solid fa-wand-magic-sparkles"></i> Isi Otomatis
                </button>
            </div>
            <div class="demo-row">
                <span>Username Admin:</span>
                <code>admin</code>
            </div>
            <div class="demo-row">
                <span>Password:</span>
                <code>admin123</code>
            </div>
        </div>
    </section>
</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const toggleBtn = document.getElementById('togglePasswordBtn');
    const passwordInput = document.getElementById('password');
    const eyeIcon = document.getElementById('eyeIcon');

    if (toggleBtn && passwordInput && eyeIcon) {
        toggleBtn.addEventListener('click', function () {
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
            } else {
                passwordInput.type = 'password';
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
            }
        });
    }

    window.fillAdminDemo = function () {
        const u = document.getElementById('username');
        const p = document.getElementById('password');
        if (u) u.value = 'admin';
        if (p) p.value = 'admin123';
        if (u) u.focus();
    };
});
</script>
</body>
</html>

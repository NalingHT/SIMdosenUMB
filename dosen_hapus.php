<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/koneksi.php';
require_once __DIR__ . '/includes/helpers.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('dosen.php');
}

$token = $_POST['csrf_token'] ?? null;
if (!csrf_validate(is_string($token) ? $token : null)) {
    http_response_code(403);
    exit('403 Forbidden: token CSRF tidak valid.');
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    redirect('dosen.php');
}

$stmt = $conn->prepare("DELETE FROM dosen WHERE id_dosen = ?");
$stmt->bind_param('i', $id);

if ($stmt->execute()) {
    $_SESSION['flash_success'] = 'Data dosen berhasil dihapus.';
} else {
    $_SESSION['flash_success'] = 'Gagal menghapus dosen: ' . $stmt->error;
}
$stmt->close();

redirect('dosen.php');

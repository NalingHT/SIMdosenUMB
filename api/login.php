<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../koneksi.php';

$response = [
    'success' => false,
    'message' => '',
    'data'    => null
];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    $response['message'] = 'Method tidak diizinkan. Gunakan POST.';
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

$raw = file_get_contents('php://input');
$input = json_decode($raw, true);
if (!is_array($input)) {
    $input = $_POST;
}

$username = trim($input['username'] ?? '');
$password = (string)($input['password'] ?? '');

if ($username === '' || $password === '') {
    http_response_code(400);
    $response['message'] = 'Username dan password wajib diisi.';
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $stmt = $conn->prepare(
        "SELECT p.id_pengguna, p.nama_tampilan, p.username, p.password_hash, r.nama_role AS peran, p.aktif
         FROM pengguna p
         LEFT JOIN roles r ON p.id_role = r.id_role
         WHERE p.username = ? OR p.email = ?
         LIMIT 1"
    );
    $stmt->bind_param('ss', $username, $username);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user || !password_verify($password, $user['password_hash']) || (int)$user['aktif'] !== 1) {
        http_response_code(401);
        $response['message'] = 'Kredensial tidak valid atau akun dinonaktifkan.';
        echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Token simulasi untuk API Client
    $token = bin2hex(random_bytes(24));

    // Update waktu login terakhir
    $upd = $conn->prepare("UPDATE pengguna SET login_terakhir = NOW() WHERE id_pengguna = ?");
    if ($upd) {
        $upd->bind_param('i', $user['id_pengguna']);
        $upd->execute();
        $upd->close();
    }

    $response['success'] = true;
    $response['message'] = 'Autentikasi berhasil.';
    $response['data'] = [
        'id_pengguna'   => (int)$user['id_pengguna'],
        'nama_tampilan' => $user['nama_tampilan'] ?: $user['username'],
        'username'      => $user['username'],
        'peran'         => $user['peran'] ?? 'ADMIN',
        'token'         => $token,
        'token_type'    => 'Bearer',
        'expires_in'    => 86400
    ];

    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = 'Terjadi kesalahan internal server: ' . $e->getMessage();
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

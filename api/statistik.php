<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../koneksi.php';

$response = [
    'success' => true,
    'message' => 'Statistik dosen berhasil dikalkulasi.',
    'data'    => []
];

try {
    // Total Dosen
    $totalDosen = (int)$conn->query("SELECT COUNT(*) AS c FROM dosen")->fetch_assoc()['c'];

    // By Status Aktivitas
    $byAktivitas = [];
    $res = $conn->query("SELECT status_aktivitas, COUNT(*) as jumlah FROM dosen GROUP BY status_aktivitas");
    while ($r = $res->fetch_assoc()) {
        $byAktivitas[$r['status_aktivitas']] = (int)$r['jumlah'];
    }

    // By Status Serdos
    $bySerdos = [];
    $res = $conn->query("SELECT status_serdos, COUNT(*) as jumlah FROM dosen GROUP BY status_serdos");
    while ($r = $res->fetch_assoc()) {
        $bySerdos[$r['status_serdos']] = (int)$r['jumlah'];
    }

    // By Jabatan Fungsional
    $byJabatan = [];
    $res = $conn->query("SELECT COALESCE(jf.nama_jabatan, 'Belum Ada') AS jabatan, COUNT(d.id_dosen) AS jumlah 
                         FROM dosen d 
                         LEFT JOIN jabatan_fungsional jf ON d.id_jabatan = jf.id_jabatan 
                         GROUP BY jf.nama_jabatan 
                         ORDER BY jumlah DESC");
    while ($r = $res->fetch_assoc()) {
        $byJabatan[$r['jabatan']] = (int)$r['jumlah'];
    }

    // By Program Studi
    $byProdi = [];
    $res = $conn->query("SELECT COALESCE(p.program_studi, 'Belum Ditempatkan') AS prodi, COUNT(d.id_dosen) AS jumlah 
                         FROM dosen d 
                         LEFT JOIN penempatan p ON d.id_penempatan = p.id_penempatan 
                         GROUP BY p.program_studi 
                         ORDER BY jumlah DESC");
    while ($r = $res->fetch_assoc()) {
        $byProdi[$r['prodi']] = (int)$r['jumlah'];
    }

    $response['data'] = [
        'total_dosen'  => $totalDosen,
        'by_aktivitas' => $byAktivitas,
        'by_serdos'    => $bySerdos,
        'by_jabatan'   => $byJabatan,
        'by_prodi'     => $byProdi
    ];

    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    $response['success'] = false;
    $response['message'] = 'Gagal menghitung statistik: ' . $e->getMessage();
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

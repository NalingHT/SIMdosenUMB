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
    'message' => 'Data jabatan fungsional berhasil diambil.',
    'data'    => []
];

try {
    $sql = "SELECT j.*, COUNT(d.id_dosen) AS total_dosen 
            FROM jabatan_fungsional j
            LEFT JOIN dosen d ON j.id_jabatan = d.id_jabatan
            WHERE j.aktif = 1
            GROUP BY j.id_jabatan
            ORDER BY j.id_jabatan ASC";
    
    $res = $conn->query($sql);
    $data = [];
    while ($row = $res->fetch_assoc()) {
        $row['id_jabatan'] = (int)$row['id_jabatan'];
        $row['total_dosen'] = (int)$row['total_dosen'];
        $row['aktif']       = (bool)$row['aktif'];
        $data[] = $row;
    }
    
    $response['data'] = $data;
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    $response['success'] = false;
    $response['message'] = 'Gagal mengambil data jabatan: ' . $e->getMessage();
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

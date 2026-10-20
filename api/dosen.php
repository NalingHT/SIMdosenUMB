<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../koneksi.php';

$response = [
    'success' => false,
    'message' => '',
    'data'    => null,
    'meta'    => null
];

$method = $_SERVER['REQUEST_METHOD'];

try {
    // ══════════════════════════════════════════════════════
    // METHOD: GET (Detail atau List)
    // ══════════════════════════════════════════════════════
    if ($method === 'GET') {
        $id    = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $nip   = isset($_GET['nip']) ? trim($_GET['nip']) : '';
        $nuptk = isset($_GET['nuptk']) ? trim($_GET['nuptk']) : '';

        // DETAIL DOSEN TUNGGAL
        if ($id > 0 || $nip !== '' || $nuptk !== '') {
            $whereSingle = '';
            $paramSingle = '';
            $typeSingle  = '';

            if ($id > 0) {
                $whereSingle = "d.id_dosen = ?";
                $paramSingle = $id;
                $typeSingle  = 'i';
            } elseif ($nip !== '') {
                $whereSingle = "d.nip = ?";
                $paramSingle = $nip;
                $typeSingle  = 's';
            } else {
                $whereSingle = "d.nuptk = ?";
                $paramSingle = $nuptk;
                $typeSingle  = 's';
            }

            $sql = "SELECT d.*, 
                           jf.nama_jabatan, 
                           kp.nama_pangkat, kp.golongan, kp.ruang,
                           pend.jenjang, 
                           ri.nama_rumpun, 
                           sk.status_pegawai, sk.ikatan_kerja,
                           p.universitas, p.fakultas, p.program_studi
                    FROM dosen d
                    LEFT JOIN jabatan_fungsional jf ON d.id_jabatan = jf.id_jabatan
                    LEFT JOIN kepangkatan kp ON d.id_kepangkatan = kp.id_kepangkatan
                    LEFT JOIN pendidikan pend ON d.id_pendidikan = pend.id_pendidikan
                    LEFT JOIN rumpun_ilmu ri ON d.id_rumpun_ilmu = ri.id_rumpun_ilmu
                    LEFT JOIN status_kepegawaian sk ON d.id_status_kepegawaian = sk.id_status_kepegawaian
                    LEFT JOIN penempatan p ON d.id_penempatan = p.id_penempatan
                    WHERE $whereSingle
                    LIMIT 1";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param($typeSingle, $paramSingle);
            $stmt->execute();
            $data = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$data) {
                http_response_code(404);
                $response['message'] = 'Data dosen tidak ditemukan.';
                echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                exit;
            }

            $response['success'] = true;
            $response['message'] = 'Data dosen ditemukan.';
            $response['data']    = $data;
            echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
        }

        // LIST DOSEN DENGAN FILTER & PAGINATION
        $search    = trim($_GET['search'] ?? $_GET['q'] ?? '');
        $fakultas  = trim($_GET['fakultas'] ?? '');
        $prodi     = trim($_GET['prodi'] ?? '');
        $status    = trim($_GET['status'] ?? '');
        $page      = max(1, (int)($_GET['page'] ?? 1));
        $limit     = max(1, min(100, (int)($_GET['limit'] ?? 10)));
        $offset    = ($page - 1) * $limit;

        $where = ["1=1"];
        $params = [];
        $types = '';

        if ($search !== '') {
            $where[] = "(d.nama LIKE ? OR d.nip LIKE ? OR d.nomor_registrasi LIKE ? OR d.nuptk LIKE ?)";
            $lk = "%$search%";
            $params[] = $lk; $params[] = $lk; $params[] = $lk; $params[] = $lk;
            $types .= 'ssss';
        }
        if ($fakultas !== '') {
            $where[] = "p.fakultas = ?";
            $params[] = $fakultas;
            $types .= 's';
        }
        if ($prodi !== '') {
            $where[] = "p.program_studi = ?";
            $params[] = $prodi;
            $types .= 's';
        }
        if ($status !== '') {
            $where[] = "d.status_aktivitas = ?";
            $params[] = $status;
            $types .= 's';
        }

        $whereStr = implode(" AND ", $where);

        // Count Total
        $sqlCount = "SELECT COUNT(*) AS total 
                     FROM dosen d 
                     LEFT JOIN penempatan p ON d.id_penempatan = p.id_penempatan 
                     WHERE $whereStr";
        $stmtC = $conn->prepare($sqlCount);
        if ($types) $stmtC->bind_param($types, ...$params);
        $stmtC->execute();
        $total = (int)$stmtC->get_result()->fetch_assoc()['total'];
        $stmtC->close();

        // Get List
        $sqlList = "SELECT d.id_dosen, d.nomor_registrasi, d.nuptk, d.nama, d.nip,
                           jf.nama_jabatan, 
                           kp.nama_pangkat, kp.golongan, kp.ruang,
                           pend.jenjang, 
                           ri.nama_rumpun, 
                           sk.status_pegawai, 
                           p.fakultas, p.program_studi,
                           d.status_serdos, d.status_aktivitas, d.created_at
                    FROM dosen d
                    LEFT JOIN jabatan_fungsional jf ON d.id_jabatan = jf.id_jabatan
                    LEFT JOIN kepangkatan kp ON d.id_kepangkatan = kp.id_kepangkatan
                    LEFT JOIN pendidikan pend ON d.id_pendidikan = pend.id_pendidikan
                    LEFT JOIN rumpun_ilmu ri ON d.id_rumpun_ilmu = ri.id_rumpun_ilmu
                    LEFT JOIN status_kepegawaian sk ON d.id_status_kepegawaian = sk.id_status_kepegawaian
                    LEFT JOIN penempatan p ON d.id_penempatan = p.id_penempatan
                    WHERE $whereStr
                    ORDER BY d.nama ASC
                    LIMIT ? OFFSET ?";
        
        $paramsList = $params;
        $paramsList[] = $limit;
        $paramsList[] = $offset;
        $typesList = $types . 'ii';

        $stmtL = $conn->prepare($sqlList);
        $stmtL->bind_param($typesList, ...$paramsList);
        $stmtL->execute();
        $resL = $stmtL->get_result();
        $list = [];
        while ($r = $resL->fetch_assoc()) {
            $list[] = $r;
        }
        $stmtL->close();

        $response['success'] = true;
        $response['message'] = 'Data dosen berhasil diambil.';
        $response['data']    = $list;
        $response['meta']    = [
            'total'       => $total,
            'page'        => $page,
            'limit'       => $limit,
            'total_pages' => max(1, ceil($total / $limit))
        ];

        echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ══════════════════════════════════════════════════════
    // METHOD: POST (Tambah Dosen)
    // ══════════════════════════════════════════════════════
    if ($method === 'POST') {
        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true);
        if (!is_array($input)) $input = $_POST;

        $nama = trim($input['nama'] ?? '');
        if ($nama === '') {
            http_response_code(400);
            $response['message'] = 'Nama dosen wajib diisi.';
            echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
        }

        $noReg  = trim($input['nomor_registrasi'] ?? '') ?: null;
        $nuptk  = trim($input['nuptk'] ?? '') ?: null;
        $nip    = trim($input['nip'] ?? '') ?: null;
        $idJab  = !empty($input['id_jabatan']) ? (int)$input['id_jabatan'] : null;
        $idPkt  = !empty($input['id_kepangkatan']) ? (int)$input['id_kepangkatan'] : null;
        $idPend = !empty($input['id_pendidikan']) ? (int)$input['id_pendidikan'] : null;
        $idRmp  = !empty($input['id_rumpun_ilmu']) ? (int)$input['id_rumpun_ilmu'] : null;
        $idSt   = !empty($input['id_status_kepegawaian']) ? (int)$input['id_status_kepegawaian'] : null;
        $idPen  = !empty($input['id_penempatan']) ? (int)$input['id_penempatan'] : null;
        $serdos = $input['status_serdos'] ?? 'Belum Diketahui';
        $aktif  = $input['status_aktivitas'] ?? 'Aktif';
        $ket    = trim($input['keterangan'] ?? '') ?: null;

        $sql = "INSERT INTO dosen 
                (nomor_registrasi, nuptk, nama, nip, id_jabatan, id_kepangkatan, id_pendidikan, 
                 id_rumpun_ilmu, id_status_kepegawaian, id_penempatan, status_serdos, status_aktivitas, keterangan)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssssiiiiissss', $noReg, $nuptk, $nama, $nip, $idJab, $idPkt, $idPend, $idRmp, $idSt, $idPen, $serdos, $aktif, $ket);
        
        if ($stmt->execute()) {
            $newId = $stmt->insert_id;
            $stmt->close();
            http_response_code(201);
            $response['success'] = true;
            $response['message'] = 'Dosen berhasil ditambahkan.';
            $response['data']    = ['id_dosen' => $newId];
        } else {
            http_response_code(400);
            $response['message'] = 'Gagal menyimpan dosen: ' . $stmt->error;
            $stmt->close();
        }

        echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ══════════════════════════════════════════════════════
    // METHOD: PUT (Update Dosen)
    // ══════════════════════════════════════════════════════
    if ($method === 'PUT') {
        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true);
        if (!is_array($input)) {
            http_response_code(400);
            $response['message'] = 'Format request JSON tidak valid.';
            echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
        }

        $id = (int)($input['id_dosen'] ?? $_GET['id'] ?? 0);
        if ($id <= 0) {
            http_response_code(400);
            $response['message'] = 'ID dosen (id_dosen) wajib disertakan.';
            echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
        }

        $nama   = trim($input['nama'] ?? '');
        $noReg  = trim($input['nomor_registrasi'] ?? '') ?: null;
        $nuptk  = trim($input['nuptk'] ?? '') ?: null;
        $nip    = trim($input['nip'] ?? '') ?: null;
        $idJab  = !empty($input['id_jabatan']) ? (int)$input['id_jabatan'] : null;
        $idPkt  = !empty($input['id_kepangkatan']) ? (int)$input['id_kepangkatan'] : null;
        $idPend = !empty($input['id_pendidikan']) ? (int)$input['id_pendidikan'] : null;
        $idRmp  = !empty($input['id_rumpun_ilmu']) ? (int)$input['id_rumpun_ilmu'] : null;
        $idSt   = !empty($input['id_status_kepegawaian']) ? (int)$input['id_status_kepegawaian'] : null;
        $idPen  = !empty($input['id_penempatan']) ? (int)$input['id_penempatan'] : null;
        $serdos = $input['status_serdos'] ?? 'Belum Diketahui';
        $aktif  = $input['status_aktivitas'] ?? 'Aktif';
        $ket    = trim($input['keterangan'] ?? '') ?: null;

        $sql = "UPDATE dosen SET
                    nomor_registrasi = ?, nuptk = ?, nama = ?, nip = ?,
                    id_jabatan = ?, id_kepangkatan = ?, id_pendidikan = ?,
                    id_rumpun_ilmu = ?, id_status_kepegawaian = ?, id_penempatan = ?,
                    status_serdos = ?, status_aktivitas = ?, keterangan = ?
                WHERE id_dosen = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssssiiiiissssi', $noReg, $nuptk, $nama, $nip, $idJab, $idPkt, $idPend, $idRmp, $idSt, $idPen, $serdos, $aktif, $ket, $id);

        if ($stmt->execute()) {
            $stmt->close();
            $response['success'] = true;
            $response['message'] = 'Data dosen berhasil diperbarui.';
            $response['data']    = ['id_dosen' => $id];
        } else {
            http_response_code(400);
            $response['message'] = 'Gagal memperbarui dosen: ' . $stmt->error;
            $stmt->close();
        }

        echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ══════════════════════════════════════════════════════
    // METHOD: DELETE (Hapus Dosen)
    // ══════════════════════════════════════════════════════
    if ($method === 'DELETE') {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            $raw = file_get_contents('php://input');
            $input = json_decode($raw, true);
            $id = (int)($input['id_dosen'] ?? 0);
        }

        if ($id <= 0) {
            http_response_code(400);
            $response['message'] = 'ID dosen (id_dosen) wajib disertakan untuk penghapusan.';
            echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
        }

        $stmt = $conn->prepare("DELETE FROM dosen WHERE id_dosen = ?");
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            $affected = $stmt->affected_rows;
            $stmt->close();
            if ($affected > 0) {
                $response['success'] = true;
                $response['message'] = 'Dosen berhasil dihapus.';
            } else {
                http_response_code(404);
                $response['message'] = 'Dosen tidak ditemukan.';
            }
        } else {
            http_response_code(400);
            $response['message'] = 'Gagal menghapus dosen: ' . $stmt->error;
            $stmt->close();
        }

        echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    http_response_code(405);
    $response['message'] = 'Method tidak didukung.';
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = 'Kesalahan server: ' . $e->getMessage();
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

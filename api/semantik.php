<?php
// Endpoint Web Semantik (JSON-LD / Schema.org) untuk SIM Dosen
// Mendukung format JSON-LD murni (Content-Type: application/ld+json) atau tampilan HTML visual jika dibuka di browser

require_once __DIR__ . '/../koneksi.php';

$idDosen = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$format  = $_GET['format'] ?? '';
$accept  = $_SERVER['HTTP_ACCEPT'] ?? '';

$sql = "SELECT d.*, 
               jf.nama_jabatan, 
               kp.nama_pangkat, kp.golongan, kp.ruang,
               pend.jenjang, 
               ri.nama_rumpun, 
               sk.status_pegawai, 
               p.universitas, p.fakultas, p.program_studi
        FROM dosen d
        LEFT JOIN jabatan_fungsional jf ON d.id_jabatan = jf.id_jabatan
        LEFT JOIN kepangkatan kp ON d.id_kepangkatan = kp.id_kepangkatan
        LEFT JOIN pendidikan pend ON d.id_pendidikan = pend.id_pendidikan
        LEFT JOIN rumpun_ilmu ri ON d.id_rumpun_ilmu = ri.id_rumpun_ilmu
        LEFT JOIN status_kepegawaian sk ON d.id_status_kepegawaian = sk.id_status_kepegawaian
        LEFT JOIN penempatan p ON d.id_penempatan = p.id_penempatan ";

if ($idDosen > 0) {
    $sql .= "WHERE d.id_dosen = " . $idDosen;
} else {
    $sql .= "ORDER BY d.nama ASC LIMIT 50";
}

$res = $conn->query($sql);
$graph = [];

$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";

while ($d = $res->fetch_assoc()) {
    $person = [
        "@type" => "Person",
        "@id"   => $baseUrl . "/simdosen/dosen_detail.php?id=" . $d['id_dosen'],
        "name"  => $d['nama'],
        "identifier" => [
            [
                "@type" => "PropertyValue",
                "name"  => "NIP",
                "value" => $d['nip'] ?: null
            ],
            [
                "@type" => "PropertyValue",
                "name"  => "NIDN / Nomor Registrasi",
                "value" => $d['nomor_registrasi'] ?: null
            ],
            [
                "@type" => "PropertyValue",
                "name"  => "NUPTK",
                "value" => $d['nuptk'] ?: null
            ]
        ],
        "jobTitle" => $d['nama_jabatan'] ?: "Dosen",
        "worksFor" => [
            "@type" => "CollegeOrUniversity",
            "name"  => $d['universitas'] ?: "Universitas Muhammadiyah Bengkulu",
            "department" => [
                "@type" => "AcademicDepartment",
                "name"  => $d['program_studi'] ?: "Teknik Informatika",
                "parentOrganization" => [
                    "@type" => "Faculty",
                    "name"  => $d['fakultas'] ?: "Fakultas Teknik"
                ]
            ]
        ],
        "hasCredential" => [
            "@type" => "EducationalOccupationalCredential",
            "credentialCategory" => "degree",
            "educationalLevel"   => $d['jenjang'] ?: "S2"
        ],
        "knowsAbout" => $d['nama_rumpun'] ?: "Teknologi Informasi",
        "additionalProperty" => [
            [
                "@type" => "PropertyValue",
                "name"  => "Status Sertifikasi Dosen",
                "value" => $d['status_serdos']
            ],
            [
                "@type" => "PropertyValue",
                "name"  => "Status Aktivitas",
                "value" => $d['status_aktivitas']
            ],
            [
                "@type" => "PropertyValue",
                "name"  => "Kepangkatan",
                "value" => ($d['nama_pangkat'] ? $d['nama_pangkat'] . " (" . $d['golongan'] . "/" . $d['ruang'] . ")" : "-")
            ]
        ]
    ];
    $graph[] = $person;
}

$jsonLd = [
    "@context" => [
        "schema" => "https://schema.org/",
        "xsd"    => "http://www.w3.org/2001/XMLSchema#",
        "@vocab" => "https://schema.org/"
    ],
    "@type" => "ItemList",
    "name"  => "Data Semantik Dosen Universitas Muhammadiyah Bengkulu",
    "description" => "Koleksi data terstruktur profil dosen berbasis Web Semantik dan Schema.org Linked Data.",
    "numberOfItems" => count($graph),
    "itemListElement" => $graph
];

// Content negotiation
$wantsJson = ($format === 'json' || strpos($accept, 'application/ld+json') !== false || strpos($accept, 'application/json') !== false);

if ($wantsJson) {
    header('Content-Type: application/ld+json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    echo json_encode($jsonLd, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Web Semantik SIM Dosen (JSON-LD Linked Data) — UMB</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .code-box {
            background: #0f172a;
            color: #38bdf8;
            padding: 24px;
            border-radius: 12px;
            overflow-x: auto;
            font-family: 'Consolas', 'Monaco', monospace;
            font-size: 0.88rem;
            line-height: 1.5;
            box-shadow: inset 0 2px 10px rgba(0,0,0,0.5);
            max-height: 600px;
        }
        .meta-tag {
            background: #e0f2fe;
            color: #0369a1;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.82rem;
            font-weight: 600;
        }
    </style>
</head>
<body class="app-body">

<div style="max-width: 1100px; margin: 40px auto; padding: 0 20px;">
    <div class="page-heading-flex">
        <div>
            <div style="display:flex; gap:8px; margin-bottom:8px;">
                <span class="meta-tag">Semantic Web 3.0</span>
                <span class="meta-tag">JSON-LD 1.1</span>
                <span class="meta-tag">Schema.org Vocabulary</span>
            </div>
            <h1 style="font-size: 2rem; color: var(--navy); margin: 0 0 8px 0;">🌐 Web Semantik Data Dosen</h1>
            <p style="color: var(--gray-600); margin: 0;">Layanan interoperabilitas data terstruktur dan Linked Open Data (LOD) profil pendidik perguruan tinggi.</p>
        </div>
        <div style="display:flex; gap:10px;">
            <a href="semantik.php?format=json" target="_blank" class="btn btn-primary">⚡ Unduh Raw JSON-LD</a>
            <a href="../api_docs.php" class="btn btn-outline">⬅ Dokumentasi API</a>
        </div>
    </div>

    <div class="dashboard-panel" style="margin-top: 24px;">
        <div style="padding: 24px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <div>
                    <strong style="color: var(--navy); font-size: 1.05rem;">Payload Linked Data (schema:Person & schema:CollegeOrUniversity)</strong>
                    <div style="font-size: 0.85rem; color: var(--gray-500);">Total <?= count($graph) ?> entitas dosen terpetakan ke ontologi global.</div>
                </div>
                <button onclick="navigator.clipboard.writeText(document.getElementById('jsonOutput').innerText); alert('JSON-LD berhasil disalin ke clipboard!');" class="btn btn-sm btn-outline">📋 Salin JSON-LD</button>
            </div>

            <pre class="code-box" id="jsonOutput"><code><?= htmlspecialchars(json_encode($jsonLd, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) ?></code></pre>
        </div>
    </div>
</div>

</body>
</html>

<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/koneksi.php';

require_login();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dokumentasi API & Web Service — SIM Dosen UMB</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .api-card {
            background: #fff;
            border-radius: var(--radius-md);
            border: 1px solid var(--border);
            margin-bottom: 20px;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }
        .api-header {
            padding: 16px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #f8fafc;
            border-bottom: 1px solid var(--border);
            cursor: pointer;
        }
        .api-method {
            font-weight: 800;
            font-size: 0.8rem;
            padding: 4px 10px;
            border-radius: 6px;
            text-transform: uppercase;
            font-family: monospace;
            letter-spacing: 0.5px;
        }
        .method-get { background: #dcfce7; color: #15803d; }
        .method-post { background: #99f6e4; color: #1d4ed8; }
        .method-put { background: #fef3c7; color: #b45309; }
        .method-delete { background: #fee2e2; color: #b91c1c; }

        .api-path {
            font-family: monospace;
            font-weight: 700;
            font-size: 0.95rem;
            color: #1e293b;
            margin-left: 12px;
        }
        .api-body {
            padding: 20px;
        }
        .code-snippet {
            background: #0f172a;
            color: #f8fafc;
            padding: 14px 18px;
            border-radius: 8px;
            font-family: 'Consolas', monospace;
            font-size: 0.85rem;
            overflow-x: auto;
            margin: 8px 0;
        }
        .param-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.88rem;
            margin: 12px 0;
        }
        .param-table th, .param-table td {
            padding: 8px 12px;
            border-bottom: 1px solid var(--border);
            text-align: left;
        }
        .param-table th {
            background: #f1f5f9;
            color: #475569;
            font-weight: 600;
        }
        .tester-console {
            background: #1e293b;
            border-radius: var(--radius-md);
            color: #fff;
            padding: 24px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        .tester-console label {
            color: #cbd5e1;
            font-weight: 600;
            font-size: 0.85rem;
        }
        .tester-input {
            width: 100%;
            background: #0f172a;
            border: 1px solid #334155;
            color: #f8fafc;
            padding: 10px 14px;
            border-radius: 8px;
            font-family: monospace;
            font-size: 0.9rem;
            margin-top: 6px;
        }
        .tester-input:focus {
            outline: none;
            border-color: #5eead4;
        }
    </style>
</head>
<body class="app-body">

<?php include 'topbar.php'; ?>
<?php include 'sidebar.php'; ?>

<main class="main-content animate-in">
    <div class="page-heading-flex">
        <div class="page-heading">
            <span class="eyebrow">INTEGRASI & LAYANAN WEB</span>
            <h1>Dokumentasi API & Web Service</h1>
            <p>RESTful API dan Layanan Web Semantik SIM Dosen Universitas Muhammadiyah Bengkulu</p>
        </div>
        <div style="display:flex; gap:10px;">
            <a href="api/semantik.php" target="_blank" class="btn btn-primary">🌐 Web Semantik (JSON-LD)</a>
        </div>
    </div>

    <!-- Live Testing Console -->
    <div class="dashboard-panel" style="margin-bottom: 30px; border-top: 4px solid var(--blue);">
        <div style="padding: 24px;">
            <h2 style="margin: 0 0 6px 0; font-size: 1.3rem; color: var(--navy); display: flex; align-items: center; gap: 8px;">
                ⚡ Konsol Interaktif Pengujian API (Live API Tester)
            </h2>
            <p style="color: var(--gray-600); margin: 0 0 20px 0; font-size: 0.92rem;">
                Uji langsung endpoint web service SIM Dosen langsung dari browser Anda tanpa memerlukan Postman atau curl.
            </p>

            <div style="display: grid; grid-template-columns: 140px 1fr 140px; gap: 12px; margin-bottom: 16px;">
                <select id="testMethod" class="form-control" style="font-weight: 700;">
                    <option value="GET">GET</option>
                    <option value="POST">POST</option>
                    <option value="PUT">PUT</option>
                    <option value="DELETE">DELETE</option>
                </select>

                <select id="testPreset" class="form-control" onchange="applyPreset()">
                    <option value="api/dosen.php">GET /api/dosen.php (Daftar Dosen & Paginate)</option>
                    <option value="api/dosen.php?id=1">GET /api/dosen.php?id=1 (Detail Dosen ID 1)</option>
                    <option value="api/dosen.php?search=Hendra">GET /api/dosen.php?search=Hendra (Cari Nama/NIP)</option>
                    <option value="api/penempatan.php">GET /api/penempatan.php (Program Studi & Penempatan)</option>
                    <option value="api/jabatan.php">GET /api/jabatan.php (Jabatan Fungsional)</option>
                    <option value="api/statistik.php">GET /api/statistik.php (Statistik Agregat Dosen)</option>
                    <option value="api/semantik.php?format=json">GET /api/semantik.php?format=json (Linked Data JSON-LD)</option>
                </select>

                <button onclick="executeApiTest()" id="btnSend" class="btn btn-primary" style="height: 100%;">🚀 Kirim</button>
            </div>

            <div style="margin-bottom: 14px;">
                <label style="font-size: 0.85rem; font-weight: 600; color: var(--gray-700);">URL Endpoint Relatif:</label>
                <input type="text" id="testEndpoint" class="form-control" value="api/dosen.php" style="font-family: monospace; font-size: 0.92rem;">
            </div>

            <div id="bodyContainer" style="margin-bottom: 14px; display: none;">
                <label style="font-size: 0.85rem; font-weight: 600; color: var(--gray-700);">Request Body (JSON):</label>
                <textarea id="testBody" class="form-control" rows="4" style="font-family: monospace; font-size: 0.88rem;">{
  "nama": "Dosen Pengujian API, M.Kom.",
  "nip": "199501012024011001",
  "nomor_registrasi": "0001019501",
  "id_jabatan": 1,
  "id_pendidikan": 3,
  "id_penempatan": 1,
  "status_serdos": "Belum",
  "status_aktivitas": "Aktif"
}</textarea>
            </div>

            <!-- Output Response Box -->
            <div style="margin-top: 18px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                    <span style="font-weight: 700; font-size: 0.88rem; color: var(--navy);">Respons Server:</span>
                    <span id="responseStatus" class="badge" style="display: none;"></span>
                </div>
                <pre class="code-snippet" id="responseBox" style="min-height: 140px; max-height: 400px; margin: 0;"><code>Klik tombol "Kirim" untuk menjalankan pengujian live endpoint.</code></pre>
            </div>
        </div>
    </div>

    <!-- Endpoint Documentation Reference -->
    <h2 style="font-size: 1.4rem; color: var(--navy); margin-bottom: 18px;">📑 Daftar Endpoint API</h2>

    <!-- 1. GET Dosen -->
    <div class="api-card">
        <div class="api-header">
            <div>
                <span class="api-method method-get">GET</span>
                <span class="api-path">/simdosen/api/dosen.php</span>
            </div>
            <span style="font-size: 0.85rem; color: var(--gray-500);">Mengambil daftar dosen dengan filter & pagination</span>
        </div>
        <div class="api-body">
            <p style="margin-top:0; font-size: 0.92rem;">Mengembalikan data daftar dosen, status aktivitas, penempatan akademik, dan metadata pagination.</p>
            <table class="param-table">
                <thead>
                    <tr><th>Parameter</th><th>Tipe</th><th>Wajib</th><th>Deskripsi</th></tr>
                </thead>
                <tbody>
                    <tr><td><code>search</code> / <code>q</code></td><td>string</td><td>Tidak</td><td>Kata kunci pencarian (nama, NIP, atau NIDN)</td></tr>
                    <tr><td><code>fakultas</code></td><td>string</td><td>Tidak</td><td>Filter nama fakultas</td></tr>
                    <tr><td><code>prodi</code></td><td>string</td><td>Tidak</td><td>Filter nama program studi</td></tr>
                    <tr><td><code>status</code></td><td>string</td><td>Tidak</td><td>Status aktivitas (Aktif, Cuti, Tidak Aktif)</td></tr>
                    <tr><td><code>page</code></td><td>integer</td><td>Tidak</td><td>Nomor halaman data (default: 1)</td></tr>
                    <tr><td><code>limit</code></td><td>integer</td><td>Tidak</td><td>Jumlah data per halaman (default: 10, max: 100)</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- 2. GET Dosen Detail -->
    <div class="api-card">
        <div class="api-header">
            <div>
                <span class="api-method method-get">GET</span>
                <span class="api-path">/simdosen/api/dosen.php?id={id}</span>
            </div>
            <span style="font-size: 0.85rem; color: var(--gray-500);">Detail lengkap satu dosen</span>
        </div>
        <div class="api-body">
            <p style="margin-top:0; font-size: 0.92rem;">Mengambil biodata lengkap dosen berdasarkan <code>id</code>, <code>nip</code>, atau <code>nuptk</code>.</p>
        </div>
    </div>

    <!-- 3. POST Dosen -->
    <div class="api-card">
        <div class="api-header">
            <div>
                <span class="api-method method-post">POST</span>
                <span class="api-path">/simdosen/api/dosen.php</span>
            </div>
            <span style="font-size: 0.85rem; color: var(--gray-500);">Menambahkan dosen baru</span>
        </div>
        <div class="api-body">
            <p style="margin-top:0; font-size: 0.92rem;">Menambahkan record dosen baru ke database SIM Dosen.</p>
            <div class="code-snippet">
Content-Type: application/json

{
  "nama": "Dr. Eko Prasetyo, M.T.",
  "nip": "198801022015041003",
  "nomor_registrasi": "0002018801",
  "id_jabatan": 2,
  "id_pendidikan": 4,
  "id_penempatan": 1,
  "status_serdos": "Sudah",
  "status_aktivitas": "Aktif"
}
            </div>
        </div>
    </div>

    <!-- 4. PUT Dosen -->
    <div class="api-card">
        <div class="api-header">
            <div>
                <span class="api-method method-put">PUT</span>
                <span class="api-path">/simdosen/api/dosen.php</span>
            </div>
            <span style="font-size: 0.85rem; color: var(--gray-500);">Memperbarui data dosen</span>
        </div>
        <div class="api-body">
            <p style="margin-top:0; font-size: 0.92rem;">Memperbarui informasi dosen yang sudah ada melalui method PUT dengan JSON payload.</p>
        </div>
    </div>

    <!-- 5. DELETE Dosen -->
    <div class="api-card">
        <div class="api-header">
            <div>
                <span class="api-method method-delete">DELETE</span>
                <span class="api-path">/simdosen/api/dosen.php?id={id}</span>
            </div>
            <span style="font-size: 0.85rem; color: var(--gray-500);">Menghapus data dosen</span>
        </div>
        <div class="api-body">
            <p style="margin-top:0; font-size: 0.92rem;">Menghapus record dosen berdasarkan ID.</p>
        </div>
    </div>

    <!-- 6. Web Semantik -->
    <div class="api-card">
        <div class="api-header" style="background: #f0fdfa;">
            <div>
                <span class="api-method" style="background:#5eead4; color:#0f766e;">LINKED DATA</span>
                <span class="api-path">/simdosen/api/semantik.php?format=json</span>
            </div>
            <span style="font-size: 0.85rem; color: #0f766e; font-weight: 600;">Format Web Semantik (JSON-LD)</span>
        </div>
        <div class="api-body">
            <p style="margin-top:0; font-size: 0.92rem;">Endpoint integrasi Semantic Web berbasis standar W3C JSON-LD dan kosakata Schema.org (<code>Person</code>, <code>CollegeOrUniversity</code>, <code>EducationalOccupationalCredential</code>).</p>
        </div>
    </div>
</main>

<script>
function applyPreset() {
    const sel = document.getElementById('testPreset');
    const endpoint = sel.value;
    document.getElementById('testEndpoint').value = endpoint;
    document.getElementById('testMethod').value = 'GET';
    toggleBody();
}

function toggleBody() {
    const method = document.getElementById('testMethod').value;
    const bodyBox = document.getElementById('bodyContainer');
    if (method === 'POST' || method === 'PUT') {
        bodyBox.style.display = 'block';
    } else {
        bodyBox.style.display = 'none';
    }
}
document.getElementById('testMethod').addEventListener('change', toggleBody);

async function executeApiTest() {
    const method = document.getElementById('testMethod').value;
    const endpoint = document.getElementById('testEndpoint').value.trim();
    const bodyText = document.getElementById('testBody').value;
    const responseBox = document.getElementById('responseBox');
    const statusBadge = document.getElementById('responseStatus');
    const btnSend = document.getElementById('btnSend');

    btnSend.disabled = true;
    btnSend.innerText = 'Memproses...';
    responseBox.innerText = 'Mengirim request ke ' + endpoint + ' ...';

    const t0 = performance.now();

    try {
        const options = {
            method: method,
            headers: {
                'Accept': 'application/json'
            }
        };

        if (method === 'POST' || method === 'PUT') {
            options.headers['Content-Type'] = 'application/json';
            options.body = bodyText;
        }

        const res = await fetch(endpoint, options);
        const t1 = performance.now();
        const duration = Math.round(t1 - t0);

        statusBadge.style.display = 'inline-block';
        if (res.ok) {
            statusBadge.className = 'badge badge-success';
            statusBadge.innerText = 'HTTP ' + res.status + ' OK (' + duration + ' ms)';
        } else {
            statusBadge.className = 'badge badge-danger';
            statusBadge.innerText = 'HTTP ' + res.status + ' ' + res.statusText + ' (' + duration + ' ms)';
        }

        const text = await res.text();
        try {
            const json = JSON.parse(text);
            responseBox.innerText = JSON.stringify(json, null, 2);
        } catch {
            responseBox.innerText = text;
        }

    } catch (err) {
        statusBadge.style.display = 'inline-block';
        statusBadge.className = 'badge badge-danger';
        statusBadge.innerText = 'Network Error';
        responseBox.innerText = 'Error: ' + err.message;
    } finally {
        btnSend.disabled = false;
        btnSend.innerText = '🚀 Kirim';
    }
}
</script>

</body>
</html>

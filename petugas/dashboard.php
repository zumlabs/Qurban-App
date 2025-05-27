<?php
session_start();
include '../config/db.php';
include '../vendor/phpqrcode/qrlib.php';

if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

// Cek pesan sukses
if (isset($_SESSION['success_message'])) {
    $success = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

if (isset($_POST['tambah_penerima'])) {
    $nama = $_POST['nama'];
    $no_hp = $_POST['no_hp'];
    $jumlah_daging = $_POST['jumlah_daging'];
    $status_pengambilan = 'Belum Diambil'; // Atur default status
    $kode_qr = uniqid();

    // Insert data ke database
    $insert = mysqli_query($conn, "INSERT INTO penerima (nama, no_hp, kode_qr, jumlah_daging) VALUES ('$nama', '$no_hp', '$kode_qr', '$jumlah_daging')");

    if ($insert) {
        $path = "../assets/qrcodes/";
        $file = $path . $kode_qr . ".png";

        // Generate QR code
        QRcode::png($kode_qr, $file, 'L', 10, 2);

        // Encode image to base64
        $imageData = base64_encode(file_get_contents($file));

        // Siapkan pesan WhatsApp
        $wa_text = "*Assalamu'alaikum, $nama*\n\n";
        $wa_text .= "Berikut adalah informasi pengambilan daging qurban Anda:\n";
        $wa_text .= "-----------------------------\n";
        $wa_text .= "Nama: $nama\n";
        $wa_text .= "No HP: $no_hp\n";
        $wa_text .= "Jumlah Daging: $jumlah_daging kg\n";
        $wa_text .= "Status: $status_pengambilan\n";
        $wa_text .= "-----------------------------\n";
        $wa_text .= "Silakan tunjukkan QR Code berikut saat pengambilan\n\n";
        $wa_text .= "Terima kasih. Semoga berkah!";

        // Buat payload
        $data = [
            "phone" => "62" . ltrim($no_hp, "0"),
            "message" => $wa_text,
            "image" => $imageData,
            "mimeType" => "image/png"
        ];

        // Kirim request ke WhatsApp API
        $ch = curl_init("https://whatsapp-api-production-024a.up.railway.app/send-message");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Opsional: Logging respons
        // file_put_contents("log_whatsapp.txt", $response);

        $_SESSION['success_message'] = "Penerima berhasil ditambahkan dan pesan dikirim ke WhatsApp!";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Proses Edit Penerima
if (isset($_POST['update_penerima'])) {
    $id = $_POST['id'];
    $nama = $_POST['nama'];
    $no_hp = $_POST['no_hp'];
    $jumlah_daging = $_POST['jumlah_daging'];
    $status_pengambilan = $_POST['status_pengambilan'];

    $update = mysqli_query($conn, "UPDATE penerima SET nama = '$nama', no_hp = '$no_hp', jumlah_daging = '$jumlah_daging', status_pengambilan = '$status_pengambilan' WHERE id = '$id'");
    if ($update) {
        $_SESSION['success_message'] = "Penerima berhasil diperbarui!";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Proses Hapus Penerima
if (isset($_GET['hapus_id'])) {
    $id = $_GET['hapus_id'];

    // Ambil kode_qr
    $result = mysqli_query($conn, "SELECT kode_qr FROM penerima WHERE id = '$id'");
    if ($row = mysqli_fetch_assoc($result)) {
        $kode_qr = $row['kode_qr'];
        $file = "../assets/qrcodes/" . $kode_qr . ".png";

        $delete = mysqli_query($conn, "DELETE FROM penerima WHERE id = '$id'");
        if ($delete) {
            if (file_exists($file)) {
                unlink($file);
            }
            $_SESSION['success_message'] = "Penerima berhasil dihapus!";
        } else {
            $_SESSION['success_message'] = "Gagal menghapus penerima.";
        }
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Admin - QurbanApp</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
    :root {
        --primary-gradient: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
        --accent-color: #ffd700;
        --glass-bg: rgba(255,255,255,0.7);
        --glass-blur: blur(8px);
    }
    body {
        font-family: 'Segoe UI', sans-serif;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        margin: 0;
        overflow-x: hidden;
    }
    .sidebar {
        width: 250px;
        background: var(--primary-gradient);
        height: 100vh;
        position: fixed;
        top: 0;
        left: 0;
        box-shadow: 2px 0 16px #0d6efd11;
        z-index: 1040;
        transition: transform 0.3s cubic-bezier(.4,2,.6,1);
    }
    .sidebar.active {
        transform: translateX(0);
    }
    .sidebar .brand {
        text-align: center;
        padding: 24px 0 18px 0;
        font-size: 1.7rem;
        color: #fff;
        font-weight: bold;
        letter-spacing: 1px;
    }
    .sidebar .menu a {
        display: flex;
        align-items: center;
        padding: 14px 28px;
        text-decoration: none;
        color: #e3e8f0;
        transition: all 0.3s;
        font-size: 1.08rem;
        border-left: 4px solid transparent;
    }
    .sidebar .menu a:hover, .sidebar .menu a.active {
        background: rgba(255,255,255,0.08);
        color: var(--accent-color);
        border-left: 4px solid var(--accent-color);
    }
    .sidebar .menu a i {
        margin-right: 12px;
        font-size: 1.2rem;
    }
    .main-content {
        margin-left: 250px;
        padding: 40px 32px 32px 32px;
        min-height: 100vh;
        transition: margin-left 0.3s cubic-bezier(.4,2,.6,1);
    }
    .dashboard-title {
        font-weight: bold;
        font-size: 2.1rem;
        background: var(--primary-gradient);
        color: #fff;
        padding: 18px 32px;
        border-radius: 18px;
        margin-bottom: 32px;
        box-shadow: 0 4px 18px #0d6efd11;
        letter-spacing: 1px;
    }
    .card {
        border-radius: 18px;
        box-shadow: 0 5px 25px rgba(0,0,0,0.07);
        border: 1.5px solid #e3e8f0;
        background: var(--glass-bg);
        backdrop-filter: var(--glass-blur);
    }
    .table th, .table td {
        text-align: center;
        vertical-align: middle;
    }
    .table th {
        background: var(--primary-gradient) !important;
        color: #fff !important;
        border: none;
        font-weight: 600;
        font-size: 1.08rem;
    }
    .cell-nama {
        max-width: 180px;
        min-width: 120px;
        text-align: left;
    }
    .cell-nama .fw-semibold {
        display: inline-block;
        vertical-align: middle;
        max-width: 120px;
    }
    .table-hover tbody tr {
        transition: background 0.18s, box-shadow 0.18s;
        border-left: 4px solid transparent;
    }
    .table-hover tbody tr:hover {
        background: #eaf2fb;
        box-shadow: 0 2px 12px #0d6efd11;
        border-left: 4px solid var(--accent-color);
    }
    .badge.bg-success, .badge.bg-warning {
        font-size: 1em;
        padding: 0.5em 1.1em;
        border-radius: 1.5em;
        font-weight: 500;
        box-shadow: 0 2px 8px #0d6efd11;
        border: 1.5px solid #e3e8f0;
    }
    .badge.bg-success {
        background: #e3fbe6 !important;
        color: #198754 !important;
        border-color: #b6e388;
    }
    .badge.bg-warning {
        background: #fffbe6 !important;
        color: #b89c00 !important;
        border-color: #ffe066;
    }
    .btn-primary, .btn-success {
        background: var(--primary-gradient);
        border: none;
        transition: background 0.2s, transform 0.2s;
    }
    .btn-primary:hover, .btn-success:hover {
        background: #ffd700;
        color: #0d6efd;
        transform: scale(1.07);
    }
    .btn-danger:hover {
        background: #dc3545cc;
        transform: scale(1.07);
    }
    .modal-dialog {
        max-width: 800px;
        margin-top: 7vh;
        transition: transform 0.25s cubic-bezier(.4,2,.6,1), box-shadow 0.2s;
        transform: translateY(-30px) scale(0.98);
    }
    .modal.fade.show .modal-dialog {
        transform: translateY(0) scale(1.01);
        box-shadow: 0 12px 48px 0 #0d6efd33, 0 2px 8px #0d6efd11;
    }
    .modal-content {
        border-radius: 22px;
        border: 2px solid #e3e8f0;
        background: var(--glass-bg);
        backdrop-filter: var(--glass-blur);
        box-shadow: 0 8px 32px #0d6efd22, 0 2px 8px #0d6efd11;
        animation: modalPop 0.35s cubic-bezier(.4,2,.6,1);
    }
    @keyframes modalPop {
        0% { transform: scale(0.95) translateY(30px); opacity: 0; }
        100% { transform: scale(1) translateY(0); opacity: 1; }
    }
    .modal-header {
        border-bottom: none;
        background: linear-gradient(90deg, #e9f3ff 0%, #f8f9fa 100%);
        border-radius: 22px 22px 0 0;
        box-shadow: 0 2px 8px #0d6efd11;
    }
    .modal-title {
        font-weight: bold;
        color: #0d6efd;
        letter-spacing: 1px;
    }
    .form-label {
        font-weight: 500;
        color: #0a58ca;
    }
    .modal-body .avatar-inisial {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        background: #e9f3ff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        color: #0d6efd;
        box-shadow: 0 2px 8px #0d6efd11;
        font-size: 1.3rem;
        margin-bottom: 10px;
    }
    .modal-footer {
        border-top: none;
        background: #f8f9fa;
        border-radius: 0 0 22px 22px;
    }
    .modal-content input, .modal-content select {
        border-radius: 12px;
        border: 1.5px solid #e3e8f0;
        box-shadow: 0 1px 4px #0d6efd08;
        transition: border 0.2s;
    }
    .modal-content input:focus, .modal-content select:focus {
        border-color: #0d6efd;
        box-shadow: 0 2px 8px #0d6efd22;
    }
    /* Responsive modal */
    @media (max-width: 600px) {
        .modal-dialog {
            max-width: 95vw;
            margin: 1.5rem auto;
        }
        .modal-content {
            padding: 10px;
        }
        .modal-header, .modal-footer {
            padding: 10px;
        }
    }
    /* Responsive sidebar & main-content */
    @media (max-width: 991.98px) {
        .sidebar {
            transform: translateX(-100%);
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            width: 80%;
            max-width: 280px;
            z-index: 1050;
        }
        .main-content {
            margin-left: 0 !important;
            padding: 70px 12px 32px 12px;
        }
        .sidebar-backdrop {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.4);
            z-index: 1049;
            backdrop-filter: blur(2px);
        }
        .sidebar-backdrop.active {
            display: block;
        }
        .mobile-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: var(--primary-gradient);
            color: white;
            padding: 10px 15px;
            z-index: 1030;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .mobile-header .brand {
            font-weight: bold;
            font-size: 1.2rem;
            margin-left: 40px;
        }
        #sidebarToggleBtn {
            position: fixed;
            top: 8px;
            left: 10px;
            z-index: 1051;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            font-size: 1.3rem;
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
        }
    }
    @media (max-width: 767.98px) {
        .table-responsive-custom {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        .table th, .table td {
            white-space: nowrap;
            font-size: 0.85rem;
            padding: 8px 10px;
        }
        .action-buttons-container {
            display: flex;
            flex-direction: column;
            gap: 5px;
            align-items: center;
        }
        .action-buttons-container .btn {
            width: 100%;
            margin: 2px 0;
        }
    }
    </style>
</head>
<body>

<!-- Sidebar Toggle Button (Mobile) -->
<div class="mobile-header d-lg-none">
    <button class="btn" id="sidebarToggleBtn">
        <i class="bi bi-list"></i>
    </button>
    <div class="brand"><i class="bi bi-box-seam-fill"></i> QurbanApp</div>
</div>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

<!-- Sidebar -->
<div class="sidebar" id="sidebarNav">
    <div class="brand d-none d-lg-block"><i class="bi bi-box-seam-fill"></i> QurbanApp</div>
    <div class="menu">
        <a href="dashboard.php" class="active"><i class="bi bi-house-door"></i> Dashboard</a>
        <a href="scan.php"><i class="bi bi-upc-scan"></i> Scan Petugas</a>
        <a href="laporan.php"><i class="bi bi-file-earmark-text"></i> Laporan</a>
        <a href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </div>
</div>

<!-- Main Content -->
<div class="main-content">
    <div class="dashboard-title mb-4 d-flex align-items-center gap-3">
        <i class="bi bi-speedometer2 me-2"></i> Dashboard Petugas
    </div>
    <?php if (isset($success)) { ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php } ?>

    <button class="btn btn-primary mb-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambahPenerima">
        <i class="bi bi-person-plus me-2"></i>Tambah Penerima Daging Qurban
    </button>

    <!-- Form Pencarian & Filter -->
    <form method="get" class="row g-2 align-items-center mb-4 justify-content-center" style="background:rgba(255,255,255,0.85);border-radius:14px;padding:18px 8px 10px 8px;box-shadow:0 2px 12px #0d6efd11;">
        <div class="col-12 col-md-4 mb-2 mb-md-0">
            <label class="form-label mb-1 w-100 text-md-start text-center" style="font-size:1rem;">Cari Nama/No HP</label>
            <input type="text" name="cari" value="<?= isset($_GET['cari']) ? htmlspecialchars($_GET['cari']) : '' ?>" class="form-control text-center text-md-start" placeholder="Cari nama atau no HP...">
        </div>
        <div class="col-12 col-md-3 mb-2 mb-md-0">
            <label class="form-label mb-1 w-100 text-md-start text-center" style="font-size:1rem;">Filter Status</label>
            <select name="status" class="form-select text-center text-md-start">
                <option value="">Semua Status</option>
                <option value="Belum Ambil" <?= (isset($_GET['status']) && $_GET['status']=='Belum Ambil')?'selected':''; ?>>Belum Ambil</option>
                <option value="Sudah Ambil" <?= (isset($_GET['status']) && $_GET['status']=='Sudah Ambil')?'selected':''; ?>>Sudah Ambil</option>
            </select>
        </div>
        <div class="col-6 col-md-2 d-grid mb-2 mb-md-0">
            <label class="form-label mb-1 invisible">Cari</label>
            <button class="btn btn-outline-primary w-100" type="submit"><i class="bi bi-search"></i> Cari</button>
        </div>
        <div class="col-6 col-md-2 d-grid">
            <label class="form-label mb-1 invisible">Reset</label>
            <?php if (isset($_GET['cari']) || isset($_GET['status'])) { ?>
                <a href="dashboard.php" class="btn btn-outline-secondary w-100"><i class="bi bi-x-circle"></i> Reset</a>
            <?php } ?>
        </div>
    </form>
    <!-- Modal Tambah -->
    <div class="modal fade" id="modalTambahPenerima" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" class="modal-content shadow-lg">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-person-plus me-2"></i>Tambah Penerima Daging Qurban</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="d-flex flex-column align-items-center mb-3">
                        <div class="avatar-inisial mb-2">
                            <i class="bi bi-person-fill"></i>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Nama</label>
                            <input type="text" name="nama" class="form-control" required autocomplete="off">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">No HP</label>
                            <input type="text" name="no_hp" class="form-control" required autocomplete="off">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Jumlah Daging</label>
                            <input type="number" name="jumlah_daging" class="form-control" required min="1" autocomplete="off">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="tambah_penerima" class="btn btn-primary px-4"><i class="bi bi-plus-circle me-1"></i>Tambah</button>
                </div>
            </form>
        </div>
    </div>
    <!-- Modal Edit -->
    <div class="modal fade" id="modalEditPenerima" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" id="editForm" class="modal-content shadow-lg">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Edit Penerima</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body row justify-content-center">
                    <div class="col-12 text-center mb-2">
                        <div class="avatar-inisial" id="editAvatarInisial">
                            <i class="bi bi-person-fill"></i>
                        </div>
                    </div>
                    <input type="hidden" name="id" id="editId">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nama</label>
                        <input type="text" name="nama" id="editNama" class="form-control" required autocomplete="off">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">No HP</label>
                        <input type="text" name="no_hp" id="editNoHp" class="form-control" required autocomplete="off">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Jumlah Daging</label>
                        <input type="number" name="jumlah_daging" id="editJumlahDaging" class="form-control" required min="1" autocomplete="off">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Status</label>
                        <select name="status_pengambilan" id="editStatus" class="form-control" required>
                            <option value="Belum Ambil">Belum Ambil</option>
                            <option value="Sudah Ambil">Sudah Ambil</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="update_penerima" class="btn btn-primary px-4"><i class="bi bi-save me-1"></i>Update</button>
                </div>
            </form>
        </div>
    </div>
    <!-- Tabel Penerima -->
    <div class="card shadow-sm mt-4">
        <div class="card-body table-responsive-custom">
            <table class="table table-bordered table-hover table-striped align-middle">
                <thead>
                    <tr>
                        <th class="text-start">Nama</th>
                        <th>No HP</th>
                        <th>QR Code</th>
                        <th>Jumlah Daging</th>
                        <th>Status</th>
                        <th>Download QR</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                // Filter & Search Query
                $where = [];
                if (!empty($_GET['cari'])) {
                    $cari = mysqli_real_escape_string($conn, $_GET['cari']);
                    $where[] = "(nama LIKE '%$cari%' OR no_hp LIKE '%$cari%')";
                }
                if (!empty($_GET['status'])) {
                    $status = mysqli_real_escape_string($conn, $_GET['status']);
                    $where[] = "status_pengambilan = '$status'";
                }
                $where_sql = $where ? 'WHERE '.implode(' AND ', $where) : '';
                $result = mysqli_query($conn, "SELECT * FROM penerima $where_sql");
                while ($row = mysqli_fetch_assoc($result)) {
                    // Format nomor HP dengan tanda strip setiap 4 digit (misal: 0812-3456-7890)
                    $no_hp = preg_replace('/(\d{4})(\d{3,4})(\d{3,4})/', '$1-$2-$3', $row['no_hp']);

                    // Konversi nomor HP ke format internasional (62) untuk WhatsApp
                    $wa_nomor = preg_replace('/[^0-9]/', '', $row['no_hp']);
                    if (substr($wa_nomor, 0, 1) === '0') {
                        $wa_nomor = '62' . substr($wa_nomor, 1);
                    }

                    echo "<tr>";
                    echo "<td class='cell-nama' style='white-space:normal; word-break:break-word;'>
                            <span class='fw-semibold d-inline-block' style='max-width:140px;' title=\"".htmlspecialchars($row['nama'])."\">" . htmlspecialchars($row['nama']) . "</span>
                          </td>";
                    echo "<td><span class='text-primary fw-semibold'><i class='bi bi-telephone me-1'></i>" . htmlspecialchars($no_hp) . "</span></td>";
                    echo "<td><img src='../assets/qrcodes/".$row['kode_qr'].".png' width='70' class='rounded shadow-sm'></td>";
                    echo "<td><span class='badge bg-light text-dark fs-6 p-2'>" . htmlspecialchars($row['jumlah_daging']) . " kg</span></td>";
                    echo "<td><span class='badge ".($row['status_pengambilan']=='Sudah Ambil'?'bg-success':'bg-warning')." d-inline-flex align-items-center'>
                            <i class='bi ".($row['status_pengambilan']=='Sudah Ambil'?'bi-check-circle-fill':'bi-clock-history')." me-2'></i>"
                            .$row['status_pengambilan']."</span></td>";
                    echo "<td><a href='../assets/qrcodes/".$row['kode_qr'].".png' class='btn btn-sm btn-success' download><i class='bi bi-download me-1'></i>Download</a></td>";
                    echo "<td>
                            <div class='d-flex flex-wrap gap-1 justify-content-center'>
                                <a href='#' class='btn btn-sm btn-warning edit-btn' 
                                    data-id='".$row['id']."' 
                                    data-nama='".htmlspecialchars($row['nama'], ENT_QUOTES)."' 
                                    data-no_hp='".htmlspecialchars($row['no_hp'], ENT_QUOTES)."' 
                                    data-jumlah_daging='".htmlspecialchars($row['jumlah_daging'], ENT_QUOTES)."' 
                                    data-status='".$row['status_pengambilan']."' 
                                    data-bs-toggle='modal' data-bs-target='#modalEditPenerima'>
                                    <i class='bi bi-pencil-square me-1'></i>Edit
                                </a>
                                <a href='?hapus_id=".$row['id']."' class='btn btn-sm btn-danger' onclick='return confirm(\"Apakah Anda yakin ingin menghapus penerima ini?\")'>
                                    <i class='bi bi-trash me-1'></i>Hapus
                                </a>
                                <a href='#' class='btn btn-sm btn-info show-qr-btn' 
    data-nama=\"".htmlspecialchars($row['nama'], ENT_QUOTES)."\" 
    data-qr='../assets/qrcodes/".$row['kode_qr'].".png'>
    <i class='bi bi-qr-code me-1'></i>Lihat QR
</a>";
// Format pesan WhatsApp, tetap rapi dan di-urlencode
$wa_text = "*Assalamu'alaikum, " . $row['nama'] . "*\n\n";
$wa_text .= "Berikut adalah informasi pengambilan daging qurban Anda:\n";
$wa_text .= "-----------------------------\n";
$wa_text .= "Nama: " . $row['nama'] . "\n";
$wa_text .= "No HP: " . $row['no_hp'] . "\n";
$wa_text .= "Jumlah Daging: " . $row['jumlah_daging'] . " kg\n";
$wa_text .= "Status: " . $row['status_pengambilan'] . "\n";
$wa_text .= "-----------------------------\n";
$wa_text .= "Silakan tunjukkan QR Code berikut saat pengambilan:\n";
$wa_text .= "https://bangzums.my.id/assets/qrcodes/".$row['kode_qr'].".png\n\n";
$wa_text .= "Terima kasih. Semoga berkah!";
$wa_text_encoded = rawurlencode($wa_text);
echo "<a href='https://wa.me/".$wa_nomor."?text=".$wa_text_encoded."' target='_blank' class='btn btn-sm btn-success'>
    <i class='bi bi-whatsapp me-1'></i>Kirim Pesan
</a>";
                            echo "</div>
                          </td>";
                    echo "</tr>";
                }
                ?>
                </tbody>
            </table>
        </div>
    </div>
    <!-- Modal Lihat QR -->
    <div class="modal fade" id="modalLihatQR" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg">
          <div class="modal-header">
            <h5 class="modal-title"><i class="bi bi-qr-code me-2"></i>QR Code Penerima</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body text-center">
            <div id="lihatQRNama" class="fw-bold mb-2"></div>
            <img id="lihatQRImg" src="" alt="QR Code" style="max-width:220px;" class="mb-2 rounded shadow">
            <div class="text-muted" style="font-size:0.95em;">Screenshot QR ini lalu kirim manual ke WhatsApp penerima.</div>
          </div>
        </div>
      </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Sidebar toggle for mobile
    const sidebar = document.getElementById('sidebarNav');
    const sidebarToggleBtn = document.getElementById('sidebarToggleBtn');
    const sidebarBackdrop = document.getElementById('sidebarBackdrop');
    function openSidebar() {
        sidebar.classList.add('active');
        sidebarBackdrop.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    function closeSidebar() {
        sidebar.classList.remove('active');
        sidebarBackdrop.classList.remove('active');
        document.body.style.overflow = '';
    }
    sidebarToggleBtn.addEventListener('click', openSidebar);
    sidebarBackdrop.addEventListener('click', closeSidebar);
    // Close sidebar on link click (mobile)
    document.querySelectorAll('.sidebar .menu a').forEach(link => {
        link.addEventListener('click', () => {
            if (window.innerWidth < 992) closeSidebar();
        });
    });

    document.querySelectorAll('.edit-btn').forEach(button => {
        button.addEventListener('click', (e) => {
            document.getElementById('editId').value = button.getAttribute('data-id');
            document.getElementById('editNama').value = button.getAttribute('data-nama');
            document.getElementById('editNoHp').value = button.getAttribute('data-no_hp');
            document.getElementById('editJumlahDaging').value = button.getAttribute('data-jumlah_daging');
            document.getElementById('editStatus').value = button.getAttribute('data-status');
            // Tampilkan inisial avatar pada modal edit
            const nama = button.getAttribute('data-nama') || '';
            const inisial = nama.trim().length > 0 ? nama.trim().charAt(0).toUpperCase() : '';
            document.getElementById('editAvatarInisial').innerHTML = inisial ? inisial : '<i class="bi bi-person-fill"></i>';
        });
    });

    // Script untuk tombol lihat QR
    document.querySelectorAll('.show-qr-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('lihatQRNama').innerText = btn.getAttribute('data-nama');
            document.getElementById('lihatQRImg').src = btn.getAttribute('data-qr');
            var qrModal = new bootstrap.Modal(document.getElementById('modalLihatQR'));
            qrModal.show();
        });
    });
</script>

</body>
</html>

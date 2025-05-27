<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

$message = '';
if (isset($_GET['kode'])) {
    $kode = $_GET['kode'];
    $query = mysqli_query($conn, "SELECT * FROM penerima WHERE kode_qr = '$kode'");
    $data = mysqli_fetch_assoc($query);

    if ($data) {
        if ($data['status_pengambilan'] == 'Sudah Ambil') {
            $message = "<div class='alert alert-danger'>QR sudah digunakan!</div>";
        } else {
            // Update status dan waktu_pengambilan
            mysqli_query($conn, "UPDATE penerima SET status_pengambilan='Sudah Ambil', waktu_pengambilan=NOW() WHERE kode_qr='$kode'");
            $message = "<div class='alert alert-success'>Berhasil, daging diberikan ke: <b>" . $data['nama'] . "</b></div>";
        }
    } else {
        $message = "<div class='alert alert-danger'>QR tidak valid!</div>";
    }
}

// Statistik dan Riwayat Scan Hari Ini
$jumlah_sudah = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM penerima WHERE status_pengambilan='Sudah Ambil'"))['total'];
$jumlah_belum = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM penerima WHERE status_pengambilan!='Sudah Ambil'"))['total'];
$tanggal_hari_ini = date('Y-m-d');
$riwayat_query = mysqli_query($conn, "SELECT * FROM penerima WHERE status_pengambilan='Sudah Ambil' AND DATE(waktu_pengambilan)='$tanggal_hari_ini' ORDER BY waktu_pengambilan DESC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Scan QR Petugas - QurbanApp</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <!-- QR Code Scanner -->
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
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
        .main-content {
            margin-left: 250px;
            padding: 40px 32px 32px 32px;
            min-height: 100vh;
            transition: margin-left 0.3s cubic-bezier(.4,2,.6,1);
        }
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
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebarNav">
        <div class="brand">
            <i class="bi bi-box-seam-fill"></i> QurbanApp
        </div>
        <div class="menu">
            <a href="dashboard.php"><i class="bi bi-house-door"></i> Dashboard</a>
            <a href="scan.php" class="active"><i class="bi bi-upc-scan"></i> Scan Petugas</a>
            <a href="laporan.php"><i class="bi bi-file-earmark-text"></i> Laporan</a>
            <a href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </div>
    </div>

    <!-- Backdrop for mobile sidebar -->
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    <!-- Mobile header -->
    <div class="mobile-header d-lg-none">
        <button class="btn" id="sidebarToggleBtn">
            <i class="bi bi-list"></i>
        </button>
        <div class="brand"><i class="bi bi-box-seam-fill"></i> QurbanApp</div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="dashboard-title mb-4 d-flex align-items-center gap-3">
            <i class="bi bi-upc-scan me-2"></i> Scan QR Penerima
        </div>

        <?php if (!empty($message)) echo $message; ?>

        <div class="row mb-4" style="max-width:100%;">
            <!-- Statistik Pengambilan -->
            <div class="col-md-6 mb-4 mb-md-0" style="max-width:500px;">
                <div class="row">
                    <div class="col">
                        <div class="card p-3 text-center">
                            <div class="fw-bold" style="font-size:1.2rem;">Sudah Ambil</div>
                            <div class="display-6 text-success"><?php echo $jumlah_sudah; ?></div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="card p-3 text-center">
                            <div class="fw-bold" style="font-size:1.2rem;">Belum Ambil</div>
                            <div class="display-6 text-danger"><?php echo $jumlah_belum; ?></div>
                        </div>
                    </div>
                </div>

                <!-- Form Scan & Tombol -->
                <div class="card p-4 mt-4">
                    <form method="get" class="mb-3" id="scanForm">
                        <div class="input-group">
                            <input type="text" name="kode" id="kodeInput" class="form-control form-control-lg" placeholder="Masukkan Kode QR" required>
                            <button type="submit" class="btn btn-primary px-4">Proses</button>
                        </div>
                    </form>
                    <div class="mb-3">
                        <button class="btn btn-success me-2" onclick="startScanner()" id="startBtn">
                            <i class="bi bi-camera me-1"></i>Mulai Scan QR
                        </button>
                        <button class="btn btn-danger" onclick="stopScanner()" id="stopBtn" style="display:none;">
                            <i class="bi bi-x-circle me-1"></i>Berhenti Scan
                        </button>
                    </div>
                    <div id="reader"></div>
                </div>
            </div>

            <!-- Riwayat Scan Hari Ini -->
            <div class="col-md-6" style="max-width:700px;">
                <div class="card p-4 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="fw-bold" style="font-size:1.1rem;">
                            <i class="bi bi-clock-history me-1"></i> Riwayat Pengambilan Hari Ini
                        </div>
                        <button class="btn btn-outline-secondary btn-sm" onclick="location.reload()">
                            <i class="bi bi-arrow-clockwise"></i> Refresh
                        </button>
                    </div>
                    <div style="max-height:350px; overflow-y:auto;">
                        <table class="table table-sm table-bordered align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>No</th>
                                    <th>Nama</th>
                                    <th>Waktu Ambil</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $no = 1;
                                if (mysqli_num_rows($riwayat_query) > 0) {
                                    while ($row = mysqli_fetch_assoc($riwayat_query)) {
                                        echo "<tr>
                                            <td>{$no}</td>
                                            <td>{$row['nama']}</td>
                                            <td>".date('H:i:s', strtotime($row['waktu_pengambilan']))."</td>
                                        </tr>";
                                        $no++;
                                    }
                                } else {
                                    echo "<tr><td colspan='3' class='text-center text-muted'>Belum ada pengambilan hari ini.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let scanner;
        let scannerIsRunning = false;

        function startScanner() {
            if (scannerIsRunning) return;

            const readerElem = document.getElementById('reader');
            readerElem.style.display = 'block';
            document.getElementById('startBtn').style.display = 'none';
            document.getElementById('stopBtn').style.display = 'inline-block';

            scanner = new Html5Qrcode("reader");

            scanner.start(
                { facingMode: "environment" },
                { fps: 10, qrbox: 250 },
                (decodedText) => {
                    document.getElementById("kodeInput").value = decodedText;
                    document.getElementById("scanForm").submit();
                    stopScanner();
                },
                (errorMessage) => {
                    // console.warn(errorMessage);
                }
            ).then(() => {
                scannerIsRunning = true;
            }).catch(err => {
                alert("Gagal mengakses kamera: " + err);
            });
        }

        function stopScanner() {
            if (!scanner || !scannerIsRunning) return;

            scanner.stop().then(() => {
                scanner.clear();
                scannerIsRunning = false;
                document.getElementById('reader').style.display = 'none';
                document.getElementById('startBtn').style.display = 'inline-block';
                document.getElementById('stopBtn').style.display = 'none';
            }).catch(err => {
                console.error("Gagal menghentikan scanner:", err);
            });
        }

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
    </script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

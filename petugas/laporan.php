<?php
session_start();
include '../config/db.php';

// Cek apakah admin sudah login
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

// Ambil data statistik
$totalPenerima = mysqli_query($conn, "SELECT COUNT(*) AS total FROM penerima");
$dataTotalPenerima = mysqli_fetch_assoc($totalPenerima);

$belumAmbil = mysqli_query($conn, "SELECT COUNT(*) AS belum FROM penerima WHERE status_pengambilan = 'Belum Ambil'");
$dataBelumAmbil = mysqli_fetch_assoc($belumAmbil);

$sudahAmbil = mysqli_query($conn, "SELECT COUNT(*) AS sudah FROM penerima WHERE status_pengambilan = 'Sudah Ambil'");
$dataSudahAmbil = mysqli_fetch_assoc($sudahAmbil);

$totalDaging = mysqli_query($conn, "SELECT SUM(jumlah_daging) AS total_daging FROM penerima");
$dataTotalDaging = mysqli_fetch_assoc($totalDaging);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan - QurbanApp</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
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
        .sidebar .menu a i { margin-right: 12px; font-size: 1.2rem;}
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
        .stat-card {
            border-radius: 18px;
            box-shadow: 0 5px 25px rgba(0,0,0,0.07);
            border: 1.5px solid #e3e8f0;
            background: var(--glass-bg);
            backdrop-filter: var(--glass-blur);
            color: #0d6efd;
            font-weight: 600;
            text-align: center;
            padding: 1.5rem 1rem;
            margin-bottom: 1.5rem;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .stat-card .icon {
            font-size: 2.2rem;
            margin-bottom: 0.5rem;
            color: var(--accent-color);
        }
        .stat-card .stat-value {
            font-size: 2.1rem;
            font-weight: bold;
            color: #0d6efd;
        }
        .stat-card .stat-label {
            font-size: 1.1rem;
            color: #0a58ca;
        }
        .stat-card.bg-success .stat-value { color: #198754; }
        .stat-card.bg-warning .stat-value { color: #b89c00; }
        .stat-card.bg-primary .stat-value { color: #0d6efd; }
        .stat-card.bg-info .stat-value { color: #0dcaf0; }
        .card {
            border-radius: 18px;
            box-shadow: 0 5px 25px rgba(0,0,0,0.07);
            border: 1.5px solid #e3e8f0;
            background: var(--glass-bg);
            backdrop-filter: var(--glass-blur);
        }
        .table th, .table td { text-align: center; vertical-align: middle;}
        .table th {
            background: var(--primary-gradient) !important;
            color: #fff !important;
            border: none;
            font-weight: 600;
            font-size: 1.08rem;
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
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar" id="sidebarNav">
    <div class="brand"><i class="bi bi-box-seam-fill"></i> QurbanApp</div>
    <div class="menu">
        <a href="dashboard.php"><i class="bi bi-house-door"></i> Dashboard</a>
        <a href="scan.php"><i class="bi bi-upc-scan"></i> Scan Petugas</a>
        <a href="laporan.php" class="active"><i class="bi bi-file-earmark-text"></i> Laporan</a>
        <a href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </div>
</div>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

<!-- Main Content -->
<div class="main-content">
    <div class="dashboard-title mb-4 d-flex align-items-center gap-3">
        <i class="bi bi-bar-chart-fill me-2"></i> Laporan Penerima Daging Qurban
    </div>

    <!-- Grafik & Kartu Statistik dalam Satu Baris -->
    <div class="mb-4">
        <div class="d-flex flex-row flex-wrap align-items-stretch gap-3">
            <!-- Grafik Statistik -->
            <div class="card shadow-sm p-2 d-flex flex-column justify-content-center align-items-center" style="width:180px;min-width:150px;max-width:200px;border-radius:14px;">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width:28px;height:28px;">
                        <i class="bi bi-pie-chart-fill"></i>
                    </span>
                    <span class="fw-semibold" style="font-size:1.05rem;">Statistik</span>
                </div>
                <canvas id="statistikChart" style="width:90px;max-width:100%;height:90px;max-height:28vw;"></canvas>
            </div>
            <!-- Kartu Statistik -->
            <div class="d-flex flex-row flex-grow-1 gap-3">
                <div class="stat-card bg-light text-dark d-flex align-items-center flex-fill m-0" style="min-width:120px;">
                    <div class="icon bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                        <i class="bi bi-people-fill fs-5"></i>
                    </div>
                    <div class="ms-2 text-start">
                        <div class="stat-value fs-5 fw-bold"><?= $dataTotalPenerima['total'] ?></div>
                        <div class="stat-label text-muted">Total Penerima</div>
                    </div>
                </div>
                <div class="stat-card bg-light text-dark d-flex align-items-center flex-fill m-0" style="min-width:120px;">
                    <div class="icon bg-warning text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                        <i class="bi bi-clock-history fs-5"></i>
                    </div>
                    <div class="ms-2 text-start">
                        <div class="stat-value fs-5 fw-bold"><?= $dataBelumAmbil['belum'] ?></div>
                        <div class="stat-label text-muted">Belum Ambil</div>
                    </div>
                </div>
                <div class="stat-card bg-light text-dark d-flex align-items-center flex-fill m-0" style="min-width:120px;">
                    <div class="icon bg-success text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                        <i class="bi bi-check-circle-fill fs-5"></i>
                    </div>
                    <div class="ms-2 text-start">
                        <div class="stat-value fs-5 fw-bold"><?= $dataSudahAmbil['sudah'] ?></div>
                        <div class="stat-label text-muted">Sudah Ambil</div>
                    </div>
                </div>
                <div class="stat-card bg-light text-dark d-flex align-items-center flex-fill m-0" style="min-width:120px;">
                    <div class="icon bg-info text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                        <i class="bi bi-box2-heart fs-5"></i>
                    </div>
                    <div class="ms-2 text-start">
                        <div class="stat-value fs-5 fw-bold"><?= $dataTotalDaging['total_daging'] ?> Kg</div>
                        <div class="stat-label text-muted">Total Daging</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Grafik & Kartu Statistik dalam Satu Baris -->

    <!-- Tabel Laporan -->
    <h3 class="mt-4 mb-3">Daftar Penerima yang Sudah Mengambil Daging</h3>
    <div class="card shadow-sm">
        <div class="card-body">
            <table class="table table-bordered table-hover table-striped align-middle">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>No HP</th>
                        <th>Jumlah Daging</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Ambil data penerima yang sudah mengambil daging
                    $query = mysqli_query($conn, "SELECT * FROM penerima WHERE status_pengambilan = 'Sudah Ambil'");
                    while ($row = mysqli_fetch_assoc($query)) {
                        $masked_phone = substr($row['no_hp'], 0, 4) . '****' . substr($row['no_hp'], -3);
                    ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="avatar-inisial" style="width:36px;height:36px;border-radius:50%;background:#e9f3ff;display:flex;align-items:center;justify-content:center;font-weight:700;color:#0d6efd;box-shadow:0 2px 8px #0d6efd11;">
                                        <?= mb_strtoupper(mb_substr($row['nama'], 0, 1)) ?>
                                    </div>
                                    <span class="fw-semibold"><?= htmlspecialchars($row['nama']) ?></span>
                                </div>
                            </td>
                            <td><span class="text-primary fw-semibold"><i class="bi bi-telephone me-1"></i><?= htmlspecialchars($masked_phone) ?></span></td>
                            <td><span class="badge bg-light text-dark fs-6 p-2"><i class="bi bi-box2-heart me-1 text-danger"></i><?= htmlspecialchars($row['jumlah_daging']) ?> kg</span></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mobile-header d-lg-none">
    <button class="btn" id="sidebarToggleBtn">
        <i class="bi bi-list"></i>
    </button>
    <div class="brand"><i class="bi bi-box-seam-fill"></i> QurbanApp</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Data dari PHP
    const total = <?= (int)$dataTotalPenerima['total'] ?>;
    const sudah = <?= (int)$dataSudahAmbil['sudah'] ?>;
    const belum = <?= (int)$dataBelumAmbil['belum'] ?>;

    const ctx = document.getElementById('statistikChart').getContext('2d');
    new Chart(ctx, {
        type: 'pie',
        data: {
            labels: ['Sudah Ambil', 'Belum Ambil'],
            datasets: [{
                data: [sudah, belum],
                backgroundColor: [
                    'rgba(25, 135, 84, 0.8)',   // green
                    'rgba(255, 193, 7, 0.8)'    // yellow
                ],
                borderColor: [
                    'rgba(25, 135, 84, 1)',
                    'rgba(255, 193, 7, 1)'
                ],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            let value = context.parsed || 0;
                            let percent = total > 0 ? (value/total*100).toFixed(1) : 0;
                            return `${label}: ${value} (${percent}%)`;
                        }
                    }
                }
            }
        }
    });

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

</body>
</html>

<?php
session_start();
include './config/db.php';

// Inisialisasi pencarian dengan validasi input
$search = isset($_GET['search']) ? htmlspecialchars(trim($_GET['search'])) : '';

// Query data penerima dengan prepared statement untuk mencegah SQL Injection
if ($search !== '') {
    $stmt = $conn->prepare("SELECT nama, no_hp, jumlah_daging, status_pengambilan FROM penerima WHERE nama LIKE ?");
    $like = "%$search%";
    $stmt->bind_param("s", $like);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $sql = "SELECT nama, no_hp, jumlah_daging, status_pengambilan FROM penerima";
    $result = $conn->query($sql);
}

// Pastikan koneksi database ditutup setelah selesai
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QurbanApp - Sistem Manajemen Qurban Modern</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="https://cdn.jsdelivr.net/gh/twitter/twemoji@14.0.2/assets/72x72/1f404.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
            --accent-color: #ffd700;
            --glass-bg: rgba(255,255,255,0.7);
            --glass-blur: blur(8px);
        }

        body {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .navbar {
            background: var(--primary-gradient);
            box-shadow: 0 4px 15px rgba(13, 110, 253, 0.2);
            transition: box-shadow 0.3s;
        }
        .navbar:hover {
            box-shadow: 0 8px 24px rgba(13, 110, 253, 0.25);
        }

        .navbar .nav-link {
            transition: color 0.3s, transform 0.3s;
        }
        .navbar .nav-link:hover {
            color: var(--accent-color);
            transform: scale(1.1);
        }

        .hero-section {
            background: var(--primary-gradient);
            color: white;
            clip-path: polygon(0 0, 100% 0, 100% 90%, 0 100%);
            padding-bottom: 8rem;
            position: relative;
            overflow: hidden;
            animation: fadeInHero 1.2s;
        }
        @keyframes fadeInHero {
            from { opacity: 0; transform: scale(1.04);}
            to { opacity: 1; transform: scale(1);}
        }

        .hero-section::after {
            content: "";
            position: absolute;
            left: 50%;
            top: 60%;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(255,215,0,0.12) 0%, transparent 80%);
            transform: translate(-50%, -50%);
            z-index: 0;
        }

        .hero-section .container {
            position: relative;
            z-index: 1;
        }

        .hero-svg {
            position: absolute;
            right: 0;
            bottom: 0;
            width: 340px;
            z-index: 0;
            opacity: 0.25;
            animation: floatSvg 4s ease-in-out infinite alternate;
        }
        @keyframes floatSvg {
            from { transform: translateY(0);}
            to { transform: translateY(-18px);}
        }

        .features-section {
            margin-top: -5rem;
            z-index: 2;
            position: relative;
        }
        .feature-card {
            background: var(--glass-bg);
            backdrop-filter: var(--glass-blur);
            border-radius: 18px;
            box-shadow: 0 8px 32px rgba(13,110,253,0.08);
            border: 1.5px solid #e3e8f0;
            transition: transform 0.25s, box-shadow 0.25s, border-color 0.25s;
            cursor: pointer;
        }
        .feature-card:hover {
            transform: translateY(-8px) scale(1.04) rotate(-1deg);
            box-shadow: 0 16px 32px rgba(13,110,253,0.13);
            border-color: var(--accent-color);
        }
        .feature-icon {
            font-size: 2.5rem;
            color: var(--accent-color);
            background: #fff;
            border-radius: 50%;
            padding: 0.7rem;
            box-shadow: 0 2px 8px #ffd70022;
            margin-bottom: 1rem;
            transition: box-shadow 0.3s, transform 0.3s;
        }
        .feature-card:hover .feature-icon {
            box-shadow: 0 4px 16px #ffd70055;
            transform: scale(1.1) rotate(-8deg);
        }

        .search-box {
            max-width: 800px;
            margin: -4rem auto 2rem;
            box-shadow: 0 5px 25px rgba(0,0,0,0.1);
            border-radius: 50px;
            overflow: hidden;
            background: var(--glass-bg);
            backdrop-filter: var(--glass-blur);
            border: 2px solid #e3e8f0;
            animation: fadeInDown 1s;
        }
        .search-box input:focus {
            box-shadow: 0 0 0 0.2rem #ffd70044;
        }
        .search-box .btn-primary {
            background: var(--primary-gradient);
            border: none;
            transition: background 0.2s, transform 0.2s;
        }
        .search-box .btn-primary:hover {
            background: #ffd700;
            color: #0d6efd;
            transform: scale(1.07);
        }
        .search-box .btn-danger {
            transition: background 0.2s, transform 0.2s;
        }
        .search-box .btn-danger:hover {
            background: #dc3545cc;
            transform: scale(1.07);
        }

        .data-table {
            border: 2px solid transparent;
            background-clip: padding-box;
            border-radius: 18px;
            background-origin: border-box;
            box-shadow: 0 5px 25px rgba(0,0,0,0.05);
            position: relative;
            overflow: hidden;
        }
        .data-table:before {
            content: "";
            position: absolute;
            inset: 0;
            z-index: 0;
            border-radius: 18px;
            padding: 2px;
            background: linear-gradient(120deg, #e3e8f0 0%, #e3e8f0 100%);
            /* warna border luar jadi abu-abu muda */
            -webkit-mask:
                linear-gradient(#fff 0 0) content-box, 
                linear-gradient(#fff 0 0);
            -webkit-mask-composite: xor;
            mask-composite: exclude;
            pointer-events: none;
        }
        .data-table > * {
            position: relative;
            z-index: 1;
        }
        .table-hover tbody tr {
            transition: background 0.18s, box-shadow 0.18s;
            border-left: 4px solid transparent;
            animation: fadeInUp 0.7s;
        }
        .table-hover tbody tr:hover {
            background: #eaf2fb;
            box-shadow: 0 2px 12px #0d6efd11;
            border-left: 4px solid transparent;
        }
        .symbol-label {
            transition: background 0.2s, transform 0.2s;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            background: #e9f3ff;
            color: #0d6efd;
            font-weight: bold;
            box-shadow: 0 2px 8px #0d6efd11;
        }
        .table-hover tbody tr:hover .symbol-label {
            background: #d6eaff;
            transform: scale(1.1);
        }
        .status-badge {
            padding: 0.5em 1.1em;
            border-radius: 1.5em;
            font-weight: 500;
            font-size: 1.05em;
            box-shadow: 0 2px 8px #0d6efd11;
            transition: background 0.2s, color 0.2s, box-shadow 0.2s;
            border: 1.5px solid #e3e8f0;
        }
        .bg-success.status-badge {
            background: #e3fbe6 !important;
            color: #198754 !important;
            border-color: #b6e388;
        }
        .bg-warning.status-badge {
            background: #fffbe6 !important;
            color: #b89c00 !important;
            border-color: #ffe066;
        }
        .badge.bg-light {
            background: #f8fafc !important;
            color: #0d6efd !important;
            font-weight: 500;
            border: 1px solid #e3e8f0;
            box-shadow: 0 1px 4px #0d6efd11;
            padding: 0.6em 1.2em;
        }
        .table-primary th {
            background: #0d6efd !important;
            color: #fff !important;
            border: none;
        }
        /* Animasi masuk tabel */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(40px);}
            to { opacity: 1; transform: translateY(0);}
        }
        .avatar-inisial {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e9f3ff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            font-weight: 700;
            color: #0d6efd;
            margin-right: 12px;
            box-shadow: 0 2px 8px #0d6efd11;
        }

        footer {
            background: var(--primary-gradient);
            color: white;
            text-align: center;
            padding: 1rem 0;
            box-shadow: 0 -4px 15px rgba(0, 0, 0, 0.1);
        }
        footer a {
            color: var(--accent-color);
            text-decoration: none;
            transition: color 0.3s;
        }
        footer a:hover {
            color: #fff;
        }

        /* Add floating effect for "Tentang Kami" */
        .nav-link-floating {
            position: relative;
            transition: transform 0.3s, color 0.3s;
        }
        .nav-link-floating:hover {
            transform: translateY(-3px);
            color: var(--accent-color) !important;
        }
        .nav-link-floating::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: -2px;
            width: 100%;
            height: 2px;
            background: var(--accent-color);
            transform: scaleX(0);
            transform-origin: right;
            transition: transform 0.3s;
        }
        .nav-link-floating:hover::after {
            transform: scaleX(1);
            transform-origin: left;
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark sticky-top">
  <div class="container">
    <a class="navbar-brand fw-bold d-flex align-items-center" href="#">
        <i class="bi bi-shop-window me-2"></i>
        QurbanApp
    </a>
    <!-- Add Toggler Button -->
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto align-items-center">
        <li class="nav-item">
          <a class="nav-link active" href="#home">Beranda</a>
        </li>
        <!-- "Tentang Kami" link -->
        <li class="nav-item">
          <a class="nav-link nav-link-floating" href="#" data-bs-toggle="modal" data-bs-target="#aboutModal">Tentang Kami</a>
        </li>
        <li class="nav-item">
          <a href="petugas/login.php" class="btn btn-outline-light ms-3">
            <i class="bi bi-box-arrow-in-right me-2"></i>Login Petugas
          </a>
        </li>
      </ul>
    </div>
  </div>
</nav>

<!-- Hero Section -->
<section class="hero-section position-relative">
  <div class="container text-center py-5">
    <div class="row justify-content-center">
      <div class="col-lg-8">
        <h1 class="display-4 fw-bold mb-4">
          Revolusi Digital dalam <span class="text-warning">Pengelolaan Qurban</span>
        </h1>
        <p class="lead mb-5 opacity-75">
          Sistem terintegrasi dengan teknologi QR Code untuk manajemen distribusi daging qurban yang efisien dan transparan
        </p>
      </div>
    </div>
  </div>
  <!-- SVG Illustration -->
  <svg class="hero-svg" viewBox="0 0 400 400" fill="none">
    <circle cx="200" cy="200" r="180" fill="#ffd700" fill-opacity="0.13"/>
    <ellipse cx="300" cy="320" rx="80" ry="30" fill="#fff" fill-opacity="0.18"/>
    <circle cx="320" cy="90" r="30" fill="#fff" fill-opacity="0.12"/>
    <rect x="60" y="60" width="60" height="60" rx="18" fill="#0d6efd" fill-opacity="0.13"/>
  </svg>
</section>

<!-- Features Section -->
<section class="features-section container mb-5">
  <div class="row g-4 justify-content-center">
    <div class="col-md-4">
      <div class="feature-card p-4 text-center h-100 shadow-sm">
        <div class="feature-icon mx-auto mb-2">
          <i class="bi bi-qr-code-scan"></i>
        </div>
        <h5 class="fw-bold mb-2">QR Code Otomatis</h5>
        <p class="mb-0">Setiap penerima mendapat QR unik untuk pengambilan daging yang cepat dan akurat.</p>
      </div>
    </div>
    <div class="col-md-4">
      <div class="feature-card p-4 text-center h-100 shadow-sm">
        <div class="feature-icon mx-auto mb-2">
          <i class="bi bi-shield-check"></i>
        </div>
        <h5 class="fw-bold mb-2">Transparan & Aman</h5>
        <p class="mb-0">Distribusi tercatat otomatis, meminimalisir human error dan meningkatkan kepercayaan.</p>
      </div>
    </div>
    <div class="col-md-4">
      <div class="feature-card p-4 text-center h-100 shadow-sm">
        <div class="feature-icon mx-auto mb-2">
          <i class="bi bi-people"></i>
        </div>
        <h5 class="fw-bold mb-2">Mudah Digunakan</h5>
        <p class="mb-0">Antarmuka ramah pengguna, cocok untuk panitia dan penerima dari berbagai usia.</p>
      </div>
    </div>
  </div>
</section>

<!-- Penerima Section -->
<section id="penerima" class="py-5 bg-light">
  <div class="container">
    <!-- Search Box -->
    <div class="search-box">
      <form method="get" class="input-group">
        <input 
          type="text" 
          name="search" 
          class="form-control form-control-lg border-0 ps-4" 
          placeholder="Cari penerima..." 
          value="<?= htmlspecialchars($search) ?>"
        >
        <button class="btn btn-primary px-4" type="submit">
          <i class="bi bi-search me-2"></i>Cari
        </button>
        <?php if ($search !== ''): ?>
          <a href="?" class="btn btn-danger px-4">
            <i class="bi bi-x-circle me-2"></i>Reset
          </a>
        <?php endif; ?>
      </form>
    </div>

    <!-- Data Table -->
    <div class="data-table bg-white table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-primary">
          <tr>
            <th class="ps-4">#</th>
            <th>Nama Penerima</th>
            <th>Kontak</th>
            <th>Jumlah</th>
            <th class="pe-4">Status</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($result && $result->num_rows > 0): ?>
            <?php $no = 1; ?>
            <?php while($row = $result->fetch_assoc()): ?>
              <?php 
                $no_hp = $row['no_hp'];
                $masked_phone = substr($no_hp, 0, 4) . '****' . substr($no_hp, -3);
                $nama = htmlspecialchars($row['nama']);
                $initial = mb_strtoupper(mb_substr($row['nama'], 0, 1));
              ?>
              <tr>
                <td class="ps-4 fw-bold"><?= $no++ ?></td>
                <td>
                  <div class="d-flex align-items-center">
                    <!-- <div class="avatar-inisial"><?= $initial ?></div> -->
                    <span class="fw-semibold fs-6"><?= $nama ?></span>
                  </div>
                </td>
                <td>
                  <span class="text-primary fw-semibold">
                    <i class="bi bi-telephone me-1"></i><?= htmlspecialchars($masked_phone) ?>
                  </span>
                </td>
                <td>
                  <span class="badge bg-light text-dark fs-6 p-2">
                    <?= htmlspecialchars($row['jumlah_daging']) ?> kg
                  </span>
                </td>
                <td class="pe-4">
                  <span class="status-badge d-inline-flex align-items-center <?= $row['status_pengambilan'] === 'Sudah Ambil' ? 'bg-success' : 'bg-warning' ?>">
                    <i class="bi <?= $row['status_pengambilan'] === 'Sudah Ambil' ? 'bi-check-circle-fill' : 'bi-clock-history' ?> me-2"></i>
                    <?= $row['status_pengambilan'] ?>
                  </span>
                </td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="5" class="text-center py-5">
                <div class="d-flex flex-column align-items-center opacity-50">
                  <i class="bi bi-database-x fs-1"></i>
                  <p class="mt-3">Data tidak ditemukan</p>
                </div>
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
    <!-- End Data Table -->
  </div>
</section>

<!-- Footer -->
<footer class="bg-dark text-white py-3">
  <div class="container text-center small">
    &copy; <?= date('Y') ?> <a href="#">QurbanApp</a>. All rights reserved.
  </div>
</footer>

<!-- Modal About Us -->
<div class="modal fade" id="aboutModal" tabindex="-1" aria-labelledby="aboutModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="aboutModalLabel">Tentang Kami</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center">
        <p class="fs-6 text-muted">
          QurbanApp membantu distribusi daging qurban secara efisien dengan teknologi modern dan transparansi tinggi.
        </p>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>

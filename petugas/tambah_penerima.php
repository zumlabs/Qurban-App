<?php
include '../config/db.php';
require '../vendor/phpqrcode/qrlib.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Tambah Penerima</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container py-5">
    <h1 class="mb-4">Tambah Penerima Qurban</h1>

    <form method="post" class="card p-4 shadow">
        <div class="mb-3">
            <label>Nama</label>
            <input type="text" name="nama" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>No HP</label>
            <input type="text" name="no_hp" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Jumlah Daging</label>
            <input type="number" name="jumlah_daging" class="form-control" required>
        </div>
        <button type="submit" name="tambah_penerima" class="btn btn-primary">Tambah Penerima</button>
    </form>

    <?php
    if (isset($_POST['tambah_penerima'])) {
        $nama = $_POST['nama'];
        $no_hp = $_POST['no_hp'];
        $jumlah_daging = $_POST['jumlah_daging'];
        $kode_qr = uniqid();

        $insert = mysqli_query($conn, "INSERT INTO penerima (nama, no_hp, kode_qr, jumlah_daging) VALUES ('$nama', '$no_hp', '$kode_qr', '$jumlah_daging')");
        if ($insert) {
            $path = "../assets/qrcodes/";
            $file = $path . $kode_qr . ".png";
            QRcode::png($kode_qr, $file, 'L', 10, 2);
            echo "<div class='alert alert-success mt-4'>Penerima berhasil ditambahkan!</div>";
        } else {
            echo "<div class='alert alert-danger mt-4'>Gagal menambahkan data.</div>";
        }
    }
    ?>

</body>
</html>

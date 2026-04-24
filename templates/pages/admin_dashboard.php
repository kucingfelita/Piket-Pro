<?php
$pageTitle = "Admin Dashboard";
// Cek jika pengguna tidak login atau bukan admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    $_SESSION['error_message'] = "Anda harus login sebagai admin untuk mengakses halaman ini.";
    header('Location: index.php?page=login');
    exit;
}
?>

<div class="card">
    <div class="card-header">
        <h3>Dashboard Admin</h3>
    </div>
    <div class="card-body">
        <h5 class="card-title">Selamat Datang, <?php echo htmlspecialchars($_SESSION['user_full_name']); ?>!</h5>
        <p class="card-text">Pilih salah satu menu di bawah untuk mengelola aplikasi.</p>
        <hr>
        <a href="index.php?page=jadwal" class="btn btn-primary">Lihat Jadwal Piket</a>
        <a href="index.php?page=atur_jadwal" class="btn btn-outline-primary">Atur Jadwal</a>
        <a href="index.php?page=konfirmasi_absensi" class="btn btn-success">Konfirmasi Absensi</a>
        <a href="index.php?page=konfirmasi_laporan" class="btn btn-outline-primary">Konfirmasi Laporan Akhir</a>
        <a href="index.php?page=laporan_bulanan" class="btn btn-info">Laporan Bulanan</a>
    </div>
</div>

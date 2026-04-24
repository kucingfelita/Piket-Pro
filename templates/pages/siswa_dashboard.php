<?php
$pageTitle = "Siswa Dashboard";
// Cek jika pengguna tidak login atau bukan siswa
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'siswa') {
    $_SESSION['error_message'] = "Anda harus login sebagai siswa untuk mengakses halaman ini.";
    header('Location: index.php?page=login');
    exit;
}
?>

<div class="card">
    <div class="card-header">
        <h3>Dashboard Siswa</h3>
    </div>
    <div class="card-body">
        <h5 class="card-title">Selamat Datang, <?php echo htmlspecialchars($_SESSION['user_full_name']); ?>!</h5>
        <p class="card-text">Pilih salah satu menu di bawah untuk melanjutkan.</p>
        <hr>
        <a href="index.php?page=absensi" class="btn btn-primary">Absensi & Laporan Piket</a>
        <a href="index.php?page=jadwal" class="btn btn-outline-primary">Lihat Jadwal Piket</a>
    </div>
</div>

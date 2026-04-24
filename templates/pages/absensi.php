<?php
$pageTitle = "Absensi Harian";

// Hanya siswa yang bisa akses
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'siswa') {
    header('Location: index.php?page=login');
    exit;
}

require 'config/database.php';

$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');

// Cek jadwal piket hari ini
$stmt_schedule = $pdo->prepare("SELECT id FROM schedule WHERE user_id = ? AND picket_date = ?");
$stmt_schedule->execute([$user_id, $today]);
$schedule = $stmt_schedule->fetch();
$schedule_id = $schedule['id'] ?? null;

$report = null;
if ($schedule_id) {
    // Cek apakah sudah ada laporan (absen masuk/selesai) untuk jadwal ini
    $stmt_report = $pdo->prepare("SELECT * FROM reports WHERE schedule_id = ?");
    $stmt_report->execute([$schedule_id]);
    $report = $stmt_report->fetch();
}

// Logika Absen Masuk
if ($schedule_id && !$report && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['check_in'])) {
    try {
        $stmt_insert = $pdo->prepare("INSERT INTO reports (schedule_id, check_in_time) VALUES (?, NOW())");
        $stmt_insert->execute([$schedule_id]);
        $_SESSION['success_message'] = "Absen masuk berhasil dicatat pukul " . date('H:i') . ". Jangan lupa mengisi laporan di akhir piket.";
        header("Location: index.php?page=absensi");
        exit;
    } catch (Exception $e) {
        $error_message = "Gagal mencatat absen masuk: " . $e->getMessage();
    }
}

?>

<div class="card">
    <div class="card-header">
        <h4>Absensi Piket Harian</h4>
    </div>
    <div class="card-body">
        <?php if (isset($_SESSION['success_message'])):
        ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if (isset($error_message)):
        ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($schedule_id): // Jika ada jadwal hari ini ?>
            <?php if (!$report): // Belum pernah absen masuk ?>
                <div class="text-center">
                    <p>Anda memiliki jadwal piket hari ini. Silakan tekan tombol di bawah untuk mencatat kehadiran Anda.</p>
                    <form method="POST" action="index.php?page=absensi">
                        <button type="submit" name="check_in" class="btn btn-primary btn-lg">Absen Masuk</button>
                    </form>
                </div>
            <?php else: // Sudah absen masuk ?>
                <div class="alert alert-info">
                    <h5 class="alert-heading">Anda Sudah Absen Masuk</h5>
                    <p>Anda telah mencatat absen masuk pada pukul <strong><?php echo (new DateTime($report['check_in_time']))->format('H:i:s'); ?></strong>.</p>
                    <hr>
                    <?php if (is_null($report['check_out_time'])): ?>
                        <p class="mb-0">Silakan lanjutkan dengan mengisi laporan piket jika tugas Anda sudah selesai.</p>
                        <a href="index.php?page=lapor_piket" class="btn btn-success mt-2">Isi Laporan Piket (Absen Keluar)</a>
                    <?php else: ?>
                        <p class="mb-0">Anda juga sudah menyelesaikan laporan piket pada pukul <strong><?php echo (new DateTime($report['check_out_time']))->format('H:i:s'); ?></strong>. Terima kasih!</p>
                        <a href="index.php?page=siswa_dashboard" class="btn btn-secondary mt-2">Kembali ke Dashboard</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php else: // Tidak ada jadwal hari ini ?>
            <div class="alert alert-warning">
                Anda tidak memiliki jadwal piket hari ini. Anda tidak dapat melakukan absensi.
            </div>
            <a href="index.php?page=siswa_dashboard" class="btn btn-secondary">Kembali ke Dashboard</a>
        <?php endif; ?>
    </div>
</div>

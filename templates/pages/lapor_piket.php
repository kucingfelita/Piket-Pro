<?php
$pageTitle = "Laporan Piket Harian";

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'siswa') {
    header('Location: index.php?page=login');
    exit;
}

require 'config/database.php';

$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');

// 1. Dapatkan jadwal hari ini
$stmt_schedule = $pdo->prepare("SELECT id FROM schedule WHERE user_id = ? AND picket_date = ?");
$stmt_schedule->execute([$user_id, $today]);
$schedule = $stmt_schedule->fetch();
$schedule_id = $schedule['id'] ?? null;

$report = null;
if ($schedule_id) {
    // 2. Dapatkan laporan yang terkait
    $stmt_report = $pdo->prepare("SELECT * FROM reports WHERE schedule_id = ?");
    $stmt_report->execute([$schedule_id]);
    $report = $stmt_report->fetch();
}

// 3. Logika untuk UPDATE laporan (check-out)
if ($report && is_null($report['check_out_time']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_report'])) {
    $turnover = $_POST['turnover'] ?? 0;
    $income = $_POST['income'] ?? 0;
    $notes = $_POST['notes'] ?? '';

    if (is_numeric($turnover) && is_numeric($income)) {
        try {
            $pdo->beginTransaction();

            $stmt_update = $pdo->prepare(
                "UPDATE reports SET turnover = ?, income = ?, notes = ?, check_out_time = NOW(), status = 'menunggu' WHERE id = ?"
            );
            $stmt_update->execute([$turnover, $income, $notes, $report['id']]);
            
            $pdo->commit();
            $_SESSION['success_message'] = "Laporan akhir berhasil dikirim dan menunggu konfirmasi.";
            header("Location: index.php?page=siswa_dashboard");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error_message = "Gagal menyimpan laporan: " . $e->getMessage();
        }
    } else {
        $error_message = "Omset dan Income harus berupa angka.";
    }
}

?>

<div class="card">
    <div class="card-header">
        <h4>Laporan Piket Harian (Absen Keluar)</h4>
    </div>
    <div class="card-body">
        <?php if (isset($error_message)):
        ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <?php if (!$schedule_id): ?>
            <div class="alert alert-warning">Anda tidak memiliki jadwal piket hari ini.</div>
            <a href="index.php?page=siswa_dashboard" class="btn btn-secondary">Kembali</a>

        <?php elseif (!$report || is_null($report['check_in_time'])): ?>
            <div class="alert alert-warning">Anda harus melakukan 'Absen Masuk' terlebih dahulu sebelum mengisi laporan.</div>
            <a href="index.php?page=absensi" class="btn btn-primary">Lakukan Absen Masuk</a>

        <?php elseif ($report['check_in_status'] === 'ditolak'): ?>
            <div class="alert alert-danger">Absensi masuk Anda telah ditolak oleh admin. Anda tidak dapat mengisi laporan akhir.</div>
            <a href="index.php?page=siswa_dashboard" class="btn btn-secondary">Kembali ke Dashboard</a>

        <?php elseif ($report['check_in_status'] === 'menunggu'): ?>
            <div class="alert alert-warning">Absensi masuk Anda sedang menunggu persetujuan admin. Anda belum bisa mengisi laporan akhir.</div>
            <a href="index.php?page=siswa_dashboard" class="btn btn-secondary">Kembali ke Dashboard</a>

        <?php elseif (!is_null($report['check_out_time'])): ?>
            <div class="alert alert-info">Anda sudah menyelesaikan laporan untuk hari ini. Laporan Anda sedang menunggu atau sudah diproses oleh admin.</div>
            <a href="index.php?page=siswa_dashboard" class="btn btn-secondary">Kembali ke Dashboard</a>

        <?php else: // Kondisi ideal: sudah check-in, belum check-out ?>
            <form method="POST" action="index.php?page=lapor_piket">
                <div class="alert alert-info">Anda tercatat masuk pada pukul <strong><?php echo (new DateTime($report['check_in_time']))->format('H:i:s'); ?></strong>. Silakan lengkapi laporan di bawah ini untuk absen keluar.</div>
                <div class="mb-3">
                    <label for="turnover" class="form-label">Omset Hari Ini (Rp)</label>
                    <input type="number" class="form-control" id="turnover" name="turnover" placeholder="Contoh: 150000" required>
                </div>
                <div class="mb-3">
                    <label for="income" class="form-label">Income Hari Ini (Rp)</label>
                    <input type="number" class="form-control" id="income" name="income" placeholder="Contoh: 45000" required>
                </div>
                <div class="mb-3">
                    <label for="notes" class="form-label">Catatan Tambahan (Opsional)</label>
                    <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Contoh: Stok barang X habis."></textarea>
                </div>
                <div class="d-grid">
                    <button type="submit" name="submit_report" class="btn btn-primary">Kirim Laporan & Absen Keluar</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>
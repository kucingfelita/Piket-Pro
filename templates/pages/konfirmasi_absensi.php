<?php
$pageTitle = "Konfirmasi Absensi Masuk";

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: index.php?page=login');
    exit;
}

require 'config/database.php';

// LOGIKA APPROVE/REJECT ABSENSI MASUK
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $report_id = $_POST['report_id'] ?? 0;

    if (isset($_POST['approve_check_in']) && $report_id > 0) {
        $stmt = $pdo->prepare("UPDATE reports SET check_in_status = 'disetujui' WHERE id = ?");
        $stmt->execute([$report_id]);
        $_SESSION['success_message'] = "Absensi masuk berhasil disetujui.";
    }

    if (isset($_POST['reject_check_in']) && $report_id > 0) {
        $stmt = $pdo->prepare("UPDATE reports SET check_in_status = 'ditolak' WHERE id = ?");
        $stmt->execute([$report_id]);
        $_SESSION['success_message'] = "Absensi masuk telah ditolak.";
    }
    
    header("Location: index.php?page=konfirmasi_absensi");
    exit;
}

// Ambil data absensi yang masih menunggu
$stmt = $pdo->prepare(
    "SELECT r.id, r.check_in_time, u.full_name, u.class, s.picket_date
     FROM reports r
     JOIN schedule s ON r.schedule_id = s.id
     JOIN users u ON s.user_id = u.id
     WHERE r.check_in_status = 'menunggu' AND r.check_in_time IS NOT NULL
     ORDER BY r.check_in_time DESC"
);
$stmt->execute();
$pending_check_ins = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!-- Tampilkan notifikasi -->
<?php if (isset($_SESSION['success_message'])):
?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h4>Konfirmasi Absensi Masuk</h4>
    </div>
    <div class="card-body">
        <p class="card-text">Setujui atau tolak absensi masuk siswa. Siswa yang disetujui dapat melanjutkan untuk mengisi laporan akhir.</p>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Waktu Absen</th>
                        <th>Nama Siswa</th>
                        <th>Kelas</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($pending_check_ins)):
                    ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted">Tidak ada absensi masuk yang menunggu konfirmasi.</td>
                        </tr>
                    <?php else:
                    ?>
                        <?php foreach ($pending_check_ins as $check_in):
                        ?>
                            <tr>
                                <td><?php echo (new DateTime($check_in['check_in_time']))->format('d M Y, H:i:s'); ?></td>
                                <td><?php echo htmlspecialchars($check_in['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($check_in['class']); ?></td>
                                <td>
                                    <form method="POST" action="index.php?page=konfirmasi_absensi" class="d-inline">
                                        <input type="hidden" name="report_id" value="<?php echo $check_in['id']; ?>">
                                        <button type="submit" name="approve_check_in" class="btn btn-success btn-sm">Setujui</button>
                                    </form>
                                    <form method="POST" action="index.php?page=konfirmasi_absensi" class="d-inline">
                                        <input type="hidden" name="report_id" value="<?php echo $check_in['id']; ?>">
                                        <button type="submit" name="reject_check_in" class="btn btn-danger btn-sm" onclick="return confirm('Anda yakin ingin menolak absensi siswa ini?');">Tolak</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

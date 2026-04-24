<?php
$pageTitle = "Konfirmasi Laporan";

// Hanya admin yang bisa akses
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: index.php?page=login');
    exit;
}

require 'config/database.php';

// LOGIKA APPROVE/REJECT
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $report_id = $_POST['report_id'] ?? 0;

    if (isset($_POST['approve_report']) && $report_id > 0) {
        $pdo->beginTransaction();
        try {
            // 1. Dapatkan tanggal piket dari laporan yang akan disetujui
            $stmt_get_date = $pdo->prepare("SELECT s.picket_date FROM reports r JOIN schedule s ON r.schedule_id = s.id WHERE r.id = ?");
            $stmt_get_date->execute([$report_id]);
            $picket_date = $stmt_get_date->fetchColumn();

            // 2. Cek apakah sudah ada laporan lain yang disetujui pada tanggal tersebut
            $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM reports r JOIN schedule s ON r.schedule_id = s.id WHERE s.picket_date = ? AND r.status = 'disetujui'");
            $stmt_check->execute([$picket_date]);
            $approved_count = $stmt_check->fetchColumn();

            if ($approved_count > 0) {
                // 3. Jika sudah ada, setujui laporan ini dengan omset/income 0
                $stmt_approve_zero = $pdo->prepare("UPDATE reports SET status = 'disetujui', turnover = 0, income = 0 WHERE id = ?");
                $stmt_approve_zero->execute([$report_id]);
            } else {
                // 4. Jika ini yang pertama, setujui seperti biasa
                $stmt_approve = $pdo->prepare("UPDATE reports SET status = 'disetujui' WHERE id = ?");
                $stmt_approve->execute([$report_id]);
            }
            
            $pdo->commit();
            $_SESSION['success_message'] = "Laporan berhasil disetujui.";

        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error_message'] = "Gagal menyetujui laporan: " . $e->getMessage();
        }
    }

    if (isset($_POST['reject_report']) && $report_id > 0) {
        $reason = $_POST['rejection_reason'] ?? 'Tidak ada alasan diberikan.';
        if(empty(trim($reason))) $reason = 'Tidak ada alasan diberikan.';
        $stmt = $pdo->prepare("UPDATE reports SET status = 'ditolak', rejection_reason = ? WHERE id = ?");
        $stmt->execute([$reason, $report_id]);
        $_SESSION['success_message'] = "Laporan berhasil ditolak.";
    }
    
    // Redirect ke halaman yang sama dengan status filter yang aktif
    $status_redirect = $_GET['status'] ?? 'menunggu';
    header("Location: index.php?page=konfirmasi_laporan&status=" . $status_redirect);
    exit;
}


// LOGIKA TAMPILAN
$status_filter = $_GET['status'] ?? 'menunggu';
$valid_statuses = ['menunggu', 'disetujui', 'ditolak'];
if (!in_array($status_filter, $valid_statuses)) {
    $status_filter = 'menunggu';
}

// Query untuk mengambil laporan
$sql = "SELECT 
            r.id, r.turnover, r.income, r.notes, r.status, r.rejection_reason, r.check_in_time, r.check_out_time, s.picket_date, 
            u_reporter.full_name as reporter_name, 
            u_reporter.class as reporter_class

        FROM reports r 
        JOIN schedule s ON r.schedule_id = s.id 
        JOIN users u_reporter ON s.user_id = u_reporter.id
        WHERE r.check_in_status = 'disetujui' AND r.check_out_time IS NOT NULL AND r.status = :status
        ORDER BY s.picket_date DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute(['status' => $status_filter]);
$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
        <h4>Konfirmasi Laporan Piket</h4>
    </div>
    <div class="card-body">
        <!-- Navigasi Tab -->
        <ul class="nav nav-tabs mb-3">
            <?php foreach ($valid_statuses as $status):
            ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($status_filter === $status) ? 'active' : ''; ?>" href="index.php?page=konfirmasi_laporan&status=<?php echo $status; ?>">
                        <?php echo ucfirst($status); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>

        <!-- Daftar Laporan -->
        <?php if (empty($reports)):
        ?>
            <div class="text-center p-4">
                <p class="text-muted">Tidak ada laporan dengan status '<?php echo $status_filter; ?>'.</p>
            </div>
        <?php else:
        ?>
            <?php foreach ($reports as $report):
            ?>
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <h5 class="card-title"><?php echo htmlspecialchars($report['reporter_name']); ?> - <?php echo htmlspecialchars($report['reporter_class']); ?></h5>
                                <h6 class="card-subtitle mb-2 text-muted">Tgl: <?php echo (new DateTime($report['picket_date']))->format('d M Y'); ?></h6>

                                <p class="card-text mb-1">
                                    <strong>Waktu:</strong> 
                                    Masuk: <?php echo $report['check_in_time'] ? (new DateTime($report['check_in_time']))->format('H:i:s') : '<em class="text-muted">Belum Absen Masuk</em>'; ?> | 
                                    Keluar: <?php echo $report['check_out_time'] ? (new DateTime($report['check_out_time']))->format('H:i:s') : '<em class="text-muted">Belum Absen Keluar</em>'; ?>
                                </p>

                                <p class="card-text mb-1">
                                    <strong>Omset:</strong> Rp <?php echo number_format($report['turnover'], 0, ',', '.'); ?><br>
                                    <strong>Income:</strong> Rp <?php echo number_format($report['income'], 0, ',', '.'); ?>
                                </p>
                                <p class="card-text fst-italic">
                                    <strong>Catatan:</strong> <?php echo !empty($report['notes']) ? htmlspecialchars($report['notes']) : '-'; ?>
                                </p>
                                <?php if ($report['status'] === 'ditolak'):
                                ?>
                                    <p class="card-text mt-2"><strong class="text-danger">Alasan Ditolak:</strong> <?php echo htmlspecialchars($report['rejection_reason']); ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-4 d-flex align-items-center justify-content-end">
                                <?php if ($report['status'] === 'menunggu'):
                                ?>
                                    <form method="POST" action="index.php?page=konfirmasi_laporan&status=menunggu" class="d-inline">
                                        <input type="hidden" name="report_id" value="<?php echo $report['id']; ?>">
                                        <button type="submit" name="approve_report" class="btn btn-success btn-sm me-2">Setujui</button>
                                    </form>
                                    <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#rejectModal-<?php echo $report['id']; ?>">
                                        Tolak
                                    </button>
                                <?php else:
                                ?>
                                    <span class="badge fs-6 bg-<?php echo $report['status'] === 'disetujui' ? 'success' : 'danger'; ?>">
                                        <?php echo ucfirst($report['status']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Penolakan -->
                <div class="modal fade" id="rejectModal-<?php echo $report['id']; ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog">
                        <form method="POST" action="index.php?page=konfirmasi_laporan&status=menunggu">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Tolak Laporan</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <p>Anda akan menolak laporan dari <strong><?php echo htmlspecialchars($report['reporter_name']); ?></strong>.</p>
                                    <input type="hidden" name="report_id" value="<?php echo $report['id']; ?>">
                                    <div class="mb-3">
                                        <label for="rejection_reason-<?php echo $report['id']; ?>" class="form-label">Alasan Penolakan (Wajib)</label>
                                        <textarea id="rejection_reason-<?php echo $report['id']; ?>" class="form-control" name="rejection_reason" rows="3" required></textarea>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                    <button type="submit" name="reject_report" class="btn btn-danger">Tolak Laporan</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

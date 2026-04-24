<?php
$pageTitle = "Laporan Bulanan";

// Hanya admin yang bisa akses
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: index.php?page=login');
    exit;
}

require 'config/database.php';

// Logika untuk filter bulan dan tahun
$current_month = date('m');
$current_year = date('Y');

$selected_month = $_GET['month'] ?? $current_month;
$selected_year = $_GET['year'] ?? $current_year;

// Logika untuk mengambil data laporan
$total_income = 0;
$reports = [];

if (checkdate($selected_month, 1, $selected_year)) {
    $stmt = $pdo->prepare(
        "SELECT 
            r.income, 
            s.picket_date, 
            u.full_name as reporter_name,
            (SELECT GROUP_CONCAT(u_team.full_name SEPARATOR ', ') 
             FROM schedule s_team 
             JOIN users u_team ON s_team.user_id = u_team.id 
             WHERE s_team.picket_date = s.picket_date) as team_members
         FROM reports r
         JOIN schedule s ON r.schedule_id = s.id
         JOIN users u ON s.user_id = u.id
         WHERE r.status = 'disetujui' AND MONTH(s.picket_date) = ? AND YEAR(s.picket_date) = ?
         ORDER BY s.picket_date ASC"
    );
    $stmt->execute([$selected_month, $selected_year]);
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Hitung total income
    foreach ($reports as $report) {
        $total_income += $report['income'];
    }
}

// Array nama bulan untuk dropdown
$months = [
    '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
    '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
    '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
];

?>

<div class="card">
    <div class="card-header">
        <h4>Laporan Pemasukan Bulanan</h4>
    </div>
    <div class="card-body">

        <!-- Form Filter -->
        <form method="GET" class="row g-3 align-items-center mb-4">
            <input type="hidden" name="page" value="laporan_bulanan">
            <div class="col-md-4">
                <label for="month" class="form-label">Bulan</label>
                <select id="month" name="month" class="form-select">
                    <?php foreach ($months as $num => $name): ?>
                        <option value="<?php echo $num; ?>" <?php echo $selected_month == $num ? 'selected' : ''; ?>><?php echo $name; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label for="year" class="form-label">Tahun</label>
                <input type="number" id="year" name="year" class="form-control" value="<?php echo $selected_year; ?>" min="2020" max="<?php echo date('Y') + 1; ?>">
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-primary">Tampilkan Laporan</button>
            </div>
        </form>

        <hr>

        <!-- Hasil Laporan -->
        <h5 class="mb-3">
            Laporan untuk: <strong><?php echo $months[$selected_month] . ' ' . $selected_year; ?></strong>
        </h5>

        <div class="alert alert-success">
            <h5>Total Pemasukan (Income): <strong>Rp <?php echo number_format($total_income, 0, ',', '.'); ?></strong></h5>
        </div>

        <h6>Rincian Laporan (Hanya yang Disetujui):</h6>
        <div class="table-responsive">
            <table class="table table-striped table-sm">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Tim Piket</th>
                        <th>Income</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reports)): ?>
                        <tr>
                            <td colspan="3" class="text-center text-muted">Tidak ada laporan yang disetujui untuk periode ini.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($reports as $report): ?>
                            <tr>
                                <td><?php echo (new DateTime($report['picket_date']))->format('d M Y'); ?></td>
                                <td>
                                    <span class="d-block"><strong>Pelapor:</strong> <?php echo htmlspecialchars($report['reporter_name']); ?></span>
                                    <small class="text-muted"><strong>Tim:</strong> <?php echo htmlspecialchars($report['team_members']); ?></small>
                                </td>
                                <td>Rp <?php echo number_format($report['income'], 0, ',', '.'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>
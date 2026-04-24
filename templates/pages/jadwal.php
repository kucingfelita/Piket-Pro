<?php
$pageTitle = "Jadwal Piket";

// Pastikan pengguna sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?page=login');
    exit;
}

require 'config/database.php';

// Tampilkan notifikasi jika ada
if (isset($_SESSION['success_message'])) {
    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">' . $_SESSION['success_message'] . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">' . $_SESSION['error_message'] . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
    unset($_SESSION['error_message']);
}


// Data untuk filter
$selected_class = $_GET['class'] ?? 'Semua';
$stmt_classes = $pdo->query("SELECT DISTINCT class FROM users WHERE class IS NOT NULL ORDER BY class");
$available_classes = $stmt_classes->fetchAll(PDO::FETCH_COLUMN);
array_unshift($available_classes, 'Semua');

$days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

// Ambil kelas siswa yang sedang login
$current_user_class = '';
if ($_SESSION['user_role'] === 'siswa') {
    $stmt_user = $pdo->prepare("SELECT class FROM users WHERE id = ?");
    $stmt_user->execute([$_SESSION['user_id']]);
    $current_user_class = $stmt_user->fetchColumn();
}




// Bangun query untuk mengambil jadwal
$sql = "SELECT schedule.id, schedule.picket_date, users.full_name, users.class, users.id as user_id
        FROM schedule 
        JOIN users ON schedule.user_id = users.id
        WHERE schedule.status = 'aktif'";

if ($selected_class !== 'Semua') {
    $sql .= " AND users.class = :class";
}

$sql .= " ORDER BY schedule.picket_date ASC, users.class ASC";
$stmt = $pdo->prepare($sql);

if ($selected_class !== 'Semua') {
    $stmt->bindParam(':class', $selected_class);
}

$stmt->execute();
$schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
        <h4 class="my-1">Jadwal Piket Unit Produksi</h4>
        <form method="GET" action="index.php" class="mb-0 my-1">
            <input type="hidden" name="page" value="jadwal">
            <div class="input-group">
                <label class="input-group-text" for="class-filter">Filter Kelas:</label>
                <select name="class" id="class-filter" class="form-select" onchange="this.form.submit()">
                    <?php foreach ($available_classes as $class_name): ?>
                        <option value="<?php echo htmlspecialchars($class_name); ?>" <?php echo ($selected_class === $class_name) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($class_name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead class="table-primary">
                    <tr>
                        <th>Tanggal</th>
                        <th>Hari</th>
                        <th>Nama Siswa</th>
                        <th>Kelas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($schedules)):
                    ?>
                        <tr>
                            <td colspan="4" class="text-center fst-italic py-4">Tidak ada jadwal untuk ditampilkan.</td>
                        </tr>
                    <?php else:
                    ?>
                        <?php foreach ($schedules as $schedule):
                            $date = new DateTime($schedule['picket_date']);
                            $is_personal = ($_SESSION['user_role'] === 'siswa' && $schedule['user_id'] === $_SESSION['user_id']);
                        ?>
                            <tr class="<?php echo $is_personal ? 'table-info' : ''; ?>">
                                <td class="<?php echo $is_personal ? 'fw-bold' : ''; ?>"><?php echo $date->format('d M Y'); ?></td>
                                <td><?php echo $days[$date->format('w')]; ?></td>
                                <td><?php echo htmlspecialchars($schedule['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($schedule['class']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if ($_SESSION['user_role'] === 'siswa'): ?>
        <div class="form-text">Jadwal piket Anda ditandai dengan latar biru dan teks tebal.</div>
        <?php endif; ?>
    </div>
</div>

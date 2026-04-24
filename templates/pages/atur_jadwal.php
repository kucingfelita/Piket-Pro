<?php
$pageTitle = "Atur Jadwal";

// Hanya admin yang bisa akses
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: index.php?page=login');
    exit;
}

require 'config/database.php';
$classes = ['X PPLG 1', 'X PPLG 2', 'XI RPL', 'XI PG'];

?>

<style>
    @media print {
        body * {
            visibility: hidden;
        }
        .printable, .printable * {
            visibility: visible;
        }
        .printable {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
        }
        .no-print {
            display: none;
        }
    }
</style>

<script>
function printSchedule() {
    const selectedClass = document.getElementById('class-print-filter').value;
    const style = document.createElement('style');
    style.id = 'print-style'; // Beri ID agar bisa dihapus nanti
    let css = '@media print { .no-print { display: none !important; } ';

    if (selectedClass !== 'Semua') {
        css += 'tbody tr { display: none !important; } ';
        css += `tbody tr[data-class="${selectedClass}"] { display: table-row !important; }`;
    }
    
    css += '}';
    style.innerHTML = css;

    // Hapus style lama jika ada, lalu tambahkan yang baru
    const oldStyle = document.getElementById('print-style');
    if (oldStyle) {
        oldStyle.remove();
    }
    document.head.appendChild(style);

    window.print();
}
</script>

<?php

// LOGIKA UNTUK SEMBUNYIKAN JADWAL OTOMATIS (Jadwal yang sudah lewat)
$yesterday = date('Y-m-d', strtotime('-1 day'));
try {
    $stmt_check_old_schedules = $pdo->prepare("SELECT COUNT(*) FROM schedule WHERE picket_date <= ? AND status = 'aktif'");
    $stmt_check_old_schedules->execute([$yesterday]);
    $old_schedules_count = $stmt_check_old_schedules->fetchColumn();

    if ($old_schedules_count > 0) {
        $pdo->beginTransaction();
        $stmt_hide_old_schedules = $pdo->prepare("UPDATE schedule SET status = 'sembunyi' WHERE picket_date <= ? AND status = 'aktif'");
        $stmt_hide_old_schedules->execute([$yesterday]);
        $hidden_rows = $stmt_hide_old_schedules->rowCount();
        $pdo->commit();
        if ($hidden_rows > 0) {
            $_SESSION['info_message'] = $hidden_rows . " jadwal piket yang sudah lewat (sebelum " . date('d M Y', strtotime($yesterday)) . ") telah disembunyikan secara otomatis.";
        }
    }
} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['error_message'] = "Gagal menyembunyikan jadwal lama secara otomatis: " . $e->getMessage();
}


// LOGIKA UNTUK GENERATE JADWAL
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_schedule'])) {
    $start_date = new DateTime($_POST['start_date']);
    $end_date = new DateTime($_POST['end_date']);
    $end_date->modify('+1 day');

    $start_attendance_number = max(1, (int)($_POST['start_attendance_number'] ?? 1)); // Ensure it's at least 1

    // 1. Ambil siswa dan kelompokkan berdasarkan kelas
    $students_by_class = [];
    foreach ($classes as $class) {
        $stmt_students = $pdo->prepare("SELECT id FROM users WHERE role = 'siswa' AND class = ? ORDER BY username ASC");
        $stmt_students->execute([$class]);
        $students_by_class[$class] = $stmt_students->fetchAll(PDO::FETCH_COLUMN);
    }

    // 2. Ambil tanggal yang sudah ada jadwalnya untuk dilewati
    $stmt_existing = $pdo->query("SELECT DISTINCT picket_date FROM schedule");
    $existing_dates = $stmt_existing->fetchAll(PDO::FETCH_COLUMN);

    // 3. Pastikan setiap kelas punya siswa
    $can_generate = true;
    foreach ($classes as $class) {
        if (empty($students_by_class[$class])) {
            $can_generate = false;
            $_SESSION['error_message'] = "Tidak bisa membuat jadwal karena kelas '$class' tidak memiliki siswa.";
            break;
        }
    }

    if ($can_generate) {
        $student_indices = array_fill_keys($classes, $start_attendance_number - 1); // Adjust to 0-based index
        $new_schedules_count = 0;
        
        $interval = new DateInterval('P1D');
        $date_period = new DatePeriod($start_date, $interval, $end_date);

        $pdo->beginTransaction();
        try {
            $stmt_insert = $pdo->prepare("INSERT INTO schedule (user_id, picket_date) VALUES (?, ?)");

            foreach ($date_period as $date) {
                $day_of_week = $date->format('N'); // 1 (Senin) - 7 (Minggu)
                $current_date_str = $date->format('Y-m-d');

                // Lewati weekend atau jika tanggal sudah ada jadwal
                if ($day_of_week >= 6 || in_array($current_date_str, $existing_dates)) {
                    continue;
                }

                // Untuk setiap hari, ambil 1 siswa dari setiap kelas
                foreach ($classes as $class) {
                    $class_students = $students_by_class[$class];
                    // Pastikan indeks tidak melebihi jumlah siswa di kelas tersebut
                    if ($student_indices[$class] >= count($class_students)) {
                        $student_indices[$class] = 0; // Kembali ke awal jika sudah melewati semua siswa
                    }
                    $current_student_index = $student_indices[$class];
                    $student_id = $class_students[$current_student_index];

                    $stmt_insert->execute([$student_id, $current_date_str]);
                    $new_schedules_count++;

                    // Pindah ke siswa selanjutnya di kelas itu
                    $student_indices[$class]++;
                }
            }
            $pdo->commit();
            if ($new_schedules_count > 0) {
                $_SESSION['success_message'] = ($new_schedules_count / count($classes)) . " hari jadwal baru berhasil dibuat (".$new_schedules_count." entri).";
            } else {
                $_SESSION['error_message'] = "Tidak ada jadwal baru yang dibuat. Mungkin semua tanggal sudah terisi atau berada di akhir pekan.";
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error_message'] = "Gagal membuat jadwal: " . $e->getMessage();
        }
    }

    header("Location: index.php?page=atur_jadwal");
    exit;
}

// LOGIKA UNTUK HAPUS SEMUA JADWAL
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_all_schedules'])) {
    if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
        $_SESSION['error_message'] = "Anda tidak memiliki izin untuk melakukan tindakan ini.";
        header('Location: index.php?page=atur_jadwal');
        exit;
    }
    try {
        $pdo->beginTransaction();
        // Hapus laporan yang terkait dengan jadwal
        $pdo->exec("DELETE FROM reports WHERE schedule_id IN (SELECT id FROM schedule)");
        // Hapus jadwal
        $pdo->exec("DELETE FROM schedule");
        $pdo->commit();
        $_SESSION['success_message'] = "Semua jadwal dan laporan terkait berhasil dihapus.";
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error_message'] = "Gagal menghapus semua jadwal: " . $e->getMessage();
    }
    header("Location: index.php?page=atur_jadwal");
    exit;
}


// Ambil jadwal yang ada untuk ditampilkan
$stmt_display = $pdo->query("SELECT schedule.id, schedule.picket_date, users.full_name, users.class 
                             FROM schedule 
                             JOIN users ON schedule.user_id = users.id 
                             WHERE schedule.status = 'aktif'
                             ORDER BY schedule.picket_date ASC, users.class ASC");
$schedules = $stmt_display->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Tampilkan notifikasi -->
<?php if (isset($_SESSION['success_message'])):
?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>
<?php if (isset($_SESSION['error_message'])):
?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>
<?php if (isset($_SESSION['info_message'])):
?>
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <?php echo $_SESSION['info_message']; unset($_SESSION['info_message']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>


<div class="row">
    <!-- Kolom Buat Jadwal -->
    <div class="col-lg-4 mb-4 no-print">
        <div class="card">
            <div class="card-header">
                <h5>Buat Jadwal Otomatis</h5>
            </div>
            <div class="card-body">
                <form action="index.php?page=atur_jadwal" method="POST">
                    <div class="mb-3">
                        <label for="start_date" class="form-label">Tanggal Mulai</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" required>
                    </div>
                    <div class="mb-3">
                        <label for="end_date" class="form-label">Tanggal Selesai</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" required>
                    </div>
                    <div class="mb-3">
                        <label for="start_attendance_number" class="form-label">Mulai dari Absen Ke- (per kelas)</label>
                        <input type="number" class="form-control" id="start_attendance_number" name="start_attendance_number" value="1" min="1">
                        <small class="form-text text-muted">Nomor absen awal untuk setiap kelas. Jika 1, mulai dari siswa pertama.</small>
                    </div>
                    <div class="d-grid">
                        <button type="submit" name="generate_schedule" class="btn btn-success">Buat Jadwal (Aturan Baru)</button>
                    </div>
                </form>
            </div>
            <div class="card-footer">
                <small class="text-muted">Aturan: 1 siswa per kelas, per hari kerja.</small>
            </div>
        </div>
    </div>

    <!-- Kolom Daftar Jadwal -->
    <div class="col-lg-8 printable">
        <div class="card">
            <div class="card-header no-print">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Daftar Jadwal Saat Ini</h5>
                        <div class="d-flex">
                            <select id="class-print-filter" class="form-select form-select-sm me-2">
                                <option value="Semua">Semua Kelas</option>
                                <?php foreach ($classes as $class): ?>
                                    <option value="<?php echo htmlspecialchars($class); ?>"><?php echo htmlspecialchars($class); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button onclick="printSchedule()" class="btn btn-sm btn-outline-secondary">Cetak Jadwal</button>
                        </div>
                    </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover table-sm">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Nama Siswa</th>
                                <th>Kelas</th>
                                <th class="no-print">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($schedules)):
                            ?>
                                <tr>
                                    <td colspan="4" class="text-center">Belum ada jadwal yang dibuat.</td>
                                </tr>
                            <?php else:
                            ?>
                                <?php foreach ($schedules as $schedule):
                                ?>
                                    <tr data-class="<?php echo htmlspecialchars($schedule['class']); ?>">
                                        <td><?php echo (new DateTime($schedule['picket_date']))->format('d M Y'); ?></td>
                                        <td><?php echo htmlspecialchars($schedule['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($schedule['class']); ?></td>
                                        <td class="no-print">
                                            <a href="index.php?page=hapus_jadwal&id=<?php echo $schedule['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Apakah Anda yakin ingin menghapus jadwal ini?');">Hapus</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <form action="index.php?page=atur_jadwal" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus SEMUA jadwal dan laporan terkait? Tindakan ini tidak dapat dibatalkan!');" class="no-print">
                    <button type="submit" name="delete_all_schedules" class="btn btn-danger mt-3">Hapus Semua Jadwal</button>
                </form>
            </div>
        </div>
    </div>
</div>

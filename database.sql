-- Skema Database untuk Aplikasi PiketPro
--
-- Database: `piketpro_db`
--

CREATE DATABASE IF NOT EXISTS `piketpro_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `piketpro_db`;

-- --------------------------------------------------------

--
-- Struktur tabel untuk `users`
-- Menyimpan data siswa dan admin
--
-- CREATE TABLE `users` (
--   `id` int(11) NOT NULL,
--   `username` varchar(50) NOT NULL COMMENT 'NIS untuk siswa, NIP/username untuk admin',
--   `password` varchar(255) NOT NULL COMMENT 'Password yang sudah di-hash',
--   `full_name` varchar(100) NOT NULL,
--   `class` varchar(20) DEFAULT NULL COMMENT 'Kelas siswa, NULL untuk admin',
--   `role` enum('siswa','admin') NOT NULL
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- CREATE TABLE `schedule` (
--   `id` int(11) NOT NULL,
--   `user_id` int(11) NOT NULL,
--   `picket_date` date NOT NULL
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --
-- -- Contoh data untuk `schedule`
-- --
-- -- --------------------------------------------------------

-- --
-- -- Struktur tabel untuk `reports`
-- -- Menyimpan laporan piket yang diisi oleh siswa
-- --
-- CREATE TABLE `reports` (
--   `id` int(11) NOT NULL,
--   `schedule_id` int(11) NOT NULL,
--   `check_in_status` enum('menunggu','disetujui','ditolak') NOT NULL DEFAULT 'menunggu',
--   `check_in_time` datetime DEFAULT NULL,
--   `check_out_time` datetime DEFAULT NULL,
--   `turnover` decimal(10,2) DEFAULT NULL,
--   `income` decimal(10,2) DEFAULT NULL,
--   `notes` text DEFAULT NULL,
--   `status` enum('menunggu','disetujui','ditolak') NOT NULL DEFAULT 'menunggu',
--   `rejection_reason` varchar(255) DEFAULT NULL,
--   `created_at` timestamp NOT NULL DEFAULT current_timestamp()
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -- --------------------------------------------------------

--
-- Struktur tabel untuk `schedule_swaps`
-- Menyimpan permintaan tukar jadwal piket
--
--
-- Indexes for dumped tables
--

ALTER TABLE `reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `schedule_id` (`schedule_id`);

ALTER TABLE `schedule`
  ADD PRIMARY KEY (`id`),
  ADD KEY `picket_date` (`picket_date`),
  ADD KEY `user_id` (`user_id`);

ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);


--
-- AUTO_INCREMENT for dumped tables
--

ALTER TABLE `reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `schedule`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;


--
-- Constraints for dumped tables
--

ALTER TABLE `reports`
  ADD CONSTRAINT `reports_ibfk_1` FOREIGN KEY (`schedule_id`) REFERENCES `schedule` (`id`) ON DELETE CASCADE;

ALTER TABLE `schedule`
  ADD CONSTRAINT `schedule_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;


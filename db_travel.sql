-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 24, 2025 at 01:30 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_travel`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

DROP TABLE IF EXISTS `admin`;

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `username`, `password`, `nama`, `email`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin@travelkita.com', NULL, '2025-06-12 07:11:30', '2025-06-12 07:11:30');

-- --------------------------------------------------------

--
-- Table structure for table `booking_sequence`
--

DROP TABLE IF EXISTS `booking_sequence`;

CREATE TABLE `booking_sequence` (
  `id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `booking_sequence`
--

INSERT INTO `booking_sequence` (`id`, `created_at`) VALUES
(1, '2025-06-14 11:41:32'),
(2, '2025-06-14 11:42:28'),
(3, '2025-06-14 11:43:37'),
(4, '2025-06-14 12:03:57'),
(5, '2025-06-14 12:07:45'),
(6, '2025-06-14 12:14:36');

-- --------------------------------------------------------

--
-- Table structure for table `detail_reservasi_kursi`
--

CREATE TABLE `detail_reservasi_kursi` (
  `id_detail` int(11) NOT NULL,
  `id_reservasi` int(11) NOT NULL,
  `id_kursi` int(11) NOT NULL,
  `harga` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `detail_reservasi_kursi`
--

INSERT INTO `detail_reservasi_kursi` (`id_detail`, `id_reservasi`, `id_kursi`, `harga`, `created_at`) VALUES
(96, 123, 49, 75000.00, '2025-06-14 11:17:23'),
(101, 135, 49, 75000.00, '2025-06-14 11:28:26'),
(104, 144, 49, 75000.00, '2025-06-14 11:36:04'),
(105, 147, 49, 75000.00, '2025-06-14 11:37:48'),
(106, 150, 49, 75000.00, '2025-06-14 11:40:20'),
(107, 153, 49, 75000.00, '2025-06-14 11:41:27'),
(108, 154, 49, 75000.00, '2025-06-14 11:42:23'),
(109, 157, 49, 75000.00, '2025-06-14 11:43:31'),
(110, 160, 49, 75000.00, '2025-06-14 12:03:52'),
(111, 163, 49, 75000.00, '2025-06-14 12:07:29'),
(112, 166, 49, 75000.00, '2025-06-14 12:14:30'),
(113, 169, 49, 75000.00, '2025-06-14 12:25:08'),
(114, 170, 49, 75000.00, '2025-06-14 12:27:18'),
(115, 171, 49, 75000.00, '2025-06-14 16:54:21'),
(116, 172, 49, 75000.00, '2025-06-14 17:05:31');

--
-- Triggers `detail_reservasi_kursi`
--
DELIMITER $$
CREATE TRIGGER `after_detail_reservasi_delete` AFTER DELETE ON `detail_reservasi_kursi` FOR EACH ROW BEGIN
        UPDATE jadwal j
        JOIN reservasi r ON r.id_reservasi = OLD.id_reservasi
        SET j.kursi_tersedia = (
            SELECT IFNULL(SUM(lk.jumlah_baris * lk.jumlah_kolom), 40) - 
                   IFNULL((
                       SELECT COUNT(*) 
                       FROM detail_reservasi_kursi drk
                       JOIN reservasi res ON drk.id_reservasi = res.id_reservasi
                       WHERE res.id_jadwal = r.id_jadwal AND res.status = 'dibayar'
                   ), 0)
            FROM layout_kursi lk
            LIMIT 1
        )
        WHERE j.id_jadwal = r.id_jadwal;
    END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_detail_reservasi_insert` AFTER INSERT ON `detail_reservasi_kursi` FOR EACH ROW BEGIN
        UPDATE jadwal j
        JOIN reservasi r ON r.id_reservasi = NEW.id_reservasi
        SET j.kursi_tersedia = (
            SELECT IFNULL(SUM(lk.jumlah_baris * lk.jumlah_kolom), 40) - 
                   IFNULL((
                       SELECT COUNT(*) 
                       FROM detail_reservasi_kursi drk
                       JOIN reservasi res ON drk.id_reservasi = res.id_reservasi
                       WHERE res.id_jadwal = r.id_jadwal AND res.status = 'dibayar'
                   ), 0)
            FROM layout_kursi lk
            LIMIT 1
        )
        WHERE j.id_jadwal = r.id_jadwal;
    END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `jadwal`
--

CREATE TABLE `jadwal` (
  `id_jadwal` int(11) NOT NULL,
  `id_kota_asal` int(11) NOT NULL,
  `id_kecamatan_asal` int(11) DEFAULT NULL,
  `id_kota_tujuan` int(11) NOT NULL,
  `id_kecamatan_tujuan` int(11) DEFAULT NULL,
  `id_layout_kursi` int(11) DEFAULT NULL,
  `tanggal_berangkat` date NOT NULL,
  `waktu_berangkat` time NOT NULL,
  `harga` decimal(10,2) NOT NULL,
  `kursi_tersedia` int(11) NOT NULL,
  `estimasi_jam` int(11) DEFAULT NULL,
  `keterangan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `jadwal`
--

INSERT INTO `jadwal` (`id_jadwal`, `id_kota_asal`, `id_kecamatan_asal`, `id_kota_tujuan`, `id_kecamatan_tujuan`, `id_layout_kursi`, `tanggal_berangkat`, `waktu_berangkat`, `harga`, `kursi_tersedia`, `estimasi_jam`, `keterangan`, `created_at`, `updated_at`) VALUES
(1, 2, 7, 1, 3, 2, '2025-06-23', '14:37:00', 75000.00, 40, 10, '0', '2025-06-12 07:35:36', '2025-06-24 08:23:56'),
(2, 1, 5, 2, 8, 1, '2025-06-24', '08:02:00', 700000.00, 40, 2, '0', '2025-06-17 05:46:26', '2025-06-24 11:30:08'),
(3, 2, 9, 1, 2, 2, '2025-06-26', '08:00:00', 100000.00, 0, 4, '', '2025-06-24 08:23:08', '2025-06-24 08:23:08');

-- --------------------------------------------------------

--
-- Table structure for table `kecamatan`
--

CREATE TABLE `kecamatan` (
  `id_kecamatan` int(11) NOT NULL,
  `id_kota` int(11) NOT NULL,
  `nama_kecamatan` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kecamatan`
--

INSERT INTO `kecamatan` (`id_kecamatan`, `id_kota`, `nama_kecamatan`, `created_at`, `updated_at`) VALUES
(1, 1, 'Jakarta Pusat', '2025-06-12 07:11:30', '2025-06-12 07:11:30'),
(2, 1, 'Jakarta Selatan', '2025-06-12 07:11:30', '2025-06-12 07:11:30'),
(3, 1, 'Jakarta Barat', '2025-06-12 07:11:30', '2025-06-12 07:11:30'),
(4, 1, 'Jakarta Utara', '2025-06-12 07:11:30', '2025-06-12 07:11:30'),
(5, 1, 'Jakarta Timur', '2025-06-12 07:11:30', '2025-06-12 07:11:30'),
(6, 2, 'Bandung Kulon', '2025-06-12 07:11:30', '2025-06-12 07:11:30'),
(7, 2, 'Bandung Kidul', '2025-06-12 07:11:30', '2025-06-12 07:11:30'),
(8, 2, 'Bandung Wetan', '2025-06-12 07:11:30', '2025-06-12 07:11:30'),
(9, 2, 'Buahbatu', '2025-06-12 07:11:30', '2025-06-12 07:11:30'),
(10, 2, 'Cibeunying', '2025-06-12 07:11:30', '2025-06-12 07:11:30');

-- --------------------------------------------------------

--
-- Table structure for table `konfirmasi_pembayaran`
--

CREATE TABLE `konfirmasi_pembayaran` (
  `id` int(11) NOT NULL,
  `kode_booking` varchar(100) NOT NULL,
  `bank_tujuan` varchar(100) NOT NULL,
  `nama_pengirim` varchar(100) NOT NULL,
  `jumlah_dibayar` decimal(12,2) NOT NULL,
  `tanggal_transfer` datetime NOT NULL,
  `bukti_transfer` varchar(255) DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  `status` enum('menunggu','diverifikasi','ditolak') NOT NULL DEFAULT 'menunggu',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `konfirmasi_pembayaran`
--

INSERT INTO `konfirmasi_pembayaran` (`id`, `kode_booking`, `bank_tujuan`, `nama_pengirim`, `jumlah_dibayar`, `tanggal_transfer`, `bukti_transfer`, `catatan`, `status`, `created_at`, `updated_at`) VALUES
(1, 'TRV684F922D1A169', 'BCA', 'ahlan boys', 7500000.00, '2025-06-16 18:37:00', 'uploads/bukti_transfer/bukti_1750073785_685001b9498c4.png', NULL, 'menunggu', '2025-06-16 11:36:25', '2025-06-16 12:47:33'),
(2, 'TRV68566E0DAE27A', 'BCA', 'ahlan boys', 700000.00, '2025-06-21 18:35:00', 'payment_685698fe0bc26.png', '', 'menunggu', '2025-06-21 11:35:26', NULL),
(3, 'TRV6857B1DF84657', 'BCA', 'ahlan boys', 75000.00, '2025-06-22 14:33:00', 'payment_6857b1f010cc7.png', '', 'menunggu', '2025-06-22 07:34:08', NULL),
(4, 'TRV6857DD86EC93C', 'BRI', 'ahlan boys', 75000.00, '2025-06-22 17:57:00', 'payment_6857e1a3ddc43.png', '', 'ditolak', '2025-06-22 10:57:39', '2025-06-22 11:43:11'),
(5, 'TRV6858191450CE3', 'BCA', 'ahlan boys', 75000.00, '2025-06-22 21:54:00', 'payment_6858191fe8b7a.png', '', 'menunggu', '2025-06-22 14:54:23', NULL),
(6, 'TRV6859968C2AA46', 'BCA', 'ahlan boys', 700000.00, '2025-06-24 01:01:00', 'payment_685997f2d2725.png', '', 'menunggu', '2025-06-23 18:07:46', NULL),
(7, 'TRV685A2BB9907DD', 'BCA', 'ahlan boys', 700000.00, '2025-06-24 11:38:00', 'payment_685a2bc46c72d.png', '', 'menunggu', '2025-06-24 04:38:28', NULL),
(8, 'TRV685A2C694858F', 'BNI', 'ahlan boys', 700000.00, '2025-06-24 11:41:00', 'payment_685a2c71b53a9.png', '', 'menunggu', '2025-06-24 04:41:21', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `kota`
--

CREATE TABLE `kota` (
  `id_kota` int(11) NOT NULL,
  `nama_kota` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kota`
--

INSERT INTO `kota` (`id_kota`, `nama_kota`, `created_at`, `updated_at`) VALUES
(1, 'Jakarta', '2025-06-12 07:11:30', '2025-06-12 07:11:30'),
(2, 'Bandung', '2025-06-12 07:11:30', '2025-06-12 07:11:30'),
(3, 'Yogyakarta', '2025-06-12 07:11:30', '2025-06-12 07:11:30'),
(4, 'Surabaya', '2025-06-12 07:11:30', '2025-06-12 07:11:30'),
(5, 'Semarang', '2025-06-12 07:11:30', '2025-06-12 07:11:30');

-- --------------------------------------------------------

--
-- Table structure for table `kursi`
--

CREATE TABLE `kursi` (
  `id_kursi` int(11) NOT NULL,
  `id_layout` int(11) NOT NULL,
  `nomor_kursi` varchar(10) NOT NULL,
  `baris` int(11) NOT NULL,
  `kolom` int(11) NOT NULL,
  `status` enum('tersedia','tidak_tersedia') NOT NULL DEFAULT 'tersedia',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `posisi_x` int(11) DEFAULT NULL,
  `posisi_y` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kursi`
--

INSERT INTO `kursi` (`id_kursi`, `id_layout`, `nomor_kursi`, `baris`, `kolom`, `status`, `created_at`, `updated_at`, `posisi_x`, `posisi_y`) VALUES
(49, 1, 'A1', 0, 0, 'tersedia', '2025-06-14 05:12:13', '2025-06-14 05:12:13', 12, 69),
(52, 1, 'A2', 0, 0, 'tersedia', '2025-06-14 06:12:31', '2025-06-14 06:12:31', 36, 38),
(53, 1, 'A3', 0, 0, 'tersedia', '2025-06-16 13:56:05', '2025-06-22 07:33:41', 36, 52),
(56, 2, 'A012', 0, 0, 'tersedia', '2025-06-17 05:41:42', '2025-06-22 07:32:59', 64, 26),
(57, 2, 'A011', 0, 0, 'tersedia', '2025-06-17 05:41:46', '2025-06-17 05:41:46', 40, 34),
(58, 2, 'A013', 0, 0, 'tersedia', '2025-06-17 05:41:50', '2025-06-22 07:33:04', 72, 44);

-- --------------------------------------------------------

--
-- Table structure for table `layout_kursi`
--

CREATE TABLE `layout_kursi` (
  `id_layout` int(11) NOT NULL,
  `nama_layout` varchar(100) NOT NULL,
  `jumlah_baris` int(11) NOT NULL,
  `jumlah_kolom` int(11) NOT NULL,
  `gambar_layout` varchar(255) DEFAULT NULL,
  `keterangan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `layout_kursi`
--

INSERT INTO `layout_kursi` (`id_layout`, `nama_layout`, `jumlah_baris`, `jumlah_kolom`, `gambar_layout`, `keterangan`, `created_at`, `updated_at`) VALUES
(1, 'Bus Medium', 10, 4, 'layout_684afe9467bc07.03374807.webp', 'Layout kursi bus medium 2-2', '2025-06-12 07:11:30', '2025-06-12 16:21:40'),
(2, 'Standard', 0, 0, 'layout_6851000eeb41c9.95331048.png', NULL, '2025-06-17 05:41:34', '2025-06-17 05:41:34');

-- --------------------------------------------------------

--
-- Table structure for table `reservasi`
--

CREATE TABLE `reservasi` (
  `id_reservasi` int(11) NOT NULL,
  `kode_booking` varchar(20) NOT NULL,
  `id_jadwal` int(11) NOT NULL,
  `nama_pemesan` varchar(100) NOT NULL,
  `no_hp` varchar(20) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `alamat_jemput` text DEFAULT NULL,
  `status` enum('pending','dibayar','dibatalkan','selesai') NOT NULL DEFAULT 'pending',
  `status_pembayaran` enum('menunggu','diverifikasi','ditolak') NOT NULL DEFAULT 'menunggu',
  `total_harga` decimal(10,2) NOT NULL,
  `metode_pembayaran` varchar(50) DEFAULT NULL,
  `bukti_pembayaran` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Triggers `reservasi`
--
DELIMITER $$
CREATE TRIGGER `after_reservasi_delete` AFTER DELETE ON `reservasi` FOR EACH ROW BEGIN
        UPDATE jadwal j
        SET j.kursi_tersedia = (
            SELECT IFNULL(SUM(lk.jumlah_baris * lk.jumlah_kolom), 40) - 
                   IFNULL((
                       SELECT COUNT(*) 
                       FROM detail_reservasi_kursi drk
                       JOIN reservasi r ON drk.id_reservasi = r.id_reservasi
                       WHERE r.id_jadwal = OLD.id_jadwal AND r.status = 'dibayar'
                   ), 0)
            FROM layout_kursi lk
            LIMIT 1
        )
        WHERE j.id_jadwal = OLD.id_jadwal;
    END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_reservasi_insert` AFTER INSERT ON `reservasi` FOR EACH ROW BEGIN
        UPDATE jadwal j
        SET j.kursi_tersedia = (
            SELECT IFNULL(SUM(lk.jumlah_baris * lk.jumlah_kolom), 40) - 
                   IFNULL((
                       SELECT COUNT(*) 
                       FROM detail_reservasi_kursi drk
                       JOIN reservasi r ON drk.id_reservasi = r.id_reservasi
                       WHERE r.id_jadwal = NEW.id_jadwal AND r.status = 'dibayar'
                   ), 0)
            FROM layout_kursi lk
            LIMIT 1
        )
        WHERE j.id_jadwal = NEW.id_jadwal;
    END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_reservasi_update` AFTER UPDATE ON `reservasi` FOR EACH ROW BEGIN
        IF OLD.status != NEW.status OR OLD.id_jadwal != NEW.id_jadwal THEN
            -- Update kursi tersedia untuk jadwal lama (jika berubah)
            IF OLD.id_jadwal IS NOT NULL AND (OLD.id_jadwal != NEW.id_jadwal OR OLD.status = 'dibayar' OR NEW.status = 'dibayar') THEN
                UPDATE jadwal j
                SET j.kursi_tersedia = (
                    SELECT IFNULL(SUM(lk.jumlah_baris * lk.jumlah_kolom), 40) - 
                           IFNULL((
                               SELECT COUNT(*) 
                               FROM detail_reservasi_kursi drk
                               JOIN reservasi r ON drk.id_reservasi = r.id_reservasi
                               WHERE r.id_jadwal = OLD.id_jadwal AND r.status = 'dibayar'
                           ), 0)
                    FROM layout_kursi lk
                    LIMIT 1
                )
                WHERE j.id_jadwal = OLD.id_jadwal;
            END IF;
            
            -- Update kursi tersedia untuk jadwal baru (jika berubah)
            IF NEW.id_jadwal IS NOT NULL AND (OLD.id_jadwal != NEW.id_jadwal OR OLD.status = 'dibayar' OR NEW.status = 'dibayar') THEN
                UPDATE jadwal j
                SET j.kursi_tersedia = (
                    SELECT IFNULL(SUM(lk.jumlah_baris * lk.jumlah_kolom), 40) - 
                           IFNULL((
                               SELECT COUNT(*) 
                               FROM detail_reservasi_kursi drk
                               JOIN reservasi r ON drk.id_reservasi = r.id_reservasi
                               WHERE r.id_jadwal = NEW.id_jadwal AND r.status = 'dibayar'
                           ), 0)
                    FROM layout_kursi lk
                    LIMIT 1
                )
                WHERE j.id_jadwal = NEW.id_jadwal;
            END IF;
        END IF;
    END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `rute`
--

CREATE TABLE `rute` (
  `id_rute` int(11) NOT NULL,
  `asal_id` int(11) NOT NULL,
  `tujuan_id` int(11) NOT NULL,
  `jarak` decimal(10,2) DEFAULT NULL COMMENT 'dalam KM',
  `waktu_tempuh` time DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `booking_sequence`
--
ALTER TABLE `booking_sequence`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `detail_reservasi_kursi`
--
ALTER TABLE `detail_reservasi_kursi`
  ADD PRIMARY KEY (`id_detail`),
  ADD KEY `id_reservasi` (`id_reservasi`),
  ADD KEY `id_kursi` (`id_kursi`);

--
-- Indexes for table `jadwal`
--
ALTER TABLE `jadwal`
  ADD PRIMARY KEY (`id_jadwal`),
  ADD KEY `id_kota_asal` (`id_kota_asal`),
  ADD KEY `id_kota_tujuan` (`id_kota_tujuan`),
  ADD KEY `id_kecamatan_asal` (`id_kecamatan_asal`),
  ADD KEY `id_kecamatan_tujuan` (`id_kecamatan_tujuan`),
  ADD KEY `id_layout_kursi` (`id_layout_kursi`);

--
-- Indexes for table `kecamatan`
--
ALTER TABLE `kecamatan`
  ADD PRIMARY KEY (`id_kecamatan`),
  ADD KEY `id_kota` (`id_kota`);

--
-- Indexes for table `konfirmasi_pembayaran`
--
ALTER TABLE `konfirmasi_pembayaran`
  ADD PRIMARY KEY (`id`),
  ADD KEY `kode_booking` (`kode_booking`);

--
-- Indexes for table `kota`
--
ALTER TABLE `kota`
  ADD PRIMARY KEY (`id_kota`),
  ADD UNIQUE KEY `nama_kota` (`nama_kota`);

--
-- Indexes for table `kursi`
--
ALTER TABLE `kursi`
  ADD PRIMARY KEY (`id_kursi`),
  ADD KEY `id_layout` (`id_layout`);

--
-- Indexes for table `layout_kursi`
--
ALTER TABLE `layout_kursi`
  ADD PRIMARY KEY (`id_layout`);

--
-- Indexes for table `reservasi`
--
ALTER TABLE `reservasi`
  ADD PRIMARY KEY (`id_reservasi`),
  ADD UNIQUE KEY `kode_booking` (`kode_booking`),
  ADD KEY `id_jadwal` (`id_jadwal`);

--
-- Indexes for table `rute`
--
ALTER TABLE `rute`
  ADD PRIMARY KEY (`id_rute`),
  ADD KEY `asal_id` (`asal_id`),
  ADD KEY `tujuan_id` (`tujuan_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `booking_sequence`
--
ALTER TABLE `booking_sequence`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `detail_reservasi_kursi`
--
ALTER TABLE `detail_reservasi_kursi`
  MODIFY `id_detail` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=277;

--
-- AUTO_INCREMENT for table `jadwal`
--
ALTER TABLE `jadwal`
  MODIFY `id_jadwal` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `kecamatan`
--
ALTER TABLE `kecamatan`
  MODIFY `id_kecamatan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `konfirmasi_pembayaran`
--
ALTER TABLE `konfirmasi_pembayaran`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `kota`
--
ALTER TABLE `kota`
  MODIFY `id_kota` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `kursi`
--
ALTER TABLE `kursi`
  MODIFY `id_kursi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT for table `layout_kursi`
--
ALTER TABLE `layout_kursi`
  MODIFY `id_layout` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `reservasi`
--
ALTER TABLE `reservasi`
  MODIFY `id_reservasi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=255;

--
-- AUTO_INCREMENT for table `rute`
--
ALTER TABLE `rute`
  MODIFY `id_rute` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `jadwal`
--
ALTER TABLE `jadwal`
  ADD CONSTRAINT `jadwal_ibfk_1` FOREIGN KEY (`id_kota_asal`) REFERENCES `kota` (`id_kota`),
  ADD CONSTRAINT `jadwal_ibfk_2` FOREIGN KEY (`id_kota_tujuan`) REFERENCES `kota` (`id_kota`),
  ADD CONSTRAINT `jadwal_ibfk_3` FOREIGN KEY (`id_kecamatan_asal`) REFERENCES `kecamatan` (`id_kecamatan`),
  ADD CONSTRAINT `jadwal_ibfk_4` FOREIGN KEY (`id_kecamatan_tujuan`) REFERENCES `kecamatan` (`id_kecamatan`),
  ADD CONSTRAINT `jadwal_ibfk_5` FOREIGN KEY (`id_layout_kursi`) REFERENCES `layout_kursi` (`id_layout`) ON UPDATE CASCADE;

--
-- Constraints for table `rute`
--
ALTER TABLE `rute`
  ADD CONSTRAINT `rute_ibfk_1` FOREIGN KEY (`asal_id`) REFERENCES `kota` (`id_kota`),
  ADD CONSTRAINT `rute_ibfk_2` FOREIGN KEY (`tujuan_id`) REFERENCES `kota` (`id_kota`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

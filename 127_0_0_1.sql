-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Anamakine: 127.0.0.1
-- Üretim Zamanı: 01 Ağu 2025, 07:04:30
-- Sunucu sürümü: 10.4.32-MariaDB
-- PHP Sürümü: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Veritabanı: `konya_belediye`
--
CREATE DATABASE IF NOT EXISTS `konya_belediye` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_turkish_ci;
USE `konya_belediye`;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `firmalar`
--

CREATE TABLE `firmalar` (
  `id` int(11) NOT NULL,
  `firma_adi` varchar(100) NOT NULL,
  `toplam_borc` decimal(10,2) NOT NULL,
  `toplam_taksit` int(11) NOT NULL,
  `baslama_tarihi` date NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `sehir` varchar(100) NOT NULL,
  `telefon` varchar(20) DEFAULT NULL,
  `kalan_borc` decimal(15,2) DEFAULT 0.00,
  `taksit_sayisi` int(11) DEFAULT 0,
  `baslangic_tarihi` date DEFAULT NULL,
  `aylik_odeme` decimal(15,2) DEFAULT 0.00,
  `durum` enum('aktif','tamamlandi','gecikme') DEFAULT 'aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

--
-- Tablo döküm verisi `firmalar`
--

INSERT INTO `firmalar` (`id`, `firma_adi`, `toplam_borc`, `toplam_taksit`, `baslama_tarihi`, `is_active`, `sehir`, `telefon`, `kalan_borc`, `taksit_sayisi`, `baslangic_tarihi`, `aylik_odeme`, `durum`, `created_at`) VALUES
(1, 'Akgün İnşaat Ltd. Şti.', 1250000.00, 0, '0000-00-00', 1, 'Konya', '0332 220 15 45', 25049.97, 21, '2023-08-15', 60000.00, 'aktif', '2025-07-28 13:34:39'),
(2, 'Beyaz Teknoloji A.Ş.', 980000.00, 0, '0000-00-00', 1, 'Ankara', '0312 485 67 89', 245000.00, 24, '2023-05-10', 40833.33, 'aktif', '2025-07-28 13:34:39'),
(3, 'Çelik Makine San. Tic.', 1580000.00, 0, '0000-00-00', 1, 'İstanbul', '0212 345 67 89', 1555000.00, 24, '2024-02-20', 65833.33, 'aktif', '2025-07-28 13:34:39'),
(4, 'Doğa Peyzaj Ltd.', 620000.00, 0, '0000-00-00', 1, 'Antalya', '0242 567 89 12', 165000.00, 36, '2022-10-05', 17500.00, 'aktif', '2025-07-28 13:34:39'),
(5, 'Ege Tekstil A.Ş.', 2100000.00, 0, '0000-00-00', 1, 'İzmir', '0232 123 45 67', 1050000.00, 24, '2023-09-01', 87500.00, 'aktif', '2025-07-28 13:34:39'),
(6, 'Fırat Gıda San.', 750000.00, 0, '0000-00-00', 1, 'Konya', '0332 678 90 23', 375000.00, 24, '2023-12-01', 31250.00, 'aktif', '2025-07-28 13:34:39'),
(7, 'Güneş Enerji Ltd. Şti.', 1350000.00, 0, '0000-00-00', 1, 'Denizli', '0258 234 56 78', 540000.00, 24, '2023-07-15', 56250.00, 'aktif', '2025-07-28 13:34:39'),
(8, 'Hızlı Lojistik A.Ş.', 890000.00, 0, '0000-00-00', 1, 'Bursa', '0224 789 01 34', 852917.00, 24, '2024-01-10', 37083.33, 'aktif', '2025-07-28 13:34:39'),
(9, 'İklimlendirme Sistemleri', 620000.00, 0, '0000-00-00', 1, 'Adana', '0322 456 78 90', 0.00, 24, '2022-08-20', 25833.33, 'tamamlandi', '2025-07-28 13:34:39'),
(10, 'Jeotermal Enerji Ltd.', 1750000.00, 0, '0000-00-00', 1, 'Kayseri', '0352 345 67 89', 875000.00, 24, '2023-06-01', 72916.67, 'aktif', '2025-07-28 13:34:39'),
(11, 'Karadeniz Balık Üretim', 480000.00, 0, '0000-00-00', 1, 'Trabzon', '0462 567 89 01', 160000.00, 24, '2023-03-15', 20000.00, 'aktif', '2025-07-28 13:34:39'),
(33, 'Mehmet', 650000.00, 0, '0000-00-00', 1, 'Kastamonu', '05001234501', 585000.00, 10, '2025-07-31', 65000.00, 'aktif', '2025-07-31 06:01:34'),
(34, 'Sadasdas', 100000.00, 0, '0000-00-00', 1, 'Konya', '', 100000.00, 10, '2025-07-31', 10000.00, 'aktif', '2025-07-31 07:30:16');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `taksitler`
--

CREATE TABLE `taksitler` (
  `id` int(11) NOT NULL,
  `firma_id` int(11) NOT NULL,
  `tutar` decimal(15,2) NOT NULL,
  `vade_tarihi` date NOT NULL,
  `odeme_tarihi` date DEFAULT NULL,
  `durum` enum('bekliyor','odendi','gecikme') DEFAULT 'bekliyor',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

--
-- Tablo döküm verisi `taksitler`
--

INSERT INTO `taksitler` (`id`, `firma_id`, `tutar`, `vade_tarihi`, `odeme_tarihi`, `durum`, `created_at`) VALUES
(1, 1, 25.00, '2025-07-28', NULL, 'bekliyor', '2025-07-28 13:37:10'),
(3, 1, 25000.00, '2025-07-28', NULL, 'bekliyor', '2025-07-28 13:37:44'),
(4, 1, 60000.00, '2025-07-28', '2025-07-28', 'odendi', '2025-07-28 13:37:54'),
(5, 1, 950.03, '2025-07-28', '2025-07-28', 'odendi', '2025-07-28 13:43:48'),
(6, 1, 60000.00, '2025-07-28', '2025-07-28', 'odendi', '2025-07-28 14:01:02'),
(7, 1, 7900.00, '2025-07-28', '2025-07-28', 'odendi', '2025-07-28 14:01:59'),
(8, 1, 1100.00, '2025-07-28', '2025-07-28', 'odendi', '2025-07-28 14:02:53'),
(9, 1, 70000.00, '2025-07-29', '2025-07-29', 'odendi', '2025-07-29 05:30:26'),
(12, 3, 25000.00, '2025-07-29', '2025-07-29', 'odendi', '2025-07-29 08:09:28'),
(16, 8, 37083.00, '2025-07-29', '2025-07-29', 'odendi', '2025-07-29 08:58:54'),
(18, 1, 1.00, '2025-07-29', '2025-07-29', 'odendi', '2025-07-29 10:44:30'),
(19, 1, 1.00, '2025-07-29', '2025-07-29', 'odendi', '2025-07-29 10:46:45'),
(20, 1, 399998.00, '2025-07-29', '2025-07-29', 'odendi', '2025-07-29 10:54:22'),
(24, 2, 40833.33, '2025-07-30', NULL, 'bekliyor', '2025-07-30 12:07:52'),
(50, 4, 17500.00, '2025-07-30', '2025-07-30', 'odendi', '2025-07-30 13:01:27'),
(51, 4, 17500.00, '2025-07-30', '2025-07-30', 'odendi', '2025-07-30 13:01:47'),
(154, 33, 65000.00, '2025-07-31', NULL, 'bekliyor', '2025-07-31 06:01:34'),
(155, 33, 65000.00, '2025-08-30', NULL, 'bekliyor', '2025-07-31 06:01:34'),
(156, 33, 65000.00, '2025-09-29', NULL, 'bekliyor', '2025-07-31 06:01:34'),
(157, 33, 65000.00, '2025-10-29', NULL, 'bekliyor', '2025-07-31 06:01:34'),
(158, 33, 65000.00, '2025-11-28', NULL, 'bekliyor', '2025-07-31 06:01:34'),
(159, 33, 65000.00, '2025-12-28', NULL, 'bekliyor', '2025-07-31 06:01:34'),
(160, 33, 65000.00, '2026-01-27', NULL, 'bekliyor', '2025-07-31 06:01:34'),
(161, 33, 65000.00, '2026-02-26', NULL, 'bekliyor', '2025-07-31 06:01:34'),
(162, 33, 65000.00, '2026-03-28', NULL, 'bekliyor', '2025-07-31 06:01:34'),
(163, 33, 65000.00, '2026-04-27', NULL, 'bekliyor', '2025-07-31 06:01:34'),
(164, 33, 65000.00, '2025-07-31', '2025-07-31', 'odendi', '2025-07-31 06:01:42'),
(165, 34, 10000.00, '2025-07-31', NULL, 'bekliyor', '2025-07-31 07:30:16'),
(166, 34, 10000.00, '2025-08-30', NULL, 'bekliyor', '2025-07-31 07:30:16'),
(167, 34, 10000.00, '2025-09-29', NULL, 'bekliyor', '2025-07-31 07:30:16'),
(168, 34, 10000.00, '2025-10-29', NULL, 'bekliyor', '2025-07-31 07:30:16'),
(169, 34, 10000.00, '2025-11-28', NULL, 'bekliyor', '2025-07-31 07:30:16'),
(170, 34, 10000.00, '2025-12-28', NULL, 'bekliyor', '2025-07-31 07:30:16'),
(171, 34, 10000.00, '2026-01-27', NULL, 'bekliyor', '2025-07-31 07:30:16'),
(172, 34, 10000.00, '2026-02-26', NULL, 'bekliyor', '2025-07-31 07:30:16'),
(173, 34, 10000.00, '2026-03-28', NULL, 'bekliyor', '2025-07-31 07:30:16'),
(174, 34, 10000.00, '2026-04-27', NULL, 'bekliyor', '2025-07-31 07:30:16');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `status` enum('onaylandi','onay_bekliyor','onaylanmadi') NOT NULL DEFAULT 'onay_bekliyor',
  `role` enum('admin','user') NOT NULL DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

--
-- Tablo döküm verisi `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `status`, `role`, `created_at`, `updated_at`) VALUES
(1, 'admin', '2025', 'onaylandi', 'admin', '2025-07-28 06:20:49', '2025-07-28 06:20:49'),
(3, 'omer', '2005', 'onaylandi', 'user', '2025-07-28 06:44:12', '2025-07-29 12:04:39'),
(18, 'belediye', '2025', 'onaylandi', 'user', '2025-07-29 12:19:11', '2025-07-30 07:29:45');

--
-- Dökümü yapılmış tablolar için indeksler
--

--
-- Tablo için indeksler `firmalar`
--
ALTER TABLE `firmalar`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `taksitler`
--
ALTER TABLE `taksitler`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_taksit_firma` (`firma_id`);

--
-- Tablo için indeksler `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);
ALTER TABLE `users` ADD FULLTEXT KEY `username_2` (`username`);

--
-- Dökümü yapılmış tablolar için AUTO_INCREMENT değeri
--

--
-- Tablo için AUTO_INCREMENT değeri `firmalar`
--
ALTER TABLE `firmalar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- Tablo için AUTO_INCREMENT değeri `taksitler`
--
ALTER TABLE `taksitler`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=286;

--
-- Tablo için AUTO_INCREMENT değeri `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;
--
-- Veritabanı: `phpmyadmin`
--
CREATE DATABASE IF NOT EXISTS `phpmyadmin` DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;
USE `phpmyadmin`;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `pma__bookmark`
--

CREATE TABLE `pma__bookmark` (
  `id` int(10) UNSIGNED NOT NULL,
  `dbase` varchar(255) NOT NULL DEFAULT '',
  `user` varchar(255) NOT NULL DEFAULT '',
  `label` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
  `query` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Bookmarks';

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `pma__central_columns`
--

CREATE TABLE `pma__central_columns` (
  `db_name` varchar(64) NOT NULL,
  `col_name` varchar(64) NOT NULL,
  `col_type` varchar(64) NOT NULL,
  `col_length` text DEFAULT NULL,
  `col_collation` varchar(64) NOT NULL,
  `col_isNull` tinyint(1) NOT NULL,
  `col_extra` varchar(255) DEFAULT '',
  `col_default` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Central list of columns';

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `pma__column_info`
--

CREATE TABLE `pma__column_info` (
  `id` int(5) UNSIGNED NOT NULL,
  `db_name` varchar(64) NOT NULL DEFAULT '',
  `table_name` varchar(64) NOT NULL DEFAULT '',
  `column_name` varchar(64) NOT NULL DEFAULT '',
  `comment` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
  `mimetype` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
  `transformation` varchar(255) NOT NULL DEFAULT '',
  `transformation_options` varchar(255) NOT NULL DEFAULT '',
  `input_transformation` varchar(255) NOT NULL DEFAULT '',
  `input_transformation_options` varchar(255) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Column information for phpMyAdmin';

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `pma__designer_settings`
--

CREATE TABLE `pma__designer_settings` (
  `username` varchar(64) NOT NULL,
  `settings_data` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Settings related to Designer';

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `pma__export_templates`
--

CREATE TABLE `pma__export_templates` (
  `id` int(5) UNSIGNED NOT NULL,
  `username` varchar(64) NOT NULL,
  `export_type` varchar(10) NOT NULL,
  `template_name` varchar(64) NOT NULL,
  `template_data` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Saved export templates';

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `pma__favorite`
--

CREATE TABLE `pma__favorite` (
  `username` varchar(64) NOT NULL,
  `tables` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Favorite tables';

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `pma__history`
--

CREATE TABLE `pma__history` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `username` varchar(64) NOT NULL DEFAULT '',
  `db` varchar(64) NOT NULL DEFAULT '',
  `table` varchar(64) NOT NULL DEFAULT '',
  `timevalue` timestamp NOT NULL DEFAULT current_timestamp(),
  `sqlquery` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='SQL history for phpMyAdmin';

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `pma__navigationhiding`
--

CREATE TABLE `pma__navigationhiding` (
  `username` varchar(64) NOT NULL,
  `item_name` varchar(64) NOT NULL,
  `item_type` varchar(64) NOT NULL,
  `db_name` varchar(64) NOT NULL,
  `table_name` varchar(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Hidden items of navigation tree';

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `pma__pdf_pages`
--

CREATE TABLE `pma__pdf_pages` (
  `db_name` varchar(64) NOT NULL DEFAULT '',
  `page_nr` int(10) UNSIGNED NOT NULL,
  `page_descr` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='PDF relation pages for phpMyAdmin';

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `pma__recent`
--

CREATE TABLE `pma__recent` (
  `username` varchar(64) NOT NULL,
  `tables` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Recently accessed tables';

--
-- Tablo döküm verisi `pma__recent`
--

INSERT INTO `pma__recent` (`username`, `tables`) VALUES
('root', '[{\"db\":\"konya_belediye\",\"table\":\"users\"},{\"db\":\"konya_belediye\",\"table\":\"firmalar\"},{\"db\":\"konya_belediye\",\"table\":\"taksitler\"},{\"db\":\"phpmyadmin\",\"table\":\"pma__users\"},{\"db\":\"kullanici_db\",\"table\":\"kullanicilar\"}]');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `pma__relation`
--

CREATE TABLE `pma__relation` (
  `master_db` varchar(64) NOT NULL DEFAULT '',
  `master_table` varchar(64) NOT NULL DEFAULT '',
  `master_field` varchar(64) NOT NULL DEFAULT '',
  `foreign_db` varchar(64) NOT NULL DEFAULT '',
  `foreign_table` varchar(64) NOT NULL DEFAULT '',
  `foreign_field` varchar(64) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Relation table';

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `pma__savedsearches`
--

CREATE TABLE `pma__savedsearches` (
  `id` int(5) UNSIGNED NOT NULL,
  `username` varchar(64) NOT NULL DEFAULT '',
  `db_name` varchar(64) NOT NULL DEFAULT '',
  `search_name` varchar(64) NOT NULL DEFAULT '',
  `search_data` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Saved searches';

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `pma__table_coords`
--

CREATE TABLE `pma__table_coords` (
  `db_name` varchar(64) NOT NULL DEFAULT '',
  `table_name` varchar(64) NOT NULL DEFAULT '',
  `pdf_page_number` int(11) NOT NULL DEFAULT 0,
  `x` float UNSIGNED NOT NULL DEFAULT 0,
  `y` float UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Table coordinates for phpMyAdmin PDF output';

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `pma__table_info`
--

CREATE TABLE `pma__table_info` (
  `db_name` varchar(64) NOT NULL DEFAULT '',
  `table_name` varchar(64) NOT NULL DEFAULT '',
  `display_field` varchar(64) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Table information for phpMyAdmin';

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `pma__table_uiprefs`
--

CREATE TABLE `pma__table_uiprefs` (
  `username` varchar(64) NOT NULL,
  `db_name` varchar(64) NOT NULL,
  `table_name` varchar(64) NOT NULL,
  `prefs` text NOT NULL,
  `last_update` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Tables'' UI preferences';

--
-- Tablo döküm verisi `pma__table_uiprefs`
--

INSERT INTO `pma__table_uiprefs` (`username`, `db_name`, `table_name`, `prefs`, `last_update`) VALUES
('root', 'konya_belediye', 'users', '{\"CREATE_TIME\":\"2025-07-28 14:32:01\",\"col_order\":[0,1,2,3,4,5,6],\"col_visib\":[1,1,1,1,1,1,1],\"sorted_col\":\"`users`.`id` ASC\"}', '2025-07-28 13:58:41');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `pma__tracking`
--

CREATE TABLE `pma__tracking` (
  `db_name` varchar(64) NOT NULL,
  `table_name` varchar(64) NOT NULL,
  `version` int(10) UNSIGNED NOT NULL,
  `date_created` datetime NOT NULL,
  `date_updated` datetime NOT NULL,
  `schema_snapshot` text NOT NULL,
  `schema_sql` text DEFAULT NULL,
  `data_sql` longtext DEFAULT NULL,
  `tracking` set('UPDATE','REPLACE','INSERT','DELETE','TRUNCATE','CREATE DATABASE','ALTER DATABASE','DROP DATABASE','CREATE TABLE','ALTER TABLE','RENAME TABLE','DROP TABLE','CREATE INDEX','DROP INDEX','CREATE VIEW','ALTER VIEW','DROP VIEW') DEFAULT NULL,
  `tracking_active` int(1) UNSIGNED NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Database changes tracking for phpMyAdmin';

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `pma__userconfig`
--

CREATE TABLE `pma__userconfig` (
  `username` varchar(64) NOT NULL,
  `timevalue` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `config_data` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='User preferences storage for phpMyAdmin';

--
-- Tablo döküm verisi `pma__userconfig`
--

INSERT INTO `pma__userconfig` (`username`, `timevalue`, `config_data`) VALUES
('root', '2025-07-29 05:09:10', '{\"Console\\/Mode\":\"collapse\",\"lang\":\"tr\"}');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `pma__usergroups`
--

CREATE TABLE `pma__usergroups` (
  `usergroup` varchar(64) NOT NULL,
  `tab` varchar(64) NOT NULL,
  `allowed` enum('Y','N') NOT NULL DEFAULT 'N'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='User groups with configured menu items';

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `pma__users`
--

CREATE TABLE `pma__users` (
  `username` varchar(64) NOT NULL,
  `usergroup` varchar(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Users and their assignments to user groups';

--
-- Dökümü yapılmış tablolar için indeksler
--

--
-- Tablo için indeksler `pma__bookmark`
--
ALTER TABLE `pma__bookmark`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `pma__central_columns`
--
ALTER TABLE `pma__central_columns`
  ADD PRIMARY KEY (`db_name`,`col_name`);

--
-- Tablo için indeksler `pma__column_info`
--
ALTER TABLE `pma__column_info`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `db_name` (`db_name`,`table_name`,`column_name`);

--
-- Tablo için indeksler `pma__designer_settings`
--
ALTER TABLE `pma__designer_settings`
  ADD PRIMARY KEY (`username`);

--
-- Tablo için indeksler `pma__export_templates`
--
ALTER TABLE `pma__export_templates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `u_user_type_template` (`username`,`export_type`,`template_name`);

--
-- Tablo için indeksler `pma__favorite`
--
ALTER TABLE `pma__favorite`
  ADD PRIMARY KEY (`username`);

--
-- Tablo için indeksler `pma__history`
--
ALTER TABLE `pma__history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `username` (`username`,`db`,`table`,`timevalue`);

--
-- Tablo için indeksler `pma__navigationhiding`
--
ALTER TABLE `pma__navigationhiding`
  ADD PRIMARY KEY (`username`,`item_name`,`item_type`,`db_name`,`table_name`);

--
-- Tablo için indeksler `pma__pdf_pages`
--
ALTER TABLE `pma__pdf_pages`
  ADD PRIMARY KEY (`page_nr`),
  ADD KEY `db_name` (`db_name`);

--
-- Tablo için indeksler `pma__recent`
--
ALTER TABLE `pma__recent`
  ADD PRIMARY KEY (`username`);

--
-- Tablo için indeksler `pma__relation`
--
ALTER TABLE `pma__relation`
  ADD PRIMARY KEY (`master_db`,`master_table`,`master_field`),
  ADD KEY `foreign_field` (`foreign_db`,`foreign_table`);

--
-- Tablo için indeksler `pma__savedsearches`
--
ALTER TABLE `pma__savedsearches`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `u_savedsearches_username_dbname` (`username`,`db_name`,`search_name`);

--
-- Tablo için indeksler `pma__table_coords`
--
ALTER TABLE `pma__table_coords`
  ADD PRIMARY KEY (`db_name`,`table_name`,`pdf_page_number`);

--
-- Tablo için indeksler `pma__table_info`
--
ALTER TABLE `pma__table_info`
  ADD PRIMARY KEY (`db_name`,`table_name`);

--
-- Tablo için indeksler `pma__table_uiprefs`
--
ALTER TABLE `pma__table_uiprefs`
  ADD PRIMARY KEY (`username`,`db_name`,`table_name`);

--
-- Tablo için indeksler `pma__tracking`
--
ALTER TABLE `pma__tracking`
  ADD PRIMARY KEY (`db_name`,`table_name`,`version`);

--
-- Tablo için indeksler `pma__userconfig`
--
ALTER TABLE `pma__userconfig`
  ADD PRIMARY KEY (`username`);

--
-- Tablo için indeksler `pma__usergroups`
--
ALTER TABLE `pma__usergroups`
  ADD PRIMARY KEY (`usergroup`,`tab`,`allowed`);

--
-- Tablo için indeksler `pma__users`
--
ALTER TABLE `pma__users`
  ADD PRIMARY KEY (`username`,`usergroup`);

--
-- Dökümü yapılmış tablolar için AUTO_INCREMENT değeri
--

--
-- Tablo için AUTO_INCREMENT değeri `pma__bookmark`
--
ALTER TABLE `pma__bookmark`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `pma__column_info`
--
ALTER TABLE `pma__column_info`
  MODIFY `id` int(5) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `pma__export_templates`
--
ALTER TABLE `pma__export_templates`
  MODIFY `id` int(5) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `pma__history`
--
ALTER TABLE `pma__history`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `pma__pdf_pages`
--
ALTER TABLE `pma__pdf_pages`
  MODIFY `page_nr` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `pma__savedsearches`
--
ALTER TABLE `pma__savedsearches`
  MODIFY `id` int(5) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- Veritabanı: `test`
--
CREATE DATABASE IF NOT EXISTS `test` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
USE `test`;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

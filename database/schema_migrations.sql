-- ==========================================
-- Database Schema Dump from Migrations
-- Generated at: 2026-09-03 05:07:27
-- ==========================================

SET FOREIGN_KEY_CHECKS=0;

-- ------------------------------------------
-- Migration: 0001_01_01_000001_create_cache_table.php
-- ------------------------------------------
create table `cache` (`key` varchar(255) not null, `value` mediumtext not null, `expiration` bigint not null, primary key (`key`));
alter table `cache` add index `cache_expiration_index`(`expiration`);
create table `cache_locks` (`key` varchar(255) not null, `owner` varchar(255) not null, `expiration` bigint not null, primary key (`key`));
alter table `cache_locks` add index `cache_locks_expiration_index`(`expiration`);

-- ------------------------------------------
-- Migration: 0001_01_01_000002_create_jobs_table.php
-- ------------------------------------------
create table `jobs` (`id` bigint unsigned not null auto_increment primary key, `queue` varchar(255) not null, `payload` longtext not null, `attempts` smallint unsigned not null, `reserved_at` int unsigned null, `available_at` int unsigned not null, `created_at` int unsigned not null);
alter table `jobs` add index `jobs_queue_index`(`queue`);
create table `job_batches` (`id` varchar(255) not null, `name` varchar(255) not null, `total_jobs` int not null, `pending_jobs` int not null, `failed_jobs` int not null, `failed_job_ids` longtext not null, `options` mediumtext null, `cancelled_at` int null, `created_at` int not null, `finished_at` int null, primary key (`id`));
create table `failed_jobs` (`id` bigint unsigned not null auto_increment primary key, `uuid` varchar(255) not null, `connection` varchar(255) not null, `queue` varchar(255) not null, `payload` longtext not null, `exception` longtext not null, `failed_at` timestamp not null default CURRENT_TIMESTAMP);
alter table `failed_jobs` add index `failed_jobs_connection_queue_failed_at_index`(`connection`, `queue`, `failed_at`);
alter table `failed_jobs` add unique `failed_jobs_uuid_unique`(`uuid`);

-- ------------------------------------------
-- Migration: 2026_08_23_000001_create_users_table.php
-- ------------------------------------------
create table `users` (`id` bigint unsigned not null auto_increment primary key, `name` varchar(255) not null, `username` varchar(255) not null, `email` varchar(255) not null, `email_verified_at` timestamp null, `password` varchar(255) not null, `role` enum('ketua', 'pengurus', 'petugas', 'anggota') not null, `address` text null, `remember_token` varchar(100) null, `created_at` timestamp null, `updated_at` timestamp null);
alter table `users` add unique `users_username_unique`(`username`);
alter table `users` add unique `users_email_unique`(`email`);

-- ------------------------------------------
-- Migration: 2026_08_24_000001_create_setoran_koperasi_table.php
-- ------------------------------------------
create table `setoran_koperasi` (`id` varchar(36) not null, `user_id` bigint unsigned not null, `jenis` enum('PEMASUKAN', 'PENGELUARAN') not null, `jumlah` decimal(12, 2) not null, `status` enum('PENDING', 'PROSES', 'SELESAI', 'BATAL') not null default 'PENDING', `label` enum('SHU', 'POKOK', 'TIPPING', 'SUKARELA') not null, `catatan` text null, `created_at` timestamp null, `updated_at` timestamp null, primary key (`id`));
alter table `setoran_koperasi` add constraint `fk_setoran_user` foreign key (`user_id`) references `users` (`id`) on delete restrict;
alter table `setoran_koperasi` add index `idx_setoran_user_id`(`user_id`);

-- ------------------------------------------
-- Migration: 2026_08_24_000002_create_detail_pengangkutan_table.php
-- ------------------------------------------
create table `detail_pengangkutan` (`id` varchar(36) not null, `user_id` bigint unsigned not null, `jadwal_angkut` datetime not null, `total_organik` decimal(8, 2) not null default '0', `total_anorganik` decimal(8, 2) not null default '0', `kecamatan` varchar(100) null, `desa` varchar(100) null, `dusun` varchar(100) null, `rw` varchar(5) null, `rt` varchar(5) null, `created_at` timestamp null, `updated_at` timestamp null, `alamat` text not null, primary key (`id`));
alter table `detail_pengangkutan` add constraint `fk_pengangkutan_user` foreign key (`user_id`) references `users` (`id`) on delete restrict;
alter table `detail_pengangkutan` add index `idx_pengangkutan_user_id`(`user_id`);

-- ------------------------------------------
-- Migration: 2026_08_27_000002_create_member_categories_table.php
-- ------------------------------------------
create table `member_categories` (`id` bigint unsigned not null auto_increment primary key, `name` varchar(100) not null);

-- ------------------------------------------
-- Migration: 2026_08_27_000003_create_trash_categories_table.php
-- ------------------------------------------
create table `trash_categories` (`id` bigint unsigned not null auto_increment primary key, `name` varchar(100) not null, `price_sorted` decimal(10, 2) not null default '0', `price_unsorted` decimal(10, 2) not null default '0', `is_active` tinyint(1) not null default '1', `created_at` timestamp null, `updated_at` timestamp null);

-- ------------------------------------------
-- Migration: 2026_08_27_000004_create_finance_categories_table.php
-- ------------------------------------------
create table `finance_categories` (`id` bigint unsigned not null auto_increment primary key, `name` varchar(100) not null, `type` enum('income', 'expense') not null, `group_type` enum('simpanan_pokok', 'simpanan_wajib', 'tipping_fee', 'operasional', 'lainnya') not null);

-- ------------------------------------------
-- Migration: 2026_08_27_000005_create_members_table.php
-- ------------------------------------------
create table `members` (`id` bigint unsigned not null auto_increment primary key, `user_id` bigint unsigned not null, `member_category_id` bigint unsigned not null, `member_code` varchar(50) not null, `name` varchar(255) not null, `address` text null, `phone` varchar(20) null, `status` enum('aktif', 'nonaktif', 'suspend') not null default 'aktif', `join_date` date not null, `created_at` timestamp null, `updated_at` timestamp null);
alter table `members` add constraint `members_user_id_foreign` foreign key (`user_id`) references `users` (`id`) on delete cascade;
alter table `members` add constraint `members_member_category_id_foreign` foreign key (`member_category_id`) references `member_categories` (`id`) on delete restrict;
alter table `members` add unique `members_member_code_unique`(`member_code`);

-- ------------------------------------------
-- Migration: 2026_08_27_000006_create_transactions_table.php
-- ------------------------------------------
create table `transactions` (`id` bigint unsigned not null auto_increment primary key, `transaction_code` varchar(100) not null, `member_id` bigint unsigned null, `category_id` bigint unsigned not null, `type` enum('income', 'expense') not null, `amount` decimal(15, 2) not null, `description` varchar(255) null, `payment_method` enum('tunai', 'transfer', 'sampah') not null, `status` enum('pending', 'berhasil', 'gagal') not null default 'pending', `transaction_date` timestamp not null, `handled_by` bigint unsigned not null, `created_at` timestamp null, `updated_at` timestamp null);
alter table `transactions` add constraint `transactions_member_id_foreign` foreign key (`member_id`) references `members` (`id`) on delete set null;
alter table `transactions` add constraint `transactions_category_id_foreign` foreign key (`category_id`) references `finance_categories` (`id`) on delete restrict;
alter table `transactions` add constraint `transactions_handled_by_foreign` foreign key (`handled_by`) references `users` (`id`) on delete restrict;
alter table `transactions` add unique `transactions_transaction_code_unique`(`transaction_code`);

-- ------------------------------------------
-- Migration: 2026_08_27_000007_create_pickups_table.php
-- ------------------------------------------
create table `pickups` (`id` bigint unsigned not null auto_increment primary key, `officer_id` bigint unsigned null, `member_id` bigint unsigned not null, `is_sorted` tinyint(1) not null default '0', `scheduled_at` timestamp null, `completed_at` timestamp null, `status` enum('menunggu', 'selesai', 'batal') not null default 'menunggu', `notes` text null, `created_at` timestamp null, `updated_at` timestamp null);
alter table `pickups` add constraint `pickups_officer_id_foreign` foreign key (`officer_id`) references `users` (`id`) on delete set null;
alter table `pickups` add constraint `pickups_member_id_foreign` foreign key (`member_id`) references `members` (`id`) on delete restrict;

-- ------------------------------------------
-- Migration: 2026_08_27_000008_create_reports_table.php
-- ------------------------------------------
create table `reports` (`id` bigint unsigned not null auto_increment primary key, `title` varchar(255) not null, `type` enum('saldo', 'laba_rugi', 'simpanan', 'operasional') not null, `period_start` date not null, `period_end` date not null, `file_path` varchar(255) not null, `status` enum('review', 'finalized') not null default 'review', `created_by` bigint unsigned not null, `created_at` timestamp not null);
alter table `reports` add constraint `reports_created_by_foreign` foreign key (`created_by`) references `users` (`id`) on delete restrict;

-- ------------------------------------------
-- Migration: 2026_08_27_000009_create_pickup_items_table.php
-- ------------------------------------------
create table `pickup_items` (`id` bigint unsigned not null auto_increment primary key, `pickup_id` bigint unsigned null, `category_id` bigint unsigned not null, `weight_kg` decimal(8, 2) not null, `total_value` decimal(15, 2) not null, `deposit_date` timestamp not null default CURRENT_TIMESTAMP, `transaction_id` bigint unsigned null);
alter table `pickup_items` add constraint `pickup_items_pickup_id_foreign` foreign key (`pickup_id`) references `pickups` (`id`) on delete cascade;
alter table `pickup_items` add constraint `pickup_items_category_id_foreign` foreign key (`category_id`) references `trash_categories` (`id`) on delete restrict;
alter table `pickup_items` add constraint `pickup_items_transaction_id_foreign` foreign key (`transaction_id`) references `transactions` (`id`) on delete set null;

-- ------------------------------------------
-- Migration: 2026_08_27_000010_create_shu_distributions_table.php
-- ------------------------------------------
create table `shu_distributions` (`id` bigint unsigned not null auto_increment primary key, `year` year not null, `total_shu` decimal(15, 2) not null, `reserve_amount` decimal(15, 2) not null, `distributed_amount` decimal(15, 2) not null, `recipient_count` int unsigned not null, `status` enum('draft', 'dibagikan') not null default 'draft', `distribution_date` date null, `handled_by` bigint unsigned not null);
alter table `shu_distributions` add constraint `shu_distributions_handled_by_foreign` foreign key (`handled_by`) references `users` (`id`) on delete restrict;
alter table `shu_distributions` add unique `shu_distributions_year_unique`(`year`);

-- ------------------------------------------
-- Migration: 2026_08_27_000011_create_shu_members_table.php
-- ------------------------------------------
create table `shu_members` (`id` bigint unsigned not null auto_increment primary key, `shu_distribution_id` bigint unsigned not null, `member_id` bigint unsigned not null, `simpanan_pokok_amount` decimal(15, 2) not null default '0', `simpanan_wajib_amount` decimal(15, 2) not null default '0', `participation_amount` decimal(15, 2) not null default '0', `total_shu` decimal(15, 2) not null default '0', `status` enum('menunggu', 'sudah_dibagikan') not null default 'menunggu', `paid_at` timestamp null, `transaction_id` bigint unsigned null);
alter table `shu_members` add constraint `shu_members_shu_distribution_id_foreign` foreign key (`shu_distribution_id`) references `shu_distributions` (`id`) on delete cascade;
alter table `shu_members` add constraint `shu_members_member_id_foreign` foreign key (`member_id`) references `members` (`id`) on delete restrict;
alter table `shu_members` add constraint `shu_members_transaction_id_foreign` foreign key (`transaction_id`) references `transactions` (`id`) on delete set null;

SET FOREIGN_KEY_CHECKS=1;

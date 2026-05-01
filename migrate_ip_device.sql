-- Migration: Add ip_address and device_fingerprint columns
-- Run with: mysql -h db -u root -p ujian_online < migrate_ip_device.sql

ALTER TABLE `jawaban_sEMENTARA` 
  ADD COLUMN IF NOT EXISTS `ip_address` varchar(45) DEFAULT NULL AFTER `updated_at`,
  ADD COLUMN IF NOT EXISTS `device_fingerprint` varchar(64) DEFAULT NULL AFTER `ip_address`;

ALTER TABLE `hasil_ujian` 
  ADD COLUMN IF NOT EXISTS `ip_address` varchar(45) DEFAULT NULL AFTER `detail_jawaban`,
  ADD COLUMN IF NOT EXISTS `device_fingerprint` varchar(64) DEFAULT NULL AFTER `ip_address`;

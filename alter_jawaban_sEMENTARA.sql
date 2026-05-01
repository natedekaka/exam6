-- Add IP Address and Device Fingerprint columns to jawaban_sEMENTARA table
ALTER TABLE `jawaban_sEMENTARA` 
ADD COLUMN `ip_address` varchar(45) DEFAULT NULL AFTER `updated_at`,
ADD COLUMN `device_fingerprint` varchar(64) DEFAULT NULL AFTER `ip_address`;

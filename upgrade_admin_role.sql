-- Add role column to admin_users table if not exists
ALTER TABLE `admin_users` ADD COLUMN IF NOT EXISTS `role` ENUM('super_admin', 'admin') DEFAULT 'admin';

-- Add created_at column if not exists
ALTER TABLE `admin_users` ADD COLUMN IF NOT EXISTS `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP;

-- Add last_login column if not exists
ALTER TABLE `admin_users` ADD COLUMN IF NOT EXISTS `last_login` DATETIME DEFAULT NULL;
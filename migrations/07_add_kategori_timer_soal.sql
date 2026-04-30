-- Migration: Add kategori and timer_soal columns to soal table
-- Run this to fix "Unknown column 'kategori' in field list" error

ALTER TABLE soal ADD COLUMN IF NOT EXISTS kategori VARCHAR(100) DEFAULT NULL AFTER poin;
ALTER TABLE soal ADD COLUMN IF NOT EXISTS timer_soal INT DEFAULT 0 AFTER kategori;
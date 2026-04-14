-- Migration: Add performance indexes
-- Run this to improve query performance for network users

-- Add index for faster exam listing
CREATE INDEX IF NOT EXISTS idx_ujian_status ON ujian(status, tgl_dibuat DESC);

-- Add index for faster question counting
CREATE INDEX IF NOT EXISTS idx_soal_ujian ON soal(id_ujian);

-- Add index for faster temporary answer lookup
CREATE INDEX IF NOT EXISTS idx_jawaban_temp_ujian_nis ON jawaban_sEMENTARA(id_ujian, nis);

-- Add index for faster violation logging
CREATE INDEX IF NOT EXISTS idx_violation_ujian_nis ON exam_violations(id_ujian, nis);
-- Fix exam_violations table to add AUTO_INCREMENT to id column
ALTER TABLE exam_violations 
MODIFY COLUMN `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY;

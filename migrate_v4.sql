-- Morning Pass v4 – Migration: Award class support
USE morning_pass;

DROP PROCEDURE IF EXISTS mp_migrate_v4;
DELIMITER $$
CREATE PROCEDURE mp_migrate_v4()
BEGIN
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'enrollments' AND COLUMN_NAME = 'is_award') THEN
        ALTER TABLE enrollments ADD COLUMN is_award   TINYINT  DEFAULT 0;
        ALTER TABLE enrollments ADD COLUMN award_date DATE     DEFAULT NULL;
    END IF;
END$$
DELIMITER ;
CALL mp_migrate_v4();
DROP PROCEDURE IF EXISTS mp_migrate_v4;

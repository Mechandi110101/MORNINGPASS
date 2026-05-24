-- Morning Pass v5 – Migration: Membership fields on students
USE morning_pass;

DROP PROCEDURE IF EXISTS mp_migrate_v5;
DELIMITER $$
CREATE PROCEDURE mp_migrate_v5()
BEGIN
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'students' AND COLUMN_NAME = 'membership_status') THEN
        ALTER TABLE students ADD COLUMN membership_status  VARCHAR(20) DEFAULT 'active';
        ALTER TABLE students ADD COLUMN membership_expires DATE        DEFAULT NULL;
    END IF;
END$$
DELIMITER ;
CALL mp_migrate_v5();
DROP PROCEDURE IF EXISTS mp_migrate_v5;

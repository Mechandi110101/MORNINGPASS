-- Morning Pass v3 – Migration (MySQL 8.0 compatible)
USE morning_pass;

DROP PROCEDURE IF EXISTS mp_migrate_v3;
DELIMITER $$
CREATE PROCEDURE mp_migrate_v3()
BEGIN
    -- time_slots: group status
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'time_slots' AND COLUMN_NAME = 'slot_status') THEN
        ALTER TABLE time_slots ADD COLUMN slot_status VARCHAR(20) NOT NULL DEFAULT 'active';
    END IF;

    -- enrollments: trial class support
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'enrollments' AND COLUMN_NAME = 'is_trial') THEN
        ALTER TABLE enrollments ADD COLUMN is_trial  TINYINT DEFAULT 0;
        ALTER TABLE enrollments ADD COLUMN trial_date DATE    DEFAULT NULL;
    END IF;

    -- professors: photo + availability
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'professors' AND COLUMN_NAME = 'photo') THEN
        ALTER TABLE professors ADD COLUMN photo        VARCHAR(255) DEFAULT '';
        ALTER TABLE professors ADD COLUMN availability TEXT;
    END IF;
END$$
DELIMITER ;
CALL mp_migrate_v3();
DROP PROCEDURE IF EXISTS mp_migrate_v3;

-- Fix JEAN color to teal (fits better with red/blue palette)
UPDATE professors SET color_hex = '#2a8a7a' WHERE name = 'JEAN' AND color_hex = '#d4a843';

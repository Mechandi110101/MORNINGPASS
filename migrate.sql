-- Morning Pass v2 – Migration (MySQL 8.0 compatible)
USE morning_pass;

-- ── 1. Programs ──────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS programs (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    name      VARCHAR(100) NOT NULL,
    color_hex VARCHAR(7)   DEFAULT '#B8232a',
    icon      VARCHAR(10)  DEFAULT '🎓',
    active    TINYINT      DEFAULT 1
);

INSERT INTO programs (id, name, color_hex, icon) VALUES
(1, 'Morning Pass',    '#B8232a', '🌅'),
(2, 'Academia',        '#27393f', '🏫'),
(3, 'Team Competition','#1a6a8a', '🏆')
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- ── 2. Add columns to time_slots (safe: skip if already exists) ──────────────
DROP PROCEDURE IF EXISTS mp_migrate;
DELIMITER $$
CREATE PROCEDURE mp_migrate()
BEGIN
    -- program_id
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'time_slots' AND COLUMN_NAME = 'program_id') THEN
        ALTER TABLE time_slots ADD COLUMN program_id INT NOT NULL DEFAULT 1 AFTER id;
    END IF;
    -- start_date
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'time_slots' AND COLUMN_NAME = 'start_date') THEN
        ALTER TABLE time_slots ADD COLUMN start_date DATE DEFAULT NULL;
    END IF;
    -- end_date
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'time_slots' AND COLUMN_NAME = 'end_date') THEN
        ALTER TABLE time_slots ADD COLUMN end_date DATE DEFAULT NULL;
    END IF;
    -- students: gender
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'students' AND COLUMN_NAME = 'gender') THEN
        ALTER TABLE students ADD COLUMN gender VARCHAR(1) DEFAULT '' AFTER name;
    END IF;
    -- students: category
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'students' AND COLUMN_NAME = 'category') THEN
        ALTER TABLE students ADD COLUMN category VARCHAR(50) DEFAULT '' AFTER gender;
    END IF;
    -- students: phone
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'students' AND COLUMN_NAME = 'phone') THEN
        ALTER TABLE students ADD COLUMN phone VARCHAR(20) DEFAULT '' AFTER category;
    END IF;
END$$
DELIMITER ;
CALL mp_migrate();
DROP PROCEDURE IF EXISTS mp_migrate;

-- ── 3. Enrollments table ─────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS enrollments (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    time_slot_id  INT         NOT NULL,
    student_id    INT         NOT NULL,
    enrolled_date DATE        NOT NULL DEFAULT (CURRENT_DATE),
    status        VARCHAR(20) DEFAULT 'active',
    notes         TEXT,
    created_at    TIMESTAMP   DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (time_slot_id) REFERENCES time_slots(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id)   REFERENCES students(id)   ON DELETE CASCADE,
    UNIQUE KEY uq_enrollment (time_slot_id, student_id)
);

-- ── 4. Migrate existing bookings → enrollments ───────────────────────────────
INSERT IGNORE INTO enrollments (time_slot_id, student_id, enrolled_date, status)
SELECT time_slot_id, student_id, MIN(week_start), 'active'
FROM   bookings
WHERE  status != 'cancelled'
GROUP  BY time_slot_id, student_id;

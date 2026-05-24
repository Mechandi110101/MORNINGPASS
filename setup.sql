-- Morning Pass - Database Setup
-- Run this once to create and seed the database

CREATE DATABASE IF NOT EXISTS morning_pass CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE morning_pass;

-- -----------------------------------------------
-- TABLES
-- -----------------------------------------------

CREATE TABLE IF NOT EXISTS professors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    color_hex VARCHAR(7) DEFAULT '#4a90d9',
    active TINYINT DEFAULT 1
);

CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    active TINYINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS time_slots (
    id INT AUTO_INCREMENT PRIMARY KEY,
    professor_id INT NOT NULL,
    day_of_week TINYINT NOT NULL COMMENT '1=Mon 2=Tue 3=Wed 4=Thu 5=Fri',
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    class_name VARCHAR(100) DEFAULT '',
    class_type VARCHAR(20) DEFAULT '' COMMENT 'MIXTO, FEM, MASC, CAT, STA',
    max_students INT DEFAULT 4,
    notes TEXT,
    active TINYINT DEFAULT 1,
    FOREIGN KEY (professor_id) REFERENCES professors(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    time_slot_id INT NOT NULL,
    student_id INT NOT NULL,
    week_start DATE NOT NULL COMMENT 'Monday of that week',
    status VARCHAR(20) DEFAULT 'confirmed' COMMENT 'confirmed, cancelled',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (time_slot_id) REFERENCES time_slots(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    UNIQUE KEY unique_booking (time_slot_id, student_id, week_start)
);

-- -----------------------------------------------
-- SEED: PROFESSORS
-- -----------------------------------------------

INSERT INTO professors (name, color_hex) VALUES
('JEAN',    '#d4a843'),
('JR',      '#4a7fc1'),
('ECHANDI', '#6db56d'),
('ALLEN',   '#c17a6d');

-- -----------------------------------------------
-- SEED: STUDENTS (from schedule image)
-- -----------------------------------------------

INSERT INTO students (name) VALUES
('ADRIANA QUIROS'),
('ALBERTO JIMENES'),
('ALEJANDRA LANG'),
('ALEJANDRO SALAZAR'),
('ALVARO RODRIGUEZ'),
('AN ABELTRAN'),
('ANA MANEMER'),
('ANDERSON QUESADA'),
('ANDREA SZLAK'),
('ANDRES FLORES'),
('ANKE PETCHEL'),
('BERNAL MORA'),
('BRENDA VARGAS'),
('CAROLINA CHACON'),
('CESAR BARRANTES'),
('CINTHIA SOLANO'),
('CLEMENCIA CRUZ'),
('DAN FILKER'),
('DANIEL DELBURGO'),
('DANNY MAYER'),
('DIANA BOTBOL'),
('DIANA CHAVERRI'),
('DIANA FISHMAN'),
('ESTEBAN OCONTRILLO'),
('FANNY'),
('FRANCISCO GONZALES'),
('GEOVANNY GARCIA'),
('GRACE ADAMS'),
('GRACIELA FUENTES'),
('HELE BRENES'),
('ILEANA MENDEZ'),
('ISAAC MENDEZ'),
('ISABELLA ARAGON'),
('JANINE SHNEIDER'),
('JASON LONG'),
('JENNI BRANCO'),
('JESUS RUIZ'),
('JOANNE PICCIOTO'),
('JOSE BARRANTES'),
('JUAN NASSAR'),
('KATHIA QUESADA'),
('KEYLA SALTER'),
('LAURA CASAFONT'),
('LEDA MENDEZ'),
('LEO BARRANTES'),
('LOURDES ICAZA'),
('LUCA FRUGONE'),
('LUIS DE AGUILAR'),
('MAITE ROCA'),
('MARCO BERMUDEZ'),
('MARCELA NAVARRO'),
('MARIA FERNANDA'),
('MARIANA ULLOA'),
('MARIANELA RAMOS'),
('NAIMA VINDAS'),
('NATALIA PORRAS'),
('NAZARENA CHONG'),
('NOELANY ARRIETA'),
('NURIA GUILLEN'),
('NURIT STEINKOLER'),
('RICARDO CORDERO'),
('RONALD SALAZAR'),
('ROSALBA MARTIGNETTY'),
('SANTIAGO RAMIREZ'),
('SHEYLA CORDERO'),
('SOFIA XIRINACHS'),
('STEPHANIE TORRES'),
('STEVEN BOLAÑOS'),
('STEVEN UMAÑA'),
('STTEFANY PADILLA'),
('TANYA LIPSYC'),
('TATIANA MARTI'),
('TAULIFURUITI'),
('VALENTINA VARGAS'),
('WILLIE FERNANDEZ'),
('BRYAN UREÑA'),
('CARLOS PEÑA'),
('JOAN SALAZAR'),
('JOANNE PICCIOTO'),
('ATZI');

-- -----------------------------------------------
-- SEED: TIME SLOTS
-- Extracted from the schedule image
-- day_of_week: 1=Mon, 2=Tue, 3=Wed, 4=Thu, 5=Fri
-- -----------------------------------------------

-- MONDAY (JEAN)
INSERT INTO time_slots (professor_id, day_of_week, start_time, end_time, class_name, class_type, max_students) VALUES
(1, 1, '06:00', '07:00', 'LUNES 6AM',      '',      4),
(1, 1, '09:00', '10:00', 'JEAN STA MIXTO', 'MIXTO', 4),
(1, 1, '10:00', '11:00', 'JEAN',           '',      4),
(1, 1, '11:00', '12:00', 'JEAN ABRIR GRUPO','',     4);

-- MONDAY (ECHANDI)
INSERT INTO time_slots (professor_id, day_of_week, start_time, end_time, class_name, class_type, max_students) VALUES
(3, 1, '10:00', '11:00', 'CAT 4TA ECHANDI', 'CAT',  4),
(3, 1, '11:00', '12:00', '3RA ECHANDI',     '',     4);

-- MONDAY (JR)
INSERT INTO time_slots (professor_id, day_of_week, start_time, end_time, class_name, class_type, max_students) VALUES
(2, 1, '11:30', '12:30', 'JR',              '',      4),
(2, 1, '12:30', '13:30', 'JR',              '',      4),
(2, 1, '13:00', '14:00', 'CAT 3RA MASC JR','MASC',  4),
(2, 1, '14:00', '15:00', 'JR',              '',      4);

-- MONDAY (ALLEN)
INSERT INTO time_slots (professor_id, day_of_week, start_time, end_time, class_name, class_type, max_students) VALUES
(4, 1, '13:00', '14:00', 'STA FEM ALLEN',  'FEM',   4);

-- TUESDAY (JR)
INSERT INTO time_slots (professor_id, day_of_week, start_time, end_time, class_name, class_type, max_students) VALUES
(2, 2, '06:00', '07:00', '6AM STA JR',     'STA',   4),
(2, 2, '11:00', '12:00', 'JR',             '',      4),
(2, 2, '12:00', '13:00', 'MD JR',          '',      4),
(2, 2, '13:00', '14:00', 'JR',             '',      4),
(2, 2, '14:00', '15:00', 'JR',             '',      4),
(2, 2, '15:00', '16:00', 'JR',             '',      4);

-- TUESDAY (JEAN)
INSERT INTO time_slots (professor_id, day_of_week, start_time, end_time, class_name, class_type, max_students) VALUES
(1, 2, '07:00', '08:00', '7AM CAT 4TA JEAN','CAT',  4),
(1, 2, '08:00', '09:00', '8AM STA JEAN',   'STA',   4),
(1, 2, '09:00', '10:00', '9AM STA JEAN',   'STA',   4),
(1, 2, '10:00', '11:00', '10AM CAT 5TA JEAN','CAT', 4),
(1, 2, '11:00', '12:00', '11AM JR',        '',      4);

-- WEDNESDAY (JEAN)
INSERT INTO time_slots (professor_id, day_of_week, start_time, end_time, class_name, class_type, max_students) VALUES
(1, 3, '07:00', '08:00', '7AM CAT 4TA JEAN','CAT',  4),
(1, 3, '09:30', '10:30', 'JEAN',           '',      4),
(1, 3, '10:30', '11:30', 'JEAN',           '',      4),
(1, 3, '11:30', '12:30', 'JEAN',           '',      4),
(1, 3, '12:30', '13:30', 'JEAN',           '',      4);

-- WEDNESDAY (JR)
INSERT INTO time_slots (professor_id, day_of_week, start_time, end_time, class_name, class_type, max_students) VALUES
(2, 3, '06:00', '07:00', '6AM JR',         '',      4),
(2, 3, '08:00', '09:00', '8AM JR',         '',      4),
(2, 3, '09:00', '10:00', '9AM JR',         '',      4),
(2, 3, '10:00', '11:00', 'CAT 5TA JR',     'CAT',   4),
(2, 3, '11:00', '12:00', 'JR',             '',      4),
(2, 3, '13:00', '14:00', '12 MD JR',       '',      4);

-- WEDNESDAY (ECHANDI)
INSERT INTO time_slots (professor_id, day_of_week, start_time, end_time, class_name, class_type, max_students) VALUES
(3, 3, '13:00', '14:00', 'CAT 3RA MASC ECHANDI','MASC', 4);

-- WEDNESDAY (ALLEN)
INSERT INTO time_slots (professor_id, day_of_week, start_time, end_time, class_name, class_type, max_students) VALUES
(4, 3, '13:00', '14:00', 'CAT 5TA FEM ALLEN','FEM',  4);

-- THURSDAY (JR)
INSERT INTO time_slots (professor_id, day_of_week, start_time, end_time, class_name, class_type, max_students) VALUES
(2, 4, '06:00', '07:00', '6AM A 7AM 4TA JR','CAT',  4),
(2, 4, '09:00', '10:00', '9AM 5TA JR',     '',      4),
(2, 4, '11:00', '12:00', 'JR',             '',      4),
(2, 4, '12:00', '13:00', 'MD JR',          '',      4),
(2, 4, '13:00', '14:00', 'JR',             '',      4),
(2, 4, '14:00', '15:00', 'JR',             '',      4);

-- THURSDAY (JEAN)
INSERT INTO time_slots (professor_id, day_of_week, start_time, end_time, class_name, class_type, max_students) VALUES
(1, 4, '09:00', '10:00', '9AM CAT 5TA JEAN','CAT',  4),
(1, 4, '10:00', '11:00', '10AM CAT 5TA JEAN','CAT', 4);

-- FRIDAY (JEAN)
INSERT INTO time_slots (professor_id, day_of_week, start_time, end_time, class_name, class_type, max_students) VALUES
(1, 5, '06:00', '07:00', '6AM A 7AM 4TA JEAN','CAT',4),
(1, 5, '07:00', '08:00', '7AM JEAN',       '',      4),
(1, 5, '08:00', '09:00', '8AM 5TA JEAN',   '',      4),
(1, 5, '09:00', '10:00', '9AM CAT 5TA JEAN','CAT',  4),
(1, 5, '10:00', '11:00', '10AM JR',        '',      4),
(1, 5, '15:00', '16:00', 'CAT 5TA FEM JEAN','FEM',  4);

-- FRIDAY (JR)
INSERT INTO time_slots (professor_id, day_of_week, start_time, end_time, class_name, class_type, max_students) VALUES
(2, 5, '10:00', '11:00', '10AM JR',        '',      4),
(2, 5, '11:00', '12:00', '11AM JR',        '',      4),
(2, 5, '12:00', '13:00', '12 MD JR',       '',      4),
(2, 5, '13:00', '14:00', 'JR',             '',      4),
(2, 5, '14:00', '15:00', 'JR',             '',      4);

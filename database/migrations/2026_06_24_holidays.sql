-- ============================================================================
-- Migration: holidays table (#8)
-- Date:      2026-06-24
--
-- Drives the "skip holidays" behaviour of the recurring-session generator and
-- can later back any non-teaching-day logic. Dates are unique.
-- Apply with:  mysql -u <user> -p <database> < this_file.sql
-- ============================================================================

CREATE TABLE IF NOT EXISTS holidays (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    holiday_date DATE         NOT NULL,
    description  VARCHAR(100) NOT NULL DEFAULT '',
    UNIQUE KEY uq_holiday_date (holiday_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Ghana public holidays (sample seed; adjust as needed).
INSERT IGNORE INTO holidays (holiday_date, description) VALUES
    ('2026-01-01', "New Year's Day"),
    ('2026-01-07', 'Constitution Day'),
    ('2026-03-06', 'Independence Day'),
    ('2026-04-03', 'Good Friday'),
    ('2026-04-06', 'Easter Monday'),
    ('2026-05-01', 'May Day'),
    ('2026-07-01', 'Republic Day'),
    ('2026-08-04', 'Founders'' Day'),
    ('2026-09-21', 'Kwame Nkrumah Memorial Day'),
    ('2026-12-25', 'Christmas Day'),
    ('2026-12-26', 'Boxing Day');

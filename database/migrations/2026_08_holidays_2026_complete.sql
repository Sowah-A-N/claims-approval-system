-- Complete the Ghana public-holiday calendar for 2026.
-- The holidays table (holiday_date is UNIQUE) already carries the fixed-date and
-- Easter holidays; this adds the ones that were missing. INSERT IGNORE keeps the
-- script safe to re-run and avoids clobbering any dates an admin has already set.
--
-- Deterministic dates:
--   * African Union Day  — 25 May          (observance)
--   * Farmers' Day       — first Friday of December (2026-12-04)
--
-- Islamic holidays follow the lunar calendar and are officially announced each
-- year, so the dates below are ESTIMATES for 2026. Confirm and correct them from
-- the admin Holiday Calendar once Government of Ghana gazettes the actual dates.
--   * Eid al-Fitr        — ~20 Mar 2026    (estimated)
--   * Eid al-Adha        — ~27 May 2026    (estimated)

INSERT IGNORE INTO holidays (holiday_date, description) VALUES
    ('2026-05-25', 'African Union Day'),
    ('2026-12-04', "Farmers' Day"),
    ('2026-03-20', 'Eid al-Fitr (estimated — confirm)'),
    ('2026-05-27', 'Eid al-Adha (estimated — confirm)');

-- --------------------------------------------
-- @update
-- --------------------------------------------

-- Insert default table data
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_SAFE_UPDATES = 0;


ALTER TABLE task
    CHANGE closed_at closed_at TIMESTAMP NULL DEFAULT NULL;
ALTER TABLE task
    CHANGE cancelled_at cancelled_at TIMESTAMP NULL DEFAULT NULL;
ALTER TABLE task
    CHANGE invoiced_at invoiced_at TIMESTAMP NULL DEFAULT NULL;

UPDATE task SET closed_at = NULL WHERE DATE(closed_at) = DATE('0000-00-00 00:00:00');
UPDATE task SET cancelled_at = NULL WHERE DATE(cancelled_at) = DATE('0000-00-00 00:00:00');
UPDATE task SET invoiced_at = NULL WHERE DATE(invoiced_at) = DATE('0000-00-00 00:00:00');

UPDATE task SET closed_at = NULL WHERE cancelled_at IS NOT NULL;

ALTER TABLE domain_ping
    CHANGE site_time site_time TIMESTAMP NULL DEFAULT NULL;


SET SQL_SAFE_UPDATES = 1;
SET FOREIGN_KEY_CHECKS = 1;



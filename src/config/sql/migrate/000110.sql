-- --------------------------------------------
-- @update
-- --------------------------------------------

-- Insert default table data
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_SAFE_UPDATES = 0;


ALTER TABLE task
    ADD closed_at TIMESTAMP DEFAULT NULL AFTER minutes;
ALTER TABLE task
    ADD cancelled_at TIMESTAMP NULL DEFAULT NULL AFTER closed_at;
ALTER TABLE task
    ADD invoiced_at TIMESTAMP NULL DEFAULT NULL AFTER cancelled_at;
ALTER TABLE task
    ADD invoice_item_id INT UNSIGNED NULL DEFAULT NULL AFTER invoiced_at;

ALTER TABLE task ADD CONSTRAINT fk_task__invoice_item_id
    FOREIGN KEY (invoice_item_id) REFERENCES invoice_item (invoice_item_id) ON DELETE CASCADE ON UPDATE CASCADE;


ALTER TABLE task DROP COLUMN invoiced;
ALTER TABLE task DROP COLUMN status;
ALTER TABLE task_log DROP COLUMN status;
ALTER TABLE task_log DROP COLUMN notes;


UPDATE task AS t
JOIN (
    SELECT
        *,
        ROW_NUMBER() OVER (PARTITION BY fkey, fid ORDER BY created DESC) AS latest
    FROM status_log
) sl ON (sl.fkey = 'App\\Db\\Task' AND sl.fid = t.task_id AND sl.latest = 1)
SET t.closed_at = sl.created
WHERE sl.name = 'closed';

UPDATE task AS t
JOIN (
    SELECT
        *,
        ROW_NUMBER() OVER (PARTITION BY fkey, fid ORDER BY created DESC) AS latest
    FROM status_log
) sl ON (sl.fkey = 'App\\Db\\Task' AND sl.fid = t.task_id AND sl.latest = 1)
SET t.cancelled_at = sl.created
WHERE sl.name = 'cancelled';

UPDATE task AS t
JOIN invoice_item i ON (i.product_code = CONCAT('TSK-', t.task_id))
SET t.invoiced_at = i.created, t.invoice_item_id = i.invoice_item_id
WHERE 1;


ALTER TABLE invoice
    ADD cancelled_on DATE DEFAULT NULL AFTER paid_on;

ALTER TABLE invoice DROP COLUMN status;

UPDATE invoice AS i
JOIN (
    SELECT
        *,
        ROW_NUMBER() OVER (PARTITION BY fkey, fid ORDER BY created DESC) AS latest
    FROM status_log
) sl ON (sl.fkey = 'App\\Db\\Invoice' AND sl.fid = i.invoice_id AND sl.latest = 1)
SET i.cancelled_on = DATE(sl.created)
WHERE sl.name = 'cancelled';

ALTER TABLE project DROP COLUMN status;

ALTER TABLE project
    ADD cancelled_on DATE DEFAULT NULL AFTER end_on;


ALTER TABLE payment DROP COLUMN status;

SET SQL_SAFE_UPDATES = 1;
SET FOREIGN_KEY_CHECKS = 1;



-- --------------------------------------------
-- @version 1.0.64
-- --------------------------------------------

-- Insert default table data
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_SAFE_UPDATES = 0;

ALTER TABLE recurring MODIFY price INT NULL DEFAULT NULL;

-- todo remove after 1.0.64 released
UPDATE recurring SET price = NULL WHERE 1;
UPDATE recurring SET description = 'APD - Server Administration Services' WHERE recurring_id = 12;
UPDATE recurring SET description = 'APD - Hosting 20Gig' WHERE recurring_id = 11;

UPDATE product SET name = 'APD - Server Administration Services' WHERE product_id IN (20, 23);
UPDATE product SET name = 'APD - Hosting 20Gig' WHERE product_id IN (19, 22);
UPDATE product SET price = 185000 WHERE product_id = 23;


SET SQL_SAFE_UPDATES = 1;
SET FOREIGN_KEY_CHECKS = 1;



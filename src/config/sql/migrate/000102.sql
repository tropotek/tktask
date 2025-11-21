-- --------------------------------------------
-- @version 1.0.64
-- --------------------------------------------

-- Insert default table data
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_SAFE_UPDATES = 0;

ALTER TABLE recurring MODIFY price INT NULL DEFAULT NULL;


SET SQL_SAFE_UPDATES = 1;
SET FOREIGN_KEY_CHECKS = 1;



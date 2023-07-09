-- ------------------------------------------------------
-- All project SQL events
--
-- Files views.sql, procedures.sql, events.sql, triggers.sql
--  will be executed if they exist after install, update and migration
--
-- They can be executed from the cli commands:
--  o `./bin/cmd migrate`
--  o `composer update`
--
-- ------------------------------------------------------

-- Delete expired user 'remember me' login tokens
DROP EVENT IF EXISTS evt_delete_expired_user_remember;
DELIMITER //
CREATE EVENT evt_delete_expired_user_remember
  ON SCHEDULE EVERY 1 DAY
  COMMENT 'Delete expired user remember me login tokens'
  DO
  BEGIN
    DELETE FROM user_remember WHERE expiry < NOW();
  END
//
DELIMITER ;

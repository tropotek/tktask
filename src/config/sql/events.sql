-- ------------------------------------------------------
-- SQL events
--
-- Note: update your mysql server to `event_scheduler=ON` to enable mysql events
-- ------------------------------------------------------


-- Delete expired notify messages
DROP EVENT IF EXISTS evt_delete_expired_notify;
DELIMITER //
CREATE EVENT evt_delete_expired_notify
  ON SCHEDULE EVERY 1 HOUR
  COMMENT 'Delete notify records'
  DO
  BEGIN
    DELETE FROM notify
    WHERE expiry < NOW();
  END
//
DELIMITER ;

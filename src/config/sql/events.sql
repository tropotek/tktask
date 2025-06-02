-- ------------------------------------------------------
-- SQL events
--
-- Note: update your mysql server to `event_scheduler=ON` to enable mysql events
-- ------------------------------------------------------


-- Delete expired notify messages (\App\Db\Notify)
DROP EVENT IF EXISTS evt_delete_expired_notify;
DELIMITER //
CREATE EVENT evt_delete_expired_notify
  ON SCHEDULE EVERY 30 MINUTE
  COMMENT 'Delete notify records'
  DO
  BEGIN
    DELETE FROM notify
    WHERE expiry < NOW();
  END
//
DELIMITER ;

-- Delete domain pings after a year (\App\Db\DomainPing)
DROP EVENT IF EXISTS evt_delete_domain_ping;
DELIMITER //
CREATE EVENT evt_delete_domain_ping
  ON SCHEDULE EVERY 1 DAY
  COMMENT 'Delete domain pings'
  DO
  BEGIN
    DELETE FROM domain_ping
    WHERE created < NOW() - INTERVAL 1 YEAR;
  END
//
DELIMITER ;

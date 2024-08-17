-- ------------------------------------------------------
-- All project views
--
-- Files views.sql, procedures.sql, events.sql, triggers.sql
--  will be executed if they exist after install, update and migration
--
-- They can be executed from the cli commands:
--  o `./bin/cmd migrate`
--  o `composer update`
--
-- ------------------------------------------------------

-- Show only active users
CREATE OR REPLACE VIEW v_user AS
SELECT *
FROM
  user u
WHERE
  u.active
;


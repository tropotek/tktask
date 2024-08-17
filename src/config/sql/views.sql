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
# CREATE OR REPLACE VIEW v_user AS
# SELECT *
# FROM
#   user u
# WHERE
#   u.active
# ;

CREATE OR REPLACE VIEW v_example AS
SELECT
  e.example_id,
  e.name,
  e.image,
  e.active,
  e.modified,
  e.created,
  NOW() AS today,
  MD5(e.example_id) AS hash
FROM example e;




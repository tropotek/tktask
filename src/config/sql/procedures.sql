-- ------------------------------------------------------
-- All project procedures and functions
--
-- Files views.sql, procedures.sql, events.sql, triggers.sql
--  will be executed if they exist after install, update and migration
--
-- They can be executed from the cli commands:
--  o `./bin/cmd migrate`
--  o `composer update`
--
-- ------------------------------------------------------

-- compares two date ranges and checks for overlap (inclusive)
-- start dates must be before end date
# DROP FUNCTION IF EXISTS dates_overlap;
# CREATE FUNCTION dates_overlap(
# 	start1 DATE,
# 	end1 DATE,
# 	start2 DATE,
# 	end2 DATE
# ) RETURNS BOOLEAN DETERMINISTIC
# 	RETURN GREATEST(start1, start2) <= LEAST(end1, end2)
# ;


-- Create a temporary date table for count queries that have no data on every date required
# DROP PROCEDURE IF EXISTS procFillCal;
# DELIMITER //
# CREATE PROCEDURE procFillCal(pTableName VARCHAR(32), pStartDate DATE, pEndDate DATE, pInterval VARCHAR(8), pIntervalUnit INTEGER)
# BEGIN
#   DECLARE pDate DATE;
# --  DROP TEMPORARY TABLE IF EXISTS pTableName;
#   CREATE TEMPORARY TABLE pTableName (`date` DATE );
#   TRUNCATE pTableName;
#   SET pDate = pStartDate;
#   WHILE pDate < pEndDate DO
#     INSERT INTO pTableName VALUES(pDate);
#     CASE UPPER(pInterval)
#       WHEN 'DAY' THEN SET pDate = ADDDATE(pDate, INTERVAL pIntervalUnit DAY);
#       WHEN 'WEEK' THEN SET pDate = ADDDATE(pDate, INTERVAL pIntervalUnit WEEK);
#       WHEN 'MONTH' THEN SET pDate = ADDDATE(pDate, INTERVAL pIntervalUnit MONTH);
#       WHEN 'YEAR' THEN SET pDate = ADDDATE(pDate, INTERVAL pIntervalUnit YEAR);
#     END CASE;
#   END WHILE;
#  END //
# DELIMITER ;


-- return extension given a filename
-- returns extension lower-cased, null if no extension found
# DROP FUNCTION IF EXISTS filename_ext;
# DELIMITER //
# CREATE FUNCTION filename_ext(filename VARCHAR(400))
#   RETURNS VARCHAR(4) DETERMINISTIC
# BEGIN
#   SET @ext = SUBSTRING_INDEX(filename, '.', -1);
#   IF @ext = filename THEN
#     -- no . found
#     SET @ext = NULL;
#   END IF;
#   RETURN LOWER(@ext);
# END //
# DELIMITER ;
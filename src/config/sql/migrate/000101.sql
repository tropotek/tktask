-- --------------------------------------------
-- @version 0.0.0
-- --------------------------------------------

CREATE TABLE IF NOT EXISTS example
(
  example_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(128) NOT NULL DEFAULT '',
  image VARCHAR(255) NOT NULL DEFAULT '',
  active BOOL NOT NULL DEFAULT TRUE,
  notes TEXT,
  modified TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  created TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


CREATE TABLE IF NOT EXISTS widget
(
  widget_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(128) NOT NULL DEFAULT '',
  active BOOL NOT NULL DEFAULT TRUE,
  enabled TINYINT(1) NOT NULL DEFAULT 0,
  notes TEXT,
  blob_data BLOB,
  time_stamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,   -- timezone enabled
  date_time DATETIME DEFAULT NULL,
  date DATE DEFAULT NULL,
  time TIME DEFAULT NULL,
  year YEAR DEFAULT NULL,
  json_str JSON DEFAULT NULL,
  -- A SET value must be the empty string or a value consisting only of the values listed in the column definition separated by commas.
  set_type SET('Applicant','Student','Mentor','Physician','Staff') DEFAULT 'Applicant,Staff',
  -- An ENUM value must be one of those listed in the column definition, or the internal numeric equivalent thereof
  enum_type ENUM('core','elective_hospital','elective_online','csc') NOT NULL DEFAULT 'core',
  rate decimal(10,4) NOT NULL DEFAULT '1.0000',
  amount FLOAT NOT NULL DEFAULT '1.0',

  modified TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
INSERT INTO widget (name, active, enabled, notes, blob_data, time_stamp, date_time, date, time, json_str) VALUES
  ('Widget 1', TRUE, FALSE, 'Notes Field', 'Blob field', NOW(), NOW(), CURRENT_DATE, CURRENT_TIME, '{"test":"this is a test str"}'),
  ('Widget 2', TRUE, TRUE, 'Notes Field', 'Blob field', NOW(), '2023-12-01 10:20:30', '2023-12-01', '10:20:30', '{"test":"this is a test str"}'),
  ('Widget 3', FALSE, FALSE, 'Notes Field', 'Blob field', NULL, NOW(), NULL, CURRENT_TIME, NULL),
  ('Widget 4', TRUE, TRUE, 'Notes Field', 'Blob field', NOW(), NULL, CURRENT_DATE, NULL, NULL);



SET FOREIGN_KEY_CHECKS = 0;
SET SQL_SAFE_UPDATES = 0;

# TRUNCATE TABLE user;
# TRUNCATE TABLE user_token;
TRUNCATE TABLE example;

INSERT INTO user (type, username, email, name_first, timezone, permissions) VALUES
  ('staff', 'admin', 'admin@example.com', 'Administrator', NULL, 1),
  ('staff', 'dev', 'dev@example.com', 'Developer', 'Australia/Melbourne', 1),
  ('staff', 'design', 'design@example.com', 'Designer', 'Australia/Melbourne', 1),
  ('staff', 'staff', 'staff@example.com', 'Staff', 'Australia/Melbourne', 2),
  ('user', 'member', 'user@example.com', 'User', 'Australia/Brisbane', 0)
;

UPDATE `user` SET `hash` = MD5(CONCAT(username, user_id)) WHERE 1;


SET SQL_SAFE_UPDATES = 1;
SET FOREIGN_KEY_CHECKS = 1;












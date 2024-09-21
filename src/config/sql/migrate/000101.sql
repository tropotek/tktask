-- --------------------------------------------
-- @version 1.0.0
-- --------------------------------------------

CREATE TABLE IF NOT EXISTS user
(
  user_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  type VARCHAR(128) NOT NULL DEFAULT '',
  title VARCHAR(20) NOT NULL DEFAULT '',
  given_name VARCHAR(128) NOT NULL DEFAULT '',
  family_name VARCHAR(128) NOT NULL DEFAULT '',
  phone VARCHAR(20) NOT NULL DEFAULT '',
  address VARCHAR(1000) NOT NULL DEFAULT '',
  city VARCHAR(128) NOT NULL DEFAULT '',
  state VARCHAR(128) NOT NULL DEFAULT '',
  postcode VARCHAR(128) NOT NULL DEFAULT '',
  country VARCHAR(128) NOT NULL DEFAULT '',
  modified TIMESTAMP ON UPDATE CURRENT_TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY (type)
);




-- Test users (remove for prod sites)
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_SAFE_UPDATES = 0;

TRUNCATE TABLE user;
TRUNCATE TABLE user_remember;

INSERT INTO user (type, given_name) VALUES ('staff', 'Developer');
INSERT INTO auth (fkey, fid, permissions, username, email, timezone) VALUES
  ('App\\Db\\User', LAST_INSERT_ID(), 1, 'dev', 'dev@example.com', 'Australia/Melbourne');

INSERT INTO user (type, given_name) VALUES ('staff', 'Designer');
INSERT INTO auth (fkey, fid, permissions, username, email, timezone) VALUES
  ('App\\Db\\User', LAST_INSERT_ID(), 6, 'design', 'design@example.com', 'Australia/Melbourne');

INSERT INTO user (type, given_name) VALUES ('staff', 'Staff');
INSERT INTO auth (fkey, fid, permissions, username, email, timezone) VALUES
  ('App\\Db\\User', LAST_INSERT_ID(), 14, 'staff', 'staff@example.com', 'Australia/Melbourne');

INSERT INTO user (type, given_name) VALUES ('member', 'Member');
INSERT INTO auth (fkey, fid, username, email, timezone) VALUES
  ('App\\Db\\User', LAST_INSERT_ID(), 'member', 'member@example.com', 'Australia/Brisbane');

SET SQL_SAFE_UPDATES = 1;
SET FOREIGN_KEY_CHECKS = 1;












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

CREATE TABLE IF NOT EXISTS notify (
  notify_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL DEFAULT 0,
  title VARCHAR(250) NOT NULL DEFAULT '',
  message TEXT,
  url VARCHAR(250) NOT NULL DEFAULT '',
  icon BLOB NOT NULL DEFAULT '',
  read_on DATETIME NULL,        -- Date user read notification in browser
  notified_on DATETIME NULL,    -- Date message was sent as browser notification
  ttl_mins INT NOT NULL DEFAULT 1440,
  created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  expiry DATETIME GENERATED ALWAYS AS (created + INTERVAL ttl_mins MINUTE) VIRTUAL,
  KEY (user_id),
  CONSTRAINT fk_notify__user_id FOREIGN KEY (user_id) REFERENCES user (user_id) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS file
(
  file_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL DEFAULT 0,  -- uploader
  fkey VARCHAR(64) DEFAULT '' NOT NULL,
  fid INT DEFAULT 0 NOT NULL DEFAULT 0,
  label VARCHAR(128) NOT NULL DEFAULT '',
  `path` VARCHAR(512) NOT NULL DEFAULT '',
  bytes INT UNSIGNED NOT NULL DEFAULT 0,
  mime VARCHAR(255) NOT NULL DEFAULT '',
  notes TEXT NULL,
  selected BOOL NOT NULL DEFAULT FALSE,
  created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY user_id (user_id),
  KEY fkey (fkey),
  KEY fkey_2 (fkey, fid),
  KEY fkey_3 (fkey, fid, label),
  CONSTRAINT fk_file__user_id FOREIGN KEY (user_id) REFERENCES user (user_id) ON DELETE CASCADE ON UPDATE CASCADE
);

-- Test users (remove for prod sites)
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_SAFE_UPDATES = 0;

# TRUNCATE TABLE user;
# TRUNCATE TABLE auth;
# TRUNCATE TABLE auth_remember;

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



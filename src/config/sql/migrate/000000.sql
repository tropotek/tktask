-- --------------------------------------------
-- @version 0.0.0
-- --------------------------------------------

CREATE TABLE IF NOT EXISTS user
(
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  uid VARCHAR(128) NOT NULL DEFAULT '',
  type VARCHAR(32) NOT NULL DEFAULT '',
  permissions BIGINT NOT NULL DEFAULT 0,
  username VARCHAR(128) NOT NULL DEFAULT '',
  password VARCHAR(128) NOT NULL DEFAULT '',
  email VARCHAR(255) NOT NULL DEFAULT '',
  name VARCHAR(128) NOT NULL DEFAULT '',
  notes TEXT DEFAULT '',
  timezone VARCHAR(64) NULL,
  active BOOL NOT NULL DEFAULT TRUE,
  hash VARCHAR(64) NOT NULL DEFAULT '',
  last_login TIMESTAMP NULL,
  -- del BOOL NOT NULL DEFAULT FALSE,
  modified TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY (uid),
  KEY (type),
  KEY (email),
  UNIQUE KEY (username)
);

TRUNCATE user;
INSERT INTO user (type, username, email, name, timezone, permissions) VALUES
  ('staff', 'admin', 'admin@example.com', 'Administrator', NULL, 1),
  ('staff', 'dev', 'dev@example.com', 'Developer', 'Australia/Melbourne', 1),
  ('staff', 'design', 'design@example.com', 'Designer', 'Australia/Melbourne', 1),
  ('staff', 'staff', 'staff@example.com', 'Staff', 'Australia/Melbourne', 2),
  ('user', 'user', 'user@example.com', 'User', 'Australia/Brisbane', 4)
;

SET SQL_SAFE_UPDATES = 0;
UPDATE `user` SET `hash` = MD5(CONCAT(username, id)) WHERE 1;
SET SQL_SAFE_UPDATES = 1;

CREATE TABLE user_tokens
(
  id INT AUTO_INCREMENT PRIMARY KEY,
  selector VARCHAR(255) NOT NULL,
  hashed_validator VARCHAR(255) NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  expiry DATETIME NOT NULL,
  CONSTRAINT fk_user_id FOREIGN KEY (user_id) REFERENCES `user` (`id`) ON DELETE CASCADE
);








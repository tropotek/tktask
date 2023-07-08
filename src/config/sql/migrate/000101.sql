-- --------------------------------------------
-- @version 0.0.0
-- --------------------------------------------


CREATE TABLE IF NOT EXISTS example
(
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(128) NOT NULL DEFAULT '',
  image VARCHAR(255) NOT NULL DEFAULT '',
  nick VARCHAR(64) NULL,
  content TEXT DEFAULT '',
  notes TEXT DEFAULT '',
  active BOOL NOT NULL DEFAULT TRUE,
  del BOOL NOT NULL DEFAULT FALSE,
  modified TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  created TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);



SET FOREIGN_KEY_CHECKS = 0;
SET SQL_SAFE_UPDATES = 0;

# TRUNCATE TABLE user;
# TRUNCATE TABLE user_token;
TRUNCATE TABLE example;

INSERT INTO user (type, username, email, name, timezone, permissions) VALUES
  ('staff', 'admin', 'admin@example.com', 'Administrator', NULL, 1),
  ('staff', 'dev', 'dev@example.com', 'Developer', 'Australia/Melbourne', 1),
  ('staff', 'design', 'design@example.com', 'Designer', 'Australia/Melbourne', 1),
  ('staff', 'staff', 'staff@example.com', 'Staff', 'Australia/Melbourne', 2),
  ('user', 'member', 'user@example.com', 'User', 'Australia/Brisbane', 0)
;

UPDATE `user` SET `hash` = MD5(CONCAT(username, id)) WHERE 1;


SET SQL_SAFE_UPDATES = 1;
SET FOREIGN_KEY_CHECKS = 1;






-- --------------------------------------------
-- @version 0.0.0
--
-- @author: Tropotek <https://tropotek.com/>
-- --------------------------------------------

-- SET FOREIGN_KEY_CHECKS = 0;

--
--
--
CREATE TABLE IF NOT EXISTS user
(
  user_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  uid VARCHAR(128) NOT NULL DEFAULT '',
  type VARCHAR(32) NOT NULL DEFAULT '',
  username VARCHAR(128) NOT NULL DEFAULT '',
  password VARCHAR(128) NOT NULL DEFAULT '',
  email VARCHAR(255) NOT NULL DEFAULT '',
  title VARCHAR(16) NOT NULL DEFAULT '',
  first_name VARCHAR(128) NOT NULL DEFAULT '',
  last_name VARCHAR(128) NOT NULL DEFAULT '',
  timezone VARCHAR(128) NOT NULL DEFAULT '',
  notes VARCHAR(512) NOT NULL DEFAULT '',
  active BOOL NOT NULL DEFAULT TRUE,
  hash VARCHAR(64) NOT NULL DEFAULT '',       -- use this instead of user_id for public requests
  last_login TIMESTAMP NULL,
  del BOOL NOT NULL DEFAULT FALSE,
  modified TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY (uid),
  KEY (type),
  KEY (email),
  UNIQUE KEY (username)
) ENGINE=InnoDB;


-- SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE user;
INSERT INTO user (type, username, email, title, first_name, last_name, timezone, hash) VALUES
   ('admin', 'admin', 'admin@example.com', '', 'Administrator', '', '', MD5(CONCAT('admin', user_id))),
   ('admin', 'mod', 'moderator@example.com', '', 'Moderator', '', 'Australia/Melbourne', MD5(CONCAT('moderator', user_id))),
   ('member', 'user', 'user@example.com', 'Mr', 'User', 'One', 'Australia/Brisbane', MD5(CONCAT('user', user_id)))
;

UPDATE `user` SET `password` = MD5(CONCAT('password', `hash`));


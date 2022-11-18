-- --------------------------------------------
-- @version 0.0.0
--
-- @author: Tropotek <https://tropotek.com/>
-- --------------------------------------------

CREATE TABLE IF NOT EXISTS user
(
  user_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  uid VARCHAR(128) NOT NULL DEFAULT '',
  type VARCHAR(32) NOT NULL DEFAULT '',
  email VARCHAR(255) NOT NULL DEFAULT '',
  title VARCHAR(16) NOT NULL DEFAULT '',
  first_name VARCHAR(128) NOT NULL DEFAULT '',
  last_name VARCHAR(128) NOT NULL DEFAULT '',
  timezone VARCHAR(128) NOT NULL DEFAULT '',
  notes VARCHAR(512) NOT NULL DEFAULT '',
  active BOOL DEFAULT TRUE NOT NULL,
  hash VARCHAR(64) NOT NULL DEFAULT '',
  last_login TIMESTAMP NULL,
  modified TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY (email),
  KEY (uid),
  KEY (type)
) ENGINE=InnoDB;


--
-- If no user entry then that user does not have login access
-- To enable an email to validate the account and create a password should be sent
-- There should be no where in the application to view/edit the password only use a recovery system
--
CREATE TABLE IF NOT EXISTS user_auth
(
  user_id INT UNSIGNED DEFAULT 0 NOT NULL,
  `username` VARCHAR(64) DEFAULT '' NOT NULL,
  `password` VARCHAR(128) DEFAULT '' NOT NULL,  -- Hashed password
  PRIMARY KEY (user_id, `username`),
  FOREIGN KEY (user_id) REFERENCES user (user_id)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Using this type of user table setup we can then add associated tables
--  such as: user_google, user_facebook, user_microsoft, user_gdrive, etc...


-- TRUNCATE user;



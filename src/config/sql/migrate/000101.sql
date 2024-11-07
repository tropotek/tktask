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
  filename VARCHAR(255) NOT NULL DEFAULT '',    -- the files relative path from site root
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

-- ----


CREATE TABLE IF NOT EXISTS company
(
  company_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  type ENUM('Client','Supplier') DEFAULT 'Client',
  name VARCHAR(128) NOT NULL DEFAULT '',
  alias VARCHAR(255) NOT NULL DEFAULT '',
  abn VARCHAR(16) DEFAULT NULL DEFAULT '',
  website VARCHAR(128) NOT NULL DEFAULT '',
  contact VARCHAR(128) NOT NULL DEFAULT '',             -- The contact persons name
  phone VARCHAR(32) NOT NULL DEFAULT '',
  email VARCHAR(128) NOT NULL DEFAULT '',
  address VARCHAR(512) NOT NULL DEFAULT '',
  credit INT NOT NULL DEFAULT '0',                      -- Store any credit the client has in cents
  notes TEXT,
  modified TIMESTAMP ON UPDATE CURRENT_TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY (type)
);


-- ---------------------------------------------------

-- Insert default table data
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_SAFE_UPDATES = 0;

-- Add default user
# INSERT INTO user (type, given_name) VALUES ('staff', 'admin');
# INSERT INTO auth (fkey, fid, permissions, username, email, timezone) VALUES
#   ('App\\Db\\User', LAST_INSERT_ID(), 1, 'admin', 'admin@email.com', 'Australia/Melbourne');
-- TODO: remove for production release
INSERT INTO user (type, given_name) VALUES ('staff', 'Tropotek');
INSERT INTO auth (fkey, fid, permissions, username, email, timezone) VALUES
  ('App\\Db\\User', LAST_INSERT_ID(), 1, 'tropotek', 'godar@dev.ttek.org', 'Australia/Melbourne');



SET SQL_SAFE_UPDATES = 1;
SET FOREIGN_KEY_CHECKS = 1;



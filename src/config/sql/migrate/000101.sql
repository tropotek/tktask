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
  credit INT NOT NULL DEFAULT 0,                      -- Store any credit the client has in cents
  notes TEXT,
  active BOOL NOT NULL DEFAULT TRUE,
  modified TIMESTAMP ON UPDATE CURRENT_TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY (type)
);

CREATE TABLE IF NOT EXISTS task_category
(
  task_category_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(128) NOT NULL DEFAULT '',
  label VARCHAR(128) NOT NULL DEFAULT '',
  description VARCHAR(512) NOT NULL DEFAULT '',
  order_by INT NOT NULL DEFAULT 0,
  active BOOL NOT NULL DEFAULT TRUE,
  modified TIMESTAMP ON UPDATE CURRENT_TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  created TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


CREATE TABLE IF NOT EXISTS product_category (
  product_category_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(64) NOT NULL DEFAULT '',
  description TEXT,
  order_by INT NOT NULL DEFAULT 0,
  modified TIMESTAMP ON UPDATE CURRENT_TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  created TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS product (
  product_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_id INT UNSIGNED NOT NULL DEFAULT 0,
  recur ENUM('week','fortnight','month','quarter','year','biannual') DEFAULT NULL,  -- price is for this duration, if null then a unit price
  name VARCHAR(128) NOT NULL DEFAULT '',
  code VARCHAR(64) NOT NULL DEFAULT '',
  price INT NOT NULL DEFAULT 0,
  description TEXT,
  notes TEXT,
  order_by INT NOT NULL DEFAULT 0,
  active BOOL NOT NULL DEFAULT TRUE,
  modified TIMESTAMP ON UPDATE CURRENT_TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY (code),
  CONSTRAINT fk_product__category_id FOREIGN KEY (category_id) REFERENCES product_category (product_category_id) ON DELETE RESTRICT ON UPDATE CASCADE
);





-- ---------------------------------------------------

-- Insert default table data
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_SAFE_UPDATES = 0;

-- Add default disabled admin user (remember to set password `./bin/cmd pwd admin`)
INSERT INTO user (type, given_name) VALUES ('staff', 'admin');
INSERT INTO auth (fkey, fid, permissions, username, email, timezone, active) VALUES
  ('App\\Db\\User', LAST_INSERT_ID(), 1, 'admin', 'admin@email.com', 'Australia/Melbourne', false);

-- TODO: remove for production release (remember to set password `./bin/cmd pwd tropotek`)
INSERT INTO user (type, given_name) VALUES ('staff', 'Tropotek');
INSERT INTO auth (fkey, fid, permissions, username, email, timezone, active) VALUES
  ('App\\Db\\User', LAST_INSERT_ID(), 1, 'tropotek', 'godar@dev.ttek.org', 'Australia/Melbourne', true);

INSERT INTO task_category (name, label) VALUES
  ('task', 'Task'),
  ('feature', 'Feature'),
  ('bug', 'Bug'),
  ('support', 'Support'),
  ('other', 'Other')
;
UPDATE task_category SET order_by = task_category_id WHERE TRUE;

SET SQL_SAFE_UPDATES = 1;
SET FOREIGN_KEY_CHECKS = 1;



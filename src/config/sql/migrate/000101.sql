-- --------------------------------------------
-- @version 1.0.0
-- --------------------------------------------

CREATE TABLE IF NOT EXISTS user
(
  user_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
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
  notify_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL DEFAULT 0,
  title VARCHAR(250) NOT NULL DEFAULT '',
  message TEXT,
  url VARCHAR(250) NOT NULL DEFAULT '',
  icon BLOB NOT NULL DEFAULT '',
  read_on DATETIME NULL,                                    -- Date user read notification in browser
  notified_on DATETIME NULL,                                -- Date message was sent as browser notification
  ttl_mins INT UNSIGNED NOT NULL DEFAULT 1440,
  created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  expiry DATETIME GENERATED ALWAYS AS (created + INTERVAL ttl_mins MINUTE) VIRTUAL,
  KEY (user_id),
  CONSTRAINT fk_notify__user_id FOREIGN KEY (user_id) REFERENCES user (user_id) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS file
(
  file_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL DEFAULT NULL,
  fkey VARCHAR(64) DEFAULT '' NOT NULL,
  fid INT UNSIGNED NOT NULL DEFAULT 0,
  label VARCHAR(128) NOT NULL DEFAULT '',
  filename VARCHAR(255) NOT NULL DEFAULT '',              -- the files relative path from site root
  bytes INT UNSIGNED NOT NULL DEFAULT 0,
  mime VARCHAR(255) NOT NULL DEFAULT '',
  notes TEXT NULL,
  selected BOOL NOT NULL DEFAULT FALSE,
  created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY (user_id),
  KEY (fkey),
  KEY (fkey, fid),
  KEY (fkey, fid, label),
  CONSTRAINT fk_file__user_id FOREIGN KEY (user_id) REFERENCES user (user_id) ON DELETE SET NULL ON UPDATE CASCADE
);

--

CREATE TABLE IF NOT EXISTS status_log (
  status_log_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL DEFAULT NULL,               -- The user who performed the activity
  fkey VARCHAR(64) NOT NULL DEFAULT '',                 -- A foreign key as a string (usually the object name)
  fid INT UNSIGNED NOT NULL DEFAULT 0,                  -- foreign_id
  name VARCHAR(32) NOT NULL DEFAULT '',                 -- pending, approved, not_approved, etc
  notify BOOL NOT NULL DEFAULT TRUE,                    -- trigger messages send event
  message TEXT,                                         -- A status update log message
  data TEXT,                                            -- json data of any related object data pertaining to this status event
  created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY (fkey, fid),
  CONSTRAINT fk_status_log__user_id FOREIGN KEY (user_id) REFERENCES user (user_id) ON DELETE SET NULL ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS company
(
  company_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
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

CREATE TABLE IF NOT EXISTS project (
  project_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL DEFAULT 0,              -- project lead user/contact
  company_id INT UNSIGNED NOT NULL DEFAULT 0,
  status ENUM('pending','active','hold','completed','cancelled') DEFAULT 'pending',
  name VARCHAR(128) NOT NULL,
  quote INT NOT NULL DEFAULT 0,
  date_start DATETIME NULL,   -- todo DATE
  date_end DATETIME NULL,     -- todo DATE
  description TEXT,
  notes TEXT,
  modified TIMESTAMP ON UPDATE CURRENT_TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY status (status),
  CONSTRAINT fk_project__user_id FOREIGN KEY (user_id) REFERENCES user (user_id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_project__company_id FOREIGN KEY (company_id) REFERENCES company (company_id) ON DELETE RESTRICT ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS project_user (
  project_id INT UNSIGNED NOT NULL DEFAULT 0,
  user_id INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (project_id, user_id),
  CONSTRAINT fk_project_user__project_id FOREIGN KEY (project_id) REFERENCES project (project_id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_project_user__user_id FOREIGN KEY (user_id) REFERENCES user (user_id) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS product_category (
  product_category_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(64) NOT NULL DEFAULT '',
  description TEXT,
  modified TIMESTAMP ON UPDATE CURRENT_TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  created TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS product (
  product_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  category_id INT UNSIGNED NOT NULL DEFAULT 0,
  recur ENUM('each', 'week','fortnight','month','quarter','year','biannual') DEFAULT NULL,  -- price is for this duration, if null then a unit price
  name VARCHAR(128) NOT NULL DEFAULT '',
  code VARCHAR(64) NOT NULL DEFAULT '',
  price INT NOT NULL DEFAULT 0,
  description TEXT,
  notes TEXT,
  active BOOL NOT NULL DEFAULT TRUE,
  modified TIMESTAMP ON UPDATE CURRENT_TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY (code),
  CONSTRAINT fk_product__category_id FOREIGN KEY (category_id) REFERENCES product_category (product_category_id) ON DELETE RESTRICT ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS task_category
(
  task_category_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(128) NOT NULL DEFAULT '',
  label VARCHAR(128) NOT NULL DEFAULT '',
  description VARCHAR(512) NOT NULL DEFAULT '',
  order_by INT UNSIGNED NOT NULL DEFAULT 0,
  active BOOL NOT NULL DEFAULT TRUE,
  modified TIMESTAMP ON UPDATE CURRENT_TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  created TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS task (
  task_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  company_id INT UNSIGNED NOT NULL DEFAULT 0,
  project_id INT UNSIGNED NULL DEFAULT NULL,
  category_id INT UNSIGNED NOT NULL DEFAULT 1,
  creator_user_id INT UNSIGNED NOT NULL DEFAULT 0,
  assigned_user_id INT UNSIGNED NOT NULL DEFAULT 0,
  closed_user_id INT UNSIGNED NULL DEFAULT NULL,
  status ENUM('pending','hold','open','closed','cancelled') DEFAULT 'pending',
  subject TEXT,
  comments TEXT,
  priority TINYINT NOT NULL DEFAULT 0,        -- 0 None, 1 Low, 5 Med, 10 High
  minutes INT UNSIGNED NOT NULL DEFAULT 0,    -- Est time in mins for task,
  invoiced DATETIME DEFAULT NULL,             -- The date the billable tasked was invoice, after task CLOSED, not to be invoiced twice
  modified TIMESTAMP ON UPDATE CURRENT_TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY priority (priority),
  KEY status (status),
  CONSTRAINT fk_task__company_id FOREIGN KEY (company_id) REFERENCES company (company_id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_task__project_id FOREIGN KEY (project_id) REFERENCES project (project_id) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_task__category_id FOREIGN KEY (category_id) REFERENCES task_category (task_category_id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_task__creator_user_id FOREIGN KEY (creator_user_id) REFERENCES user (user_id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_task__assigned_user_id FOREIGN KEY (assigned_user_id) REFERENCES user (user_id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_task__closed_user_id FOREIGN KEY (closed_user_id) REFERENCES user (user_id) ON DELETE SET NULL ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS task_log (
  task_log_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  task_id INT UNSIGNED NOT NULL DEFAULT 0,
  user_id INT UNSIGNED NOT NULL DEFAULT 0,
  product_id INT UNSIGNED NOT NULL DEFAULT 1,       -- Usually labor products go here
  status ENUM('pending','hold','open','closed','cancelled') DEFAULT 'pending',  -- Same options as a task
  billable BOOL NOT NULL DEFAULT 0,                 -- Is this task billable
  -- todo rename `worked_at`
  date DATETIME NOT NULL,                           -- DateTime worked started
  minutes INT NOT NULL DEFAULT 0,                   -- Time worked
  comment TEXT,
  notes TEXT,
  modified TIMESTAMP ON UPDATE CURRENT_TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_task_log__task_id FOREIGN KEY (task_id) REFERENCES task (task_id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_task_log__user_id FOREIGN KEY (user_id) REFERENCES user (user_id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_task_log__product_id FOREIGN KEY (product_id) REFERENCES product (product_id) ON DELETE RESTRICT ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS recurring (
  recurring_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  company_id INT UNSIGNED NOT NULL DEFAULT 0,
  product_id INT UNSIGNED NULL DEFAULT NULL,
  price INT NOT NULL DEFAULT 0,              -- The chargeable amount in cents (what is charged to the invoice if > 0)
  count INT UNSIGNED NOT NULL DEFAULT 0,     -- The number of issued invoices
  type ENUM('week','fortnight','month','year','biannual') DEFAULT 'year',
  start_on DATE NOT NULL,                    -- date started recurring invoicing
  end_on DATE NULL DEFAULT NULL,             -- (optional) date to end the recurring invoicing
  prev_on DATE NULL DEFAULT NULL,            -- date the line item was last invoiced
  next_on DATE NOT NULL,                     -- date the line item will be invoiced next
  active BOOL NOT NULL DEFAULT TRUE,         -- If inactive this record should still be updated (next_on) just not invoiced, as if paid automatically
  issue BOOL NOT NULL DEFAULT FALSE,         -- if set, the current invoice is issued after the recurring items are added for that company
  description TEXT,
  notes TEXT,
  modified TIMESTAMP ON UPDATE CURRENT_TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_recurring__company_id FOREIGN KEY (company_id) REFERENCES company (company_id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_recurring__product_id FOREIGN KEY (product_id) REFERENCES product (product_id) ON DELETE SET NULL ON UPDATE CASCADE
);




CREATE TABLE expense_category (
  expense_category_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(128) NOT NULL,
  description TEXT,
  ratio FLOAT UNSIGNED NOT NULL DEFAULT 1.0,
  active BOOL NOT NULL DEFAULT TRUE,
  modified TIMESTAMP ON UPDATE CURRENT_TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  created TIMESTAMP DEFAULT CURRENT_TIMESTAMP
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



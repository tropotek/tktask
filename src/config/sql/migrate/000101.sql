-- --------------------------------------------
-- @version 0.0.0
-- --------------------------------------------

# CREATE TABLE IF NOT EXISTS example
# (
#   example_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
#   name VARCHAR(128) NOT NULL DEFAULT '',
#   image VARCHAR(255) NOT NULL DEFAULT '',
#   nick VARCHAR(64) NULL,
#   content TEXT DEFAULT '',
#   notes TEXT DEFAULT '',
#   active BOOL NOT NULL DEFAULT TRUE,
#   del BOOL NOT NULL DEFAULT FALSE,
#   modified TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
#   created TIMESTAMP DEFAULT CURRENT_TIMESTAMP
# );


CREATE TABLE IF NOT EXISTS example
(
  example_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(128) NOT NULL DEFAULT '',
  image VARCHAR(255) NOT NULL DEFAULT '',
  is_active BOOL NOT NULL DEFAULT TRUE,
  modified TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  created TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS example_details
(
  example_id INT UNSIGNED NOT NULL,
  nickname VARCHAR(64) NULL,
  mobile VARCHAR(128) NOT NULL DEFAULT '',
  address1 VARCHAR(255) NOT NULL DEFAULT '',
  address2 VARCHAR(255) NOT NULL DEFAULT '',
  city VARCHAR(255) NOT NULL DEFAULT '',
  state VARCHAR(255) NOT NULL DEFAULT '',
  country VARCHAR(255) NOT NULL DEFAULT '',
  content TEXT DEFAULT '',
  notes TEXT DEFAULT '',
  CONSTRAINT fk_example_details__example_id FOREIGN KEY (example_id) REFERENCES example (example_id) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS example_microsoft
(
  example_id INT UNSIGNED NOT NULL,
  username VARCHAR(128) NOT NULL DEFAULT '',
  microsoft_id VARCHAR(128) NOT NULL DEFAULT '',
  is_active BOOL NOT NULL DEFAULT TRUE,
  CONSTRAINT fk_example_microsoft__example_id FOREIGN KEY (example_id) REFERENCES example (example_id) ON DELETE CASCADE ON UPDATE CASCADE
);


-- TODO: This goes into views.php
CREATE OR REPLACE VIEW v_example AS
SELECT
    e.example_id,
    e.name,
    e.image,
    e.is_active,
    ed.nickname,
    ed.mobile,
    ed.address1,
    ed.address2,
    ed.city,
    ed.state,
    ed.country,
    ed.content,
    ed.notes,
    em.username,
    em.microsoft_id,
    em.is_active AS microsoft_is_active,
    e.modified,
    e.created
FROM example e
LEFT JOIN example_details ed USING (example_id)
LEFT JOIN example_microsoft em USING (example_id)
;
















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






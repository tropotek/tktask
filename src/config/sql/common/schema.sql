-- --------------------------------------------
-- @version install
--
-- This file should contain required DB schema
--  for a fresh install of the site.
--
-- data, views, functions, procedures, etc
--
-- @author: Tropotek <https://tropotek.com/>
-- --------------------------------------------


CREATE TABLE IF NOT EXISTS user
(
  user_id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  uid         VARCHAR(128) DEFAULT ''    NOT NULL,
  type        VARCHAR(32)  DEFAULT ''    NOT NULL,
  `username`  VARCHAR(64)  DEFAULT ''    NOT NULL,
  `password`  VARCHAR(128) default ''    not null,
  name_first  VARCHAR(128) DEFAULT ''    NOT NULL,
  name_last   VARCHAR(128) DEFAULT ''    NOT NULL,
  email       VARCHAR(255) DEFAULT ''    NOT NULL,
  last_login  TIMESTAMP                      NULL,
  active      BOOL         DEFAULT TRUE  NOT NULL,
  del         BOOL         DEFAULT FALSE NOT NULL,
  modified    TIMESTAMP    ON UPDATE CURRENT_TIMESTAMP,
  created     TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  KEY (`username`),
  KEY (`email`)
) ENGINE=InnoDB;




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

INSERT INTO user (type, username, password, name_first, name_last, email) VALUES
  ('admin', 'admin', 'password', 'Administrator', '', 'admin@example.com'),
  ('admin', 'moderator', 'password', 'Sam', 'Bekett', 'beketts@example.com'),
  ('member', 'user1', 'password', 'User1', 'One', 'user1@example.com'),
  ('member', 'user2', 'password', 'User2', 'Two', 'user2@example.com'),
  ('member', 'user3', 'password', 'User3', 'three', 'user3@example.com'),
  ('member', 'user4', 'password', 'User4', 'Four', 'user4@example.com'),
  ('member', 'user5', 'password', 'User5', 'Five', 'user5@example.com'),
  ('member', 'user6', 'password', 'User6', 'Six', 'user6@example.com'),
  ('member', 'user7', 'password', 'User7', 'Seven', 'user7@example.com'),
  ('member', 'user8', 'password', 'User8', 'Eight', 'user8@example.com')
;


-- --------------------------------------------
-- @version install
--
-- This file should contain all the required data
--  for a fresh install of the site
--
-- @author: Tropotek <https://tropotek.com/>
-- --------------------------------------------




INSERT INTO user (type, username, password, name_first, name_last, email) VALUES
  ('admin', 'admin', 'password', 'Administrator', '', 'admin@example.com'),
  ('admin', 'moderator', 'password', 'Sam', 'Beckett', 'beketts@example.com'),
  ('member', 'user1', 'password', 'User1', 'One', 'user1@example.com'),
  ('member', 'user2', 'password', 'User2', 'Two', 'user2@example.com'),
  ('member', 'user3', 'password', 'User3', 'three', 'user3@example.com'),
  ('member', 'user4', 'password', 'User4', 'Four', 'user4@example.com'),
  ('member', 'user5', 'password', 'User5', 'Five', 'user5@example.com'),
  ('member', 'user6', 'password', 'User6', 'Six', 'user6@example.com'),
  ('member', 'user7', 'password', 'User7', 'Seven', 'user7@example.com'),
  ('member', 'user8', 'password', 'User8', 'Eight', 'user8@example.com')
;


-- ------------------------------------------------------
-- Dev environment db changes
--
-- It will also run after a mirror command is called
--   and the system is in debug mode.
-- It can be executed from the cli command
--   `./bin/cmd debug`
--
-- @author: Tropotek <https://tropotek.com/>
-- ------------------------------------------------------

-- --------------------------------------
-- Change all passwords to 'password' for debug mode
-- --------------------------------------

-- Salted
-- UPDATE `user` SET `password` = MD5(CONCAT('password', `hash`));

-- Unsalted
-- UPDATE `user` SET `password` = MD5('password');



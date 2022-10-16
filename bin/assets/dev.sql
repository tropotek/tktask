-- ------------------------------------------------------
-- EMS II to EMS III migration script Debug SQL
--
-- Author: Michael Mifsud
-- Date: 06/04/17
-- ------------------------------------------------------


-- --------------------------------------
-- Change all passwords to 'password' for debug mode
-- --------------------------------------

-- Salted
-- UPDATE `user` SET `password` = MD5(CONCAT('password', `hash`));

-- Unalted
-- UPDATE `user` SET `password` = MD5('password');



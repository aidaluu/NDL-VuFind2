--
-- Modifications to table `user`
--

ALTER TABLE "user"
  ADD COLUMN pending_email varchar(255) NOT NULL DEFAULT '';

ALTER TABLE "user"
  ADD COLUMN user_provided_email boolean NOT NULL DEFAULT '0';

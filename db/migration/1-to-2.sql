-- Version 2 introduced "locale" column in "users" table

LOCK TABLES `users` WRITE;
ALTER TABLE `users` ADD COLUMN `locale` varchar(10) DEFAULT "@CORA_DEFAULT_LANGUAGE@" AFTER `comment`;
UNLOCK TABLES;

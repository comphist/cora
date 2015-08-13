-- Version 2 introduced "locale" column in "users" table

LOCK TABLES `users` WRITE;
ALTER TABLE `users` ADD COLUMN `locale` varchar(10) DEFAULT "en-US" AFTER `comment`;
UNLOCK TABLES;

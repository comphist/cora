-- Version 2 introduced "locale" column in "users" table

LOCK TABLES `users` WRITE;
ALTER TABLE `users` DROP COLUMN `locale`;
UNLOCK TABLES;

-- Version 3 makes sure all columns have default values where necessary

LOCK TABLES `users` WRITE,
            `project` WRITE,
            `comment` WRITE,
            `dipl` WRITE,
            `modern` WRITE,
            `page` WRITE,
            `tag_suggestion` WRITE,
            `text` WRITE,
            `tagger` WRITE,
            `tagger_options` WRITE;

ALTER TABLE `users` MODIFY COLUMN `lastactive` timestamp;

ALTER TABLE `project` MODIFY COLUMN `cmd_edittoken` varchar(255);
ALTER TABLE `project` MODIFY COLUMN `cmd_import` varchar(255);

ALTER TABLE `comment` MODIFY COLUMN `value` varchar(255) NOT NULL;
ALTER TABLE `comment` MODIFY COLUMN `subtok_id` bigint(20);

ALTER TABLE `dipl` MODIFY COLUMN `utf` varchar(255) NOT NULL;
ALTER TABLE `dipl` MODIFY COLUMN `trans` varchar(255) NOT NULL;

ALTER TABLE `modern` MODIFY COLUMN `trans` varchar(255) NOT NULL;
ALTER TABLE `modern` MODIFY COLUMN `ascii` varchar(255) NOT NULL;
ALTER TABLE `modern` MODIFY COLUMN `utf` varchar(255) NOT NULL;

ALTER TABLE `page` MODIFY COLUMN `name` varchar(16) NOT NULL;

ALTER TABLE `tag_suggestion` MODIFY COLUMN `source` enum('user','auto') NOT NULL;

ALTER TABLE `text` MODIFY COLUMN `changed` timestamp;
ALTER TABLE `text` MODIFY COLUMN `changer_id` int(11) NOT NULL;
ALTER TABLE `text` MODIFY COLUMN `fullfile` text;

ALTER TABLE `tagger` MODIFY COLUMN `class_name` varchar(255) NOT NULL;

ALTER TABLE `tagger_options` MODIFY COLUMN `opt_value` varchar(255);

UNLOCK TABLES;

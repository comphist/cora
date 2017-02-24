-- 
-- Copyright (C) 2015 Marcel Bollmann <bollmann@linguistics.rub.de>
--
-- Permission is hereby granted, free of charge, to any person obtaining a copy of
-- this software and associated documentation files (the "Software"), to deal in
-- the Software without restriction, including without limitation the rights to
-- use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of
-- the Software, and to permit persons to whom the Software is furnished to do so,
-- subject to the following conditions:
--
-- The above copyright notice and this permission notice shall be included in all
-- copies or substantial portions of the Software.
--
-- THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
-- IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS
-- FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
-- COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
-- IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
-- CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
-- 
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

ALTER TABLE `users` MODIFY COLUMN `lastactive` timestamp DEFAULT 0;

ALTER TABLE `project` MODIFY COLUMN `cmd_edittoken` varchar(255) DEFAULT '';
ALTER TABLE `project` MODIFY COLUMN `cmd_import` varchar(255) DEFAULT '';

ALTER TABLE `comment` MODIFY COLUMN `value` varchar(255) DEFAULT '';
ALTER TABLE `comment` MODIFY COLUMN `subtok_id` bigint(20) DEFAULT NULL;

ALTER TABLE `dipl` MODIFY COLUMN `utf` varchar(255) DEFAULT '';
ALTER TABLE `dipl` MODIFY COLUMN `trans` varchar(255) DEFAULT '';

ALTER TABLE `modern` MODIFY COLUMN `trans` varchar(255) DEFAULT '';
ALTER TABLE `modern` MODIFY COLUMN `ascii` varchar(255) DEFAULT '';
ALTER TABLE `modern` MODIFY COLUMN `utf` varchar(255) DEFAULT '';

ALTER TABLE `page` MODIFY COLUMN `name` varchar(16) DEFAULT '';

ALTER TABLE `tag_suggestion` MODIFY COLUMN `source` enum('user','auto') NOT NULL DEFAULT 'user';

ALTER TABLE `text` MODIFY COLUMN `changed` timestamp DEFAULT 0;
ALTER TABLE `text` MODIFY COLUMN `changer_id` int(11) DEFAULT NULL;
ALTER TABLE `text` MODIFY COLUMN `fullfile` text DEFAULT NULL;

ALTER TABLE `tagger` MODIFY COLUMN `class_name` varchar(255) DEFAULT '';

ALTER TABLE `tagger_options` MODIFY COLUMN `opt_value` varchar(255) DEFAULT NULL;

UNLOCK TABLES;

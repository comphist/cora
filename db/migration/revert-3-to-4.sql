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
-- Version 4 introduces "comment" and "sec_comment" annotation layers

START TRANSACTION;

-- Re-create `subtok_id` column
ALTER TABLE `comment` ADD COLUMN `subtok_id` bigint(20) DEFAULT NULL;

-- Re-insert CorA comments into `comment`
INSERT INTO `comment` (`value`, `tok_id`, `subtok_id`, `comment_type`)
       SELECT `tag`.`value`,
              `modern`.`tok_id`,
              `modern`.`id`,
              'C'
       FROM `tag_suggestion` AS `ts`
       LEFT JOIN `tag` ON `tag`.`id`=`ts`.`tag_id`
       LEFT JOIN `tagset` ON `tagset`.`id`=`tag`.`tagset_id`
       LEFT JOIN `modern` ON `modern`.`id`=`ts`.`mod_id`
       WHERE `tagset`.`class`='comment';

-- Delete all from `tag_suggestion`, `tag`, `tagset`
-- referring to "sec_comment"s or "comment"s
DELETE `tag_suggestion`, `tag`, `tagset`
       FROM `tag_suggestion`
       LEFT JOIN `tag` ON `tag`.`id`=`tag_suggestion`.`tag_id`
       LEFT JOIN `tagset` ON `tag`.`tagset_id`=`tagset`.`id`
       WHERE `tagset`.`class`='sec_comment' OR `tagset`.`class`='comment';

-- Tags without any linked tag_suggestions
DELETE `tag`, `tagset`
       FROM `tag`
       LEFT JOIN `tagset` ON `tag`.`tagset_id`=`tagset`.`id`
       WHERE `tagset`.`class`='sec_comment' OR `tagset`.`class`='comment';

-- Tagsets without any linked tags
DELETE FROM `tagset` WHERE `class`='sec_comment';
DELETE FROM `tagset` WHERE `class`='comment';

COMMIT;

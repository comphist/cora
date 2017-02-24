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

-- "sec_comment" is new; we simply insert this line and are done with it
INSERT INTO `tagset` (`name`, `class`, `set_type`) VALUES
       ('Secondary Comment', 'sec_comment', 'open');

-- "comment" is a replacement for what was previously stored
-- in table `comment` with `comment_type`="C"
INSERT INTO `tagset` (`name`, `class`, `set_type`) VALUES
       ('Comment',           'comment',     'open');
SET @cid = LAST_INSERT_ID();

-- All texts previously had the ability to receive comments,
-- so link all existing texts to our new "comment" tagset
INSERT INTO `text2tagset` (`text_id`, `tagset_id`)
       SELECT `id`, @cid FROM `text`;

-- Similarly, make it the default for all existing projects
INSERT INTO `text2tagset_defaults` (`project_id`, `tagset_id`)
       SELECT `id`, @cid FROM `project`;

-- NOTE: In the following statements, we are selecting from `modern`,
--       not from `comment`, so that we only select rows with valid `subtok_id`
--       fields.  This foreign key restriction is not enforced on this field
--       (since not all comments are required to even have a `subtok_id`),
--       but is enforced on `tag_suggestion`.`mod_id`, so the insert will fail
--       if we happen to have invalid keys in our data.

-- Clone all CorA comments into the `tag` table
INSERT INTO `tag` (`value`, `tagset_id`)
       SELECT `comment`.`value`,
              @cid AS `tagset_id`
       FROM `modern`
       LEFT JOIN `comment` ON `comment`.`subtok_id`=`modern`.`id`
       WHERE `comment`.`comment_type`='C'
       ORDER BY `modern`.`id`;
SET @tag_first_id = LAST_INSERT_ID();

-- Link the inserted rows to their modern_ids in `tag_suggestion`
-- NOTE: It's important that we enumerate the rows (with @tag_id) in the same
--       order that they were inserted in; to do that, we need to wrap the
--       SELECT in a sub-query, and only add the enumerator on the outer query;
--       otherwise, MySQL will enumerate BEFORE applying the 'ORDER BY' clause!
INSERT INTO `tag_suggestion` (`selected`, `tag_id`, `mod_id`)
       SELECT 1 AS `selected`,
              @tag_id := @tag_id + 1 AS `tag_id`,
              x.`subtok_id` AS `mod_id`
       FROM  (SELECT `comment`.`subtok_id`
              FROM `modern`
              LEFT JOIN `comment` ON `comment`.`subtok_id`=`modern`.`id`
              WHERE `comment`.`comment_type`='C'
              ORDER BY `modern`.`id`) x
       CROSS JOIN (SELECT @tag_id := @tag_first_id - 1) r;

-- Finally, drop the comments from `comment`
-- NOTE: This will also drop comments with invalid `subtok_id`s (see above),
--       potentially losing data, but this data wasn't valid and/or accessible
--       by CorA anyway.
DELETE FROM `comment` WHERE `comment_type`='C';

-- `subtok_id` column is no longer needed
ALTER TABLE `comment` DROP COLUMN `subtok_id`;

COMMIT;

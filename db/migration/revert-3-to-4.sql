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

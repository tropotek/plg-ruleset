
-- ----------------------------------------------------
-- SQL
-- 
-- Author: Michael Mifsud <info@tropotek.com>
--
-- This is a major update to make all rulesets
--  associated to the subject rather than the profile
-- ----------------------------------------------------

-- Create Assert field
alter table rule add assert varchar(128) default '' not null after max;

-- Assign Assert classes
UPDATE `rule` SET `assert` = '\\Rs\\Assert\\IsTypeA' WHERE `id` = 1;
UPDATE `rule` SET `assert` = '\\Rs\\Assert\\IsTypeB' WHERE `id` = 2;
UPDATE `rule` SET `assert` = '\\Rs\\Assert\\IsTypeC' WHERE `id` = 3;
UPDATE `rule` SET `assert` = '\\Rs\\Assert\\IsTypeA' WHERE `id` = 4;
UPDATE `rule` SET `assert` = '\\Rs\\Assert\\IsTypeB' WHERE `id` = 5;
UPDATE `rule` SET `assert` = '\\Rs\\Assert\\IsTypeC' WHERE `id` = 6;
UPDATE `rule` SET `assert` = '\\Rs\\Assert\\HasAcademic' WHERE `id` = 7;
UPDATE `rule` SET `assert` = '\\Rs\\Assert\\IsRural' WHERE `id` = 8;
UPDATE `rule` SET `assert` = '\\Rs\\Assert\\IsUrban' WHERE `id` = 9;
UPDATE `rule` SET `assert` = '\\Rs\\Assert\\IsNational' WHERE `id` = 10;
UPDATE `rule` SET `assert` = '\\Rs\\Assert\\IsInternational' WHERE `id` = 11;
UPDATE `rule` SET `assert` = '\\Rs\\Assert\\IsResearch' WHERE `id` = 12;
UPDATE `rule` SET `assert` = '\\Rs\\Assert\\IsTypeD' WHERE `id` = 13;
UPDATE `rule` SET `assert` = '\\Rs\\Assert\\IsTypeE' WHERE `id` = 14;


alter table rule add uid INT default 0 not null after id;
UPDATE `rule` SET `uid` = id;

alter table rule add subject_id INT default 0 not null after profile_id;
create index rule_profile_id_index on rule (profile_id);
create index rule_subject_id_index on rule (subject_id);

-- Create new rules for each subject
INSERT INTO rule (uid, profile_id, subject_id, name, label, description, min, max, assert, script, del, order_by, modified, created)
  (
    SELECT b.uid, b.profile_id, a.id as 'subject_id', b.name, b.label, b.description, b.min, b.max, b.assert, b.script, b.del, b.order_by, b.modified, b.created
    FROM subject a, rule b
    WHERE a.course_id = b.profile_id
  )
;

-- Enable Plugins for all subjects with profiles enabled
INSERT INTO plugin_zone (plugin_name, zone_name, zone_id)
  (
    SELECT 'plg-ruleset' as 'plugin_name', 'subject' as 'zone_name', a.id
    FROM subject a, plugin_zone b
    WHERE b.plugin_name = 'plg-ruleset' AND a.course_id = b.zone_id
  )
;

-- Rules
UPDATE rule_has_placement a, placement b, rule c
  SET a.rule_id = c.id
  WHERE a.placement_id = b.id AND a.rule_id = c.uid AND b.subject_id = c.subject_id AND b.subject_id > 0
;


alter table rule add active TINYINT default 1 not null;
UPDATE `rule` t SET t.`active` = 0 WHERE t.`id` = 97;
UPDATE `rule` t SET t.`active` = 0 WHERE t.`id` = 98;
UPDATE `rule` t SET t.`active` = 0 WHERE t.`id` = 108;
UPDATE `rule` t SET t.`active` = 0 WHERE t.`id` = 109;
--



-- Fix Cindy Ho rules list
#UPDATE rule_has_placement a, placement b, rule c, rule d
#SET a.rule_id = d.id
#WHERE
#  a.placement_id = b.id
#    AND a.rule_id = c.id
#    AND c.uid = d.uid AND d.subject_id = 58
#    AND b.subject_id = 58
#    AND b.user_id = 2156
#;

























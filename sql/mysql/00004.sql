
-- ----------------------------------------------------
-- SQL
-- 
-- Author: Michael Mifsud <http://www.tropotek.com/>
--
-- Here we will be reverting the major ruleset upgrade
--  that made all rules subject associated, we will be
--  going back to profile associated
-- ----------------------------------------------------



-- --------------------------------------------------
-- Use this table to add options to a rule per subject
-- This then does not need to be created on a new subject creation
-- only when the options change from default
-- --------------------------------------------------
CREATE TABLE IF NOT EXISTS rule_subject (
  rule_id INT(10) UNSIGNED NOT NULL DEFAULT 0,
  subject_id INT(10) UNSIGNED NOT NULL DEFAULT 0,
  active TINYINT(1) NOT NULL DEFAULT 1,             -- default active
  PRIMARY KEY (rule_id, subject_id)
) ENGINE=InnoDB;


-- Revert all placements back to their UID rule without a subject ID
UPDATE rule_has_placement a, placement b, rule c
SET a.rule_id = c.uid
WHERE a.placement_id = b.id AND a.rule_id = c.id
;


-- create rule_subject entries for all rules that have false as active in their subject rule table
INSERT INTO rule_subject (rule_id, subject_id, active)
    (
      SELECT a.uid as 'rule_id', a.subject_id, a.active
      FROM rule a
      WHERE a.subject_id > 0
    )
;


-- Remove all rules where the subject_id > 0
DELETE FROM rule WHERE subject_id > 0;


-- Remove subject_id and active field from `rule` table
alter table rule drop column active;
drop index rule_subject_id_index on rule;
alter table rule drop column subject_id;

-- Remove all plugin enables for subject and revert back to profile plugin level
DELETE FROM plugin_zone WHERE `plugin_name` =  'plg-ruleset' AND `zone_name` = 'subject';








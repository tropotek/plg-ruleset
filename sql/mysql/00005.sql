
-- ----------------------------------------------------
-- SQL
-- 
-- Author: Michael Mifsud <info@tropotek.com>
--
-- ----------------------------------------------------



alter table rule change profile_id course_id int unsigned default 0 not null;
drop index rule_profile_id_index on rule;
create index course_id on rule (course_id);

alter table rule add static TINYINT(1) default 1 not null after max;
UPDATE rule SET static = 0 WHERE LENGTH(label) = 1 OR (label = 'small' OR label = 'prod' OR label = 'equine' OR label = 'other');




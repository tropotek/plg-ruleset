-- ---------------------------------
-- Install SQL
-- 
-- Author: Michael Mifsud <http://www.tropotek.com/>
-- ---------------------------------


INSERT INTO company_data (`fid`, `fkey`, `key`, `value`)
    (
    SELECT a.id, 'App\\Db\\Company', 'autoApprove', 'autoApprove'
    FROM plugin_zone b, company a LEFT JOIN company_data c ON (a.id = c.fid AND c.fkey = 'App\\Db\\Company' AND c.`key` = 'autoApprove')
    WHERE a.profile_id = b.zone_id AND b.plugin_name = 'plg-ruleset' AND b.zone_name = 'profile' AND c.fid IS NULL
    )
;











-- ---------------------------------
-- Install SQL
-- 
-- Author: Michael Mifsud <info@tropotek.com>
-- ---------------------------------


CREATE TABLE IF NOT EXISTS rule (
  id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  profile_id INT(10) UNSIGNED NOT NULL DEFAULT '0',
  name VARCHAR(128) NOT NULL DEFAULT '',
  label VARCHAR(128) NOT NULL DEFAULT '',
  description TEXT,
  min FLOAT NOT NULL DEFAULT '0',
  max FLOAT NOT NULL DEFAULT '0',
  script TEXT,
  del TINYINT(1) NOT NULL DEFAULT '0',
  order_by INT(11) NOT NULL,
  modified DATETIME NOT NULL,
  created DATETIME NOT NULL
) ENGINE=InnoDB;








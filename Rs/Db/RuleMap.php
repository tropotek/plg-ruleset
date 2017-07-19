<?php
namespace Rs\Db;

use Tk\Db\Tool;
use Tk\Db\Map\ArrayObject;
use Tk\DataMap\Db;
use Tk\DataMap\Form;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class RuleMap extends \App\Db\Mapper
{

    /**
     *
     * @return \Tk\DataMap\DataMap
     */
    public function getDbMap()
    {
        if (!$this->dbMap) {
            $this->dbMap = new \Tk\DataMap\DataMap();
            $this->dbMap->addPropertyMap(new Db\Integer('id'), 'key');
            $this->dbMap->addPropertyMap(new Db\Integer('profileId', 'profile_id'));
            $this->dbMap->addPropertyMap(new Db\Text('name'));
            $this->dbMap->addPropertyMap(new Db\Text('label'));
            $this->dbMap->addPropertyMap(new Db\Text('description'));
            $this->dbMap->addPropertyMap(new Db\Float('min'));
            $this->dbMap->addPropertyMap(new Db\Float('max'));
            $this->dbMap->addPropertyMap(new Db\Text('script'));
            $this->dbMap->addPropertyMap(new Db\Integer('orderBy', 'order_by'));
            $this->dbMap->addPropertyMap(new Db\Date('created'));
        }
        return $this->dbMap;
    }

    /**
     * @return \Tk\DataMap\DataMap
     */
    public function getFormMap()
    {
        if (!$this->formMap) {
            $this->formMap = new \Tk\DataMap\DataMap();
            $this->formMap->addPropertyMap(new Form\Integer('id'), 'key');
            $this->formMap->addPropertyMap(new Form\Integer('profileId'));
            $this->formMap->addPropertyMap(new Form\Text('name'));
            $this->formMap->addPropertyMap(new Form\Text('label'));
            $this->formMap->addPropertyMap(new Form\Text('description'));
            $this->formMap->addPropertyMap(new Form\Float('min'));
            $this->formMap->addPropertyMap(new Form\Float('max'));
            $this->formMap->addPropertyMap(new Form\Text('script'));
        }
        return $this->formMap;
    }


    /**
     * Find filtered records
     *
     * @param array $filter
     * @param Tool $tool
     * @return ArrayObject|Rule[]
     */
    public function findFiltered($filter = array(), $tool = null)
    {
        if (!$tool)
            $tool = \Tk\Db\Tool::create('a.order_by');

        $from = sprintf('%s a ', $this->quoteTable($this->getTable()));
        $where = '';

        if (!empty($filter['keywords'])) {
            $kw = '%' . $this->escapeString($filter['keywords']) . '%';
            $w = '';
            $w .= sprintf('a.name LIKE %s OR ', $this->quote($kw));
            $w .= sprintf('a.label LIKE %s OR ', $this->quote($kw));
            $w .= sprintf('a.description LIKE %s OR ', $this->quote($kw));
            if (is_numeric($filter['keywords'])) {
                $id = (int)$filter['keywords'];
                $w .= sprintf('a.id = %d OR ', $id);
            }
            if ($w) {
                $where .= '(' . substr($w, 0, -3) . ') AND ';
            }
        }

        if (!empty($filter['profileId'])) {
            $where .= sprintf('a.profile_id = %s AND ', (int)$filter['profileId']);
        }

        if (!empty($filter['name'])) {
            $where .= sprintf('a.name = %s AND ', $this->quote($filter['name']));
        }

        if (!empty($filter['label'])) {
            $where .= sprintf('a.label = %s AND ', $this->quote($filter['label']));
        }

        if (!empty($filter['placementId'])) {
            $from .= sprintf(' ,%s d', $this->quoteTable('rule_has_placement'));
            $where .= sprintf('a.id = d.rule_id AND d.placement_id = %s AND ', (int)$filter['placementId']);
        }

        
        if (!empty($filter['exclude'])) {
            if (!is_array($filter['exclude'])) $filter['exclude'] = array($filter['exclude']);
            $w = '';
            foreach ($filter['exclude'] as $v) {
                $w .= sprintf('a.id != %d AND ', (int)$v);
            }
            if ($w) {
                $where .= ' ('. rtrim($w, ' AND ') . ') AND ';
            }
        }

        if ($where) {
            $where = substr($where, 0, -4);
        }

        $res = $this->selectFrom($from, $where, $tool);
        return $res;
    }


    // TODO: ------------------------------------------------------

    /**
     * @param int $ruleId
     * @param int $placementId
     * @return boolean
     */
    public function hasPlacement($ruleId, $placementId)
    {
        $sql = sprintf('SELECT * FROM %s WHERE rule_id = %d AND placement_id = %d',
            $this->quoteTable('rule_has_placement'), (int)$ruleId, (int)$placementId);
        return ($this->getDb()->query($sql)->rowCount() > 0);
    }

    /**
     * @param int $ruleId
     * @param int $placementId (optional) If null all placements are to be removed
     */
    public function removePlacement($ruleId, $placementId = null)
    {
        $sup = '';
        if ($placementId) {
            $sup = sprintf(' AND placement_id = %d ', (int)$placementId);
        }
        $query = sprintf('DELETE FROM %s WHERE rule_id = %d %s',
            $this->quoteTable('rule_has_placement'), (int)$ruleId, $sup);
        $this->getDb()->exec($query);
    }

    /**
     * @param int $ruleId
     * @param int $placementId
     */
    public function addPlacement($ruleId, $placementId)
    {
        if ($this->hasPlacement($ruleId, $placementId)) return;
        $query = sprintf('INSERT INTO %s (rule_id, placement_id) VALUES (%d, %d) ',
            $this->quoteTable('rule_has_placement'), (int)$ruleId, (int)$placementId);
        $this->getDb()->exec($query);
    }

}
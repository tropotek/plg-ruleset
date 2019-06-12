<?php
namespace Rs\Controller;

use Tk\Form\Field;
use Tk\Request;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class RuleReport extends \App\Controller\AdminManagerIface
{

    /**
     * @var array
     */
    static public $calcCache = array();


    /**
     * Manager constructor.
     */
    public function __construct()
    {
        $this->setPageTitle('Rule Report');
    }

    /**
     * @param Request $request
     * @throws \Exception
     */
    public function doDefault(Request $request)
    {

        $this->setTable(\App\Config::getInstance()->createTable(\App\Config::getInstance()->getUrlName()));
        $this->getTable()->setRenderer(\App\Config::getInstance()->createTableRenderer($this->table));

        $this->getTable()->appendCell(new \Tk\Table\Cell\Text('uid'))->setLabel('Student Number');
        $this->getTable()->appendCell(new \Tk\Table\Cell\Text('name'));
        $this->getTable()->appendCell(new \Tk\Table\Cell\Email('email'))->addCss('key');

        $subject = $this->getSubject();
        $rules = \Rs\Db\RuleMap::create()->findFiltered(array('profileId' => $this->getProfileId(), 'subjectId' => $subject->getId()));

        foreach ($rules as $rule) {
            $this->getTable()->appendCell(new \Tk\Table\Cell\Text($rule->getLabel()))->setOnPropertyValue(function ($cell, $obj, $value) use ($rule, $subject) {
                /** @var \Tk\Table\Cell\Text $cell  */
                /** @var \App\Db\User $obj  */
                $tblFilter = $cell->getTable()->getFilterValues();
                if (empty(self::$calcCache[$obj->getId()])) {
                    $filter = array(
                        'userId' => $obj->getId(),
                        'subjectId' => $subject->getId(),
                        'status' => array(\App\Db\Placement::STATUS_APPROVED, \App\Db\Placement::STATUS_ASSESSING, \App\Db\Placement::STATUS_EVALUATING, \App\Db\Placement::STATUS_COMPLETED)
                    );
                    $placementList = \App\Db\PlacementMap::create()->findFiltered($filter);
                    self::$calcCache[$obj->getId()] = \Rs\Calculator::createFromPlacementList($placementList);
                }
                /** @var \Rs\Calculator $calc */
                $calc = self::$calcCache[$obj->getId()];

                $field = 'total';
                if (!empty($tblFilter['results']))
                    $field = $tblFilter['results'];

                if ($calc) {
                    $arr = $calc->getTotals();
                    if (isset($arr[$cell->getProperty()]))
                        return $arr[$cell->getProperty()][$field];
                }
                return '';
            });

        }

        // Filters
        $list = array('-- Status --' => '', 'Pending' => 'pending', 'Completed' => 'completed');
        $this->getTable()->appendFilter(new Field\Select('results', $list)); //->setLabel('Status');

        // Actions
        $this->getTable()->appendAction(\Tk\Table\Action\ColumnSelect::create()->setDisabled(array('id', 'name'))
            ->addUnselected('created')->addUnselected('description'));
        $this->getTable()->appendAction(\Tk\Table\Action\Csv::create());

        $this->getTable()->setList($this->getList());
    }

    /**
     * @return \Rs\Db\Rule[]|\Tk\Db\Map\ArrayObject
     * @throws \Exception
     */
    protected function getList()
    {
        $filter = $this->getTable()->getFilterValues();
        $filter['subjectId'] = $this->getSubject()->getId();
        $filter['type'] = \Uni\Db\Role::TYPE_STUDENT;

        return \App\Db\UserMap::create()->findFiltered($filter, $this->table->getTool('a.name'));
    }

    /**
     * @return \Dom\Template
     */
    public function show()
    {
        $template = parent::show();

        $template->appendTemplate('panel', $this->getTable()->getRenderer()->show());

        return $template;
    }

    /**
     * DomTemplate magic method
     *
     * @return \Dom\Template
     */
    public function __makeTemplate()
    {
        $xhtml = <<<HTML
<div class="tk-panel" data-panel-title="Rule Report" data-panel-icon="fa fa-check" var="panel"></div>
HTML;

        return \Dom\Loader::load($xhtml);
    }


}


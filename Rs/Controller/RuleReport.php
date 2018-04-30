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
     * @var \App\Db\Profile
     */
    private $profile = null;

    /**
     * Manager constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->setPageTitle('Rule Report');
    }

    /**
     * @param Request $request
     * @throws \Exception
     * @throws \Tk\Db\Exception
     */
    public function doDefault(Request $request)
    {
        $this->profile = $this->getProfile();

        $this->table = \App\Config::getInstance()->createTable(\Tk\Object::basename($this).'ruleReport');
        $this->table->setRenderer(\App\Config::getInstance()->createTableRenderer($this->table));

        $this->table->addCell(new \Tk\Table\Cell\Text('uid'))->setLabel('Student Number');
        $this->table->addCell(new \Tk\Table\Cell\Text('name'));
        $this->table->addCell(new \Tk\Table\Cell\Email('email'))->addCss('key');

        $rules = \Rs\Db\RuleMap::create()->findFiltered(array('profileId' => $this->getProfile()->getId()));
        $subject = $this->getSubject();

        foreach ($rules as $rule) {
            $this->table->addCell(new \Tk\Table\Cell\Text($rule->getLabel()))->setOnPropertyValue(function ($cell, $obj, $value) use ($rule, $subject) {
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
        $this->table->addFilter(new Field\Select('results', $list)); //->setLabel('Status');

        // Actions
        $this->table->addAction(\Tk\Table\Action\ColumnSelect::create()->setDisabled(array('id', 'name'))
            ->addUnselected('created')->addUnselected('description'));
        $this->table->addAction(\Tk\Table\Action\Csv::create());

        $this->table->setList($this->getList());
    }

    /**
     * @return \Rs\Db\Rule[]|\Tk\Db\Map\ArrayObject
     * @throws \Exception
     */
    protected function getList()
    {
        $filter = $this->table->getFilterValues();
        $filter['subjectId'] = $this->getSubject()->getId();
        $filter['role'] = array(\App\Db\UserGroup::ROLE_STUDENT);

        return \App\Db\UserMap::create()->findFiltered($filter, $this->table->getTool('a.name'));
    }

    /**
     * @return \Dom\Template
     */
    public function show()
    {
        $template = parent::show();

        $template->replaceTemplate('table', $this->table->getRenderer()->show());

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
<div>

  <div class="panel panel-default">
    <div class="panel-heading">
      <h4 class="panel-title"><i class="fa fa-check"></i> Rule Report</h4>
    </div>
    <div class="panel-body">
      <div var="table"></div>
    </div>
  </div>

</div>
HTML;

        return \Dom\Loader::load($xhtml);
    }


}


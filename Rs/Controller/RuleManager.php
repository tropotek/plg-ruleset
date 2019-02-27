<?php
namespace Rs\Controller;

use Tk\Form\Field;
use Tk\Request;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class RuleManager extends \App\Controller\AdminManagerIface
{


    /**
     * Manager constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->setPageTitle('Rule Manager');
    }

    /**
     * @param Request $request
     * @throws \Exception
     */
    public function doDefault(Request $request)
    {

        $editUrl = \App\Uri::createSubjectUrl('/ruleEdit.html');

        $this->getActionPanel()->add(\Tk\Ui\Button::create('New Rule', $editUrl, 'fa fa-check fa-add-action'));

        $this->table = \App\Config::getInstance()->createTable(\App\Config::getInstance()->getUrlName());
        $this->table->setRenderer(\App\Config::getInstance()->createTableRenderer($this->table));

        $this->table->appendCell(new \Tk\Table\Cell\Checkbox('id'));
        $this->table->appendCell(new \Tk\Table\Cell\Text('name'))->addCss('key')->setUrl(clone $editUrl);
        $this->table->appendCell(new \Tk\Table\Cell\Text('description'));
        $this->table->appendCell(new \Tk\Table\Cell\Text('label'));
        $this->table->appendCell(new \Tk\Table\Cell\Text('assert'));
        $this->table->appendCell(new \Tk\Table\Cell\Boolean('active'));
        $this->table->appendCell(new \Tk\Table\Cell\Text('min'));
        $this->table->appendCell(new \Tk\Table\Cell\Text('max'));
        $this->table->appendCell(new \Tk\Table\Cell\Date('created'));
        $this->table->appendCell(new \Tk\Table\Cell\OrderBy('orderBy'));

        // Filters
        $this->table->appendFilter(new Field\Input('keywords'))->setAttr('placeholder', 'Keywords');

        // Actions
        $this->table->appendAction(\Tk\Table\Action\ColumnSelect::create()->setDisabled(array('id', 'name'))
            ->addUnselected('created')->addUnselected('description'));
        $this->table->appendAction(\Tk\Table\Action\Csv::create());
        $this->table->appendAction(\Tk\Table\Action\Delete::create());

        $this->table->setList($this->getList());
    }

    /**
     * @return \Rs\Db\Rule[]|\Tk\Db\Map\ArrayObject
     * @throws \Exception
     */
    protected function getList()
    {
        $filter = $this->table->getFilterValues();
        $filter['subjectId'] = $this->getConfig()->getSubjectId();
        return \Rs\Db\RuleMap::create()->findFiltered($filter, $this->table->getTool('a.order_by'));
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
      <h4 class="panel-title"><i class="fa fa-check"></i> Rule Manager</h4>
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


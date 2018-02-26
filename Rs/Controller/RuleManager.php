<?php
namespace Rs\Controller;

use Tk\Form\Field;
use Tk\Request;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class RuleManager extends \App\Controller\AdminManagerIface
{

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
        $this->setPageTitle('Rule Manager');
    }

    /**
     * @param Request $request
     * @throws \Tk\Form\Exception
     */
    public function doDefault(Request $request)
    {
        if ($request->get('profileId')) {
            $this->profile = \App\Db\ProfileMap::create()->find($request->get('profileId'));
        }

        $editUrl = \App\Uri::createHomeUrl('/ruleEdit.html')->set('profileId', $this->profile->getId());

        $this->getActionPanel()->add(\Tk\Ui\Button::create('New Rule', $editUrl, 'fa fa-check fa-add-action'));

        $this->table = \App\Config::getInstance()->createTable(\Tk\Object::basename($this).'ruleList');
        $this->table->setRenderer(\App\Config::getInstance()->createTableRenderer($this->table));

        $this->table->addCell(new \Tk\Table\Cell\Checkbox('id'));
        $this->table->addCell(new \Tk\Table\Cell\Text('name'))->addCss('key')->setUrl(clone $editUrl);
        $this->table->addCell(new \Tk\Table\Cell\Text('description'));
        $this->table->addCell(new \Tk\Table\Cell\Text('label'));
        $this->table->addCell(new \Tk\Table\Cell\Text('min'));
        $this->table->addCell(new \Tk\Table\Cell\Text('max'));
        $this->table->addCell(new \Tk\Table\Cell\Date('created'));
        $this->table->addCell(new \Tk\Table\Cell\OrderBy('orderBy'));

        // Filters
        $this->table->addFilter(new Field\Input('keywords'))->setAttr('placeholder', 'Keywords');

        // Actions
        $this->table->addAction(\Tk\Table\Action\ColumnSelect::create()->setDisabled(array('id', 'name'))
            ->addUnselected('created')->addUnselected('description'));
        $this->table->addAction(\Tk\Table\Action\Csv::create());
        $this->table->addAction(\Tk\Table\Action\Delete::create());

        $this->table->setList($this->getList());
    }

    /**
     * @return \Rs\Db\Rule[]|\Tk\Db\Map\ArrayObject
     */
    protected function getList()
    {
        $filter = $this->table->getFilterValues();
        $filter['profileId'] = $this->profile->getId();
        return \Rs\Db\RuleMap::create()->findFiltered($filter, $this->table->makeDbTool('a.order_by'));
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


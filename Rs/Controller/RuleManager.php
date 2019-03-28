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
        if ($request->get('action') == 'update')
            return $this->doUpdate($request);


        $this->table = \App\Config::getInstance()->createTable(\App\Config::getInstance()->getUrlName());
        $this->table->setRenderer(\App\Config::getInstance()->createTableRenderer($this->table));

        $editUrl = null;
        if(!$this->getConfig()->isSubjectUrl()) {
            $editUrl = \App\Uri::createHomeUrl('/ruleEdit.html');
        }

        $this->table->appendCell(new \Tk\Table\Cell\Checkbox('id'));
        $this->table->appendCell(new \Tk\Table\Cell\Text('name'))->addCss('key')->setUrl($editUrl);
        $this->table->appendCell(new \Tk\Table\Cell\Text('description'));
        $this->table->appendCell(new \Tk\Table\Cell\Text('label'));
        $this->table->appendCell(new \Tk\Table\Cell\Text('assert'));
        $this->table->appendCell(new \Tk\Table\Cell\Text('min'));
        $this->table->appendCell(new \Tk\Table\Cell\Text('max'));
        $this->table->appendCell(new \Tk\Table\Cell\Date('created'));
        if (!$this->getConfig()->isSubjectUrl()) {
            $this->table->appendCell(new \Tk\Table\Cell\OrderBy('orderBy'));
        } else {
            $this->table->appendCell(new \Tk\Table\Cell\Checkbox('activeCb'))->setLabel('Active')->setUseValue(true)
                ->setOnPropertyValue(function ($cell, $obj, $value) {
                    /** @var $cell \Tk\Table\Cell\Checkbox */
                    /** @var $obj \Rs\Db\Rule */
                    $subjectId = \App\Config::getInstance()->getSubjectId();
                    $cell->setAttr('data-url', \Tk\Uri::create()->set('active'));
                    $cell->setAttr('data-rule-id', $obj->getId());
                    $cell->setAttr('data-subject-id', $subjectId);
                    $cell->addCss('tk-ajax-checkbox');
                    return $obj->isActive($subjectId);
                });
        }
        // Filters
        $this->table->appendFilter(new Field\Input('keywords'))->setAttr('placeholder', 'Keywords');

        // Actions
        $this->table->appendAction(\Tk\Table\Action\ColumnSelect::create()->setDisabled(array('id', 'name'))
            ->addUnselected('created')->addUnselected('description'));
        $this->table->appendAction(\Tk\Table\Action\Csv::create());
        if(!$this->getConfig()->isSubjectUrl()) {
            $this->table->appendAction(\Tk\Table\Action\Delete::create());
        }

        $this->table->setList($this->getList());
    }

    /**
     * @param Request $request
     * @return \Tk\Response
     */
    public function doUpdate(Request $request)
    {
        if ((int)$request->get('ruleId') && (int)$request->get('subjectId') && $request->has('value'))
            \Rs\Db\RuleMap::create()->setActive((int)$request->get('ruleId'), (int)$request->get('subjectId'), $request->get('value') == 'true');
        return \Tk\ResponseJson::createJson(array('status' => 'ok'));
    }

    /**
     * @return \Rs\Db\Rule[]|\Tk\Db\Map\ArrayObject
     * @throws \Exception
     */
    protected function getList()
    {
        $filter = $this->table->getFilterValues();
        $filter['profileId'] = $this->getProfileId();
        return \Rs\Db\RuleMap::create()->findFiltered($filter, $this->table->getTool('a.order_by'));
    }

    /**
     * @return \Dom\Template
     */
    public function show()
    {
        if (!$this->getConfig()->isSubjectUrl())
            $this->getActionPanel()->append(\Tk\Ui\Link::createBtn('New Rule',
                \App\Uri::createHomeUrl('/ruleEdit.html')->set('profileId', $this->getProfileId()), 'fa fa-check fa-add-action'));

        $template = parent::show();
        $template->replaceTemplate('table', $this->table->getRenderer()->show());
        if ($this->getConfig()->isSubjectUrl()) {
            $template->setChoice('subjectUrl');
            $template->setAttr('rulesManager', 'href', \App\Uri::createHomeUrl('/profileEdit.html')->set('profileId', $this->getProfileId()));
            $js = <<<JS
jQuery(function ($) {
  
    // Fire off the href url as an ajax call, handy for updates and deletes
    $('.tk-ajax-checkbox input[type="checkbox"]').on('change', function (e) {
      var cb = $(this);
      var data = $(this).parent().data();
      var url = data.url;
      cb.attr('disabled', 'disabled');
      
      var params = $.extend({action: 'update', value: cb.prop('checked')}, data);
      $.post(url, params).done(function (data) {
        cb.removeAttr('disabled');
      });
      
      return false;
    });
    
});
JS;
            $template->appendJs($js);
        }

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
      
      <p choice="subjectUrl">
        NOTE: You can only activate and deactivate rules from here. 
        Use the <a herf="#" var="rulesManager">Profile Rules Manager</a> to edit the rule records.
      </p>
      
      <div var="table"></div>
    </div>
  </div>

</div>
HTML;

        return \Dom\Loader::load($xhtml);
    }


}


<?php
namespace Rs\Controller;

use Dom\Template;
use Tk\Form\Event;
use Tk\Form\Field;
use Tk\Request;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class RuleEdit extends \App\Controller\AdminEditIface
{

    /**
     * @var \Rs\Db\Rule
     */
    protected $rule = null;


    /**
     * Iface constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->setPageTitle('Rule Edit');
    }

    /**
     * @param Request $request
     * @throws \Exception
     */
    public function doDefault(Request $request)
    {
        $this->rule = \Rs\Db\RuleMap::create()->find($request->get('ruleId'));
        if (!$this->rule) {
            $this->rule = new \Rs\Db\Rule();
            $this->rule->profileId = $request->get('profileId');
        }

        $this->buildForm();
        if ($this->rule->getId() && $this->getConfig()->isSubjectUrl()) {
            $this->form->load(array(
                'active' => $this->rule->isActive($this->getSubjectId())
            ));
        }
        $this->form->load(\Rs\Db\RuleMap::create()->unmapForm($this->rule));
        $this->form->execute($request);

    }

    /**
     *
     * @throws \Exception
     */
    protected function buildForm() 
    {
        $this->form = \App\Config::getInstance()->createForm('ruleEdit');
        $this->form->setRenderer(\App\Config::getInstance()->createFormRenderer($this->form));

        $this->form->appendField(new Field\Input('name'));
        $this->form->appendField(new Field\Input('label'));
        $this->form->appendField(new \App\Form\Field\MinMax('min', 'max'));
        $this->form->appendField(new Field\Input('description'));
        if ($this->getConfig()->isSubjectUrl()) {
            $this->form->appendField(new Field\Checkbox('active'))->setValue(true);
        }

        $list = \Rs\Db\Rule::getAssertList($this->rule->assert);
        $this->form->appendField(new Field\Select('assert', $list))->prependOption('-- None --', '');
        $this->form->appendField(new Field\Textarea('script'))->addCss('code')->setAttr('data-mode', 'text/x-php')
        ->setNotes('NOTE: This will be deprecated in the future in favor of the Assert field above.');

        $this->form->appendField(new Event\Submit('update', array($this, 'doSubmit')));
        $this->form->appendField(new Event\Submit('save', array($this, 'doSubmit')));
        $this->form->appendField(new Event\Link('cancel', $this->getConfig()->getBackUrl()));
    }

    /**
     * @param \Tk\Form $form
     * @param \Tk\Form\Event\Iface $event
     * @throws \Exception
     */
    public function doSubmit($form, $event)
    {
        // Load the object with data from the form using a helper object
        \Rs\Db\RuleMap::create()->mapForm($form->getValues(), $this->rule);

        $form->addFieldErrors($this->rule->validate());

        if ($form->hasErrors()) {
            return;
        }

        $this->rule->save();

        if ($form->getField('active') && $this->getConfig()->isSubjectUrl()) {
            \Rs\Db\RuleMap::create()->setActive($this->rule->getId(), $this->getSubjectId(), $form->getFieldValue('active'));
        }

        \Tk\Alert::addSuccess('Record saved!');
        $event->setRedirect($this->getConfig()->getBackUrl());
        if ($form->getTriggeredEvent()->getName() == 'save') {
            $event->setRedirect(\Tk\Uri::create()->set('ruleId', $this->rule->getId()));
        }
    }

    /**
     * @return \Dom\Template
     */
    public function show()
    {
        $template = parent::show();

        // Render the form
        $template->insertTemplate('form', $this->form->getRenderer()->show());

        return $template;
    }

    /**
     * DomTemplate magic method
     *
     * @return Template
     */
    public function __makeTemplate()
    {
        $xhtml = <<<HTML
<div>
    
  <div class="panel panel-default">
    <div class="panel-heading">
      <h4 class="panel-title"><i class="fa fa-check"></i> <span var="panel-title">Rule Edit</span></h4>
    </div>
    <div class="panel-body">
      <div var="form"></div>
    </div>
  </div>
    
</div>
HTML;

        return \Dom\Loader::load($xhtml);
    }

}
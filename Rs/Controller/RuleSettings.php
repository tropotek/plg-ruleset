<?php
namespace Rs\Controller;

use Tk\Request;
use Tk\Form;
use Tk\Form\Event;
use Tk\Form\Field;
use Rs\Plugin;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class RuleSettings extends \App\Controller\AdminEditIface
{

    /**
     * @var \Tk\Db\Data|null
     */
    protected $data = null;

    /**
     * @var \App\Db\Profile
     */
    private $profile = null;


    /**
     * ProfileSettings constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->setPageTitle('Rule Settings');
    }


    /**
     * doDefault
     *
     * @param Request $request
     * @throws \Exception
     */
    public function doDefault(Request $request)
    {
        $plugin = Plugin::getInstance();
        $this->profile = $this->getProfile();
        if (!$this->profile)
            $this->profile = \App\Db\ProfileMap::create()->find($request->get('zoneId'));
        if (!$this->profile)
            throw new \Tk\Exception('Profile Not Found!');

        $this->data = \Tk\Db\Data::create($plugin->getName() . '.subject.profile', $this->profile->getId());

        $this->form = \App\Config::getInstance()->createForm('formEdit');
        $this->form->setRenderer(\App\Config::getInstance()->createFormRenderer($this->form));

        $this->form->appendField(new Field\Textarea('plugin.company.get.class'))->setLabel('Company Category Class')
            ->setNotes('Add custom code to modify the company class calculation of Company::getCategoryClass() method<br/><em>Warning: This is deprecated as each company should only have one category class.</em>')
            ->setRequired(true)->addCss('code')->setAttr('data-mode', 'text/x-php');

        $this->form->appendField(new Field\Checkbox('plugin.active'))
            ->setCheckboxLabel('Enable/disable the rules and auto approval system for this profile.')
            ->setLabel('Active')->setRequired(true);

        $this->form->appendField(new Event\Submit('update', array($this, 'doSubmit')));
        $this->form->appendField(new Event\Submit('save', array($this, 'doSubmit')));
        $this->form->appendField(new Event\LinkButton('cancel', $this->getConfig()->getBackUrl()));

        $this->form->load($this->data->toArray());
        $this->form->execute();
    }

    /**
     * doSubmit()
     *
     * @param Form $form
     * @param \Tk\Form\Event\Iface $event
     * @throws \Exception
     */
    public function doSubmit($form, $event)
    {
        $values = $form->getValues();
        $this->data->replace($values);

        if ($this->form->hasErrors()) {
            return;
        }

        $this->data->save();

        \Tk\Alert::addSuccess('Settings saved.');
        $event->setRedirect($this->getConfig()->getBackUrl());
        if ($form->getTriggeredEvent()->getName() == 'save') {
            $event->setRedirect(\Tk\Uri::create());
        }
    }

    /**
     * show()
     *
     * @return \Dom\Template
     */
    public function show()
    {

        $this->getActionPanel()->append(\Tk\Ui\Link::createBtn('Rules', \App\Uri::createHomeUrl('/ruleManager.html')
            ->set('profileId', $this->profile->getId()), 'fa fa-check'));

        $template = parent::show();
        
        // Render the form
        $template->insertTemplate($this->form->getId(), $this->form->getRenderer()->show());


        return $template;
    }

    /**
     * DomTemplate magic method
     *
     * @return \Dom\Template
     */
    public function __makeTemplate()
    {
        $xhtml = <<<XHTML
<div var="content">
  
  <div class="panel panel-default">
    <div class="panel-heading">
      <h4 class="panel-title"><i class="fa fa-cog"></i> <span>Settings</span> </h4>
    </div>
    <div class="panel-body">
      <div var="formEdit"></div>
    </div>
  </div>
  
</div>
XHTML;

        return \Dom\Loader::load($xhtml);
    }
}
<?php
namespace Rs\Controller;

use Tk\Request;
use Tk\Form;
use Tk\Form\Event;
use Tk\Form\Field;
use Rs\Plugin;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class ProfileSettings extends \App\Controller\AdminIface
{

    /**
     * @var Form
     */
    protected $form = null;

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
     * @throws Form\Exception
     * @throws \Exception
     * @throws \Tk\Db\Exception
     */
    public function doDefault(Request $request)
    {
        $plugin = Plugin::getInstance();
        $this->profile = \App\Db\ProfileMap::create()->find($request->get('zoneId'));
        if (!$this->profile)
            $this->profile = \App\Db\ProfileMap::create()->find($request->get('profileId'));

        $this->getActionPanel()->add(\Tk\Ui\Button::create('Rules', \App\Uri::createHomeUrl('/ruleManager.html')->
            set('profileId', $this->profile->getId()), 'fa fa-check'));

        $this->data = \Tk\Db\Data::create($plugin->getName() . '.subject.profile', $this->profile->getId());

        $this->form = \App\Config::getInstance()->createForm('formEdit');
        $this->form->setRenderer(\App\Config::getInstance()->createFormRenderer($this->form));

        $this->form->addField(new Field\Textarea('plugin.company.get.class'))->setLabel('Company Category Class')->
            setNotes('Add custom code to modify the company class calculation of Company::getCategoryClass() method')->
            addCss('tkCode')->setRequired(true);

        $this->form->addField(new Field\Checkbox('plugin.active'))->
            setNotes('Enable/disable the rules and auto approval system for this profile.')->
            setLabel('Active')->setRequired(true);
        
        $this->form->addField(new Event\Submit('update', array($this, 'doSubmit')));
        $this->form->addField(new Event\Submit('save', array($this, 'doSubmit')));
        $this->form->addField(new Event\LinkButton('cancel', \Uni\Ui\Crumbs::getInstance()->getBackUrl()));

        $this->form->load($this->data->toArray());
        $this->form->execute();

    }

    /**
     * doSubmit()
     *
     * @param Form $form
     * @throws \Tk\Db\Exception
     */
    public function doSubmit($form)
    {
        $values = $form->getValues();
        $this->data->replace($values);

        if ($this->form->hasErrors()) {
            return;
        }
        
        $this->data->save();
        
        \Tk\Alert::addSuccess('Settings saved.');
        if ($form->getTriggeredEvent()->getName() == 'update') {
            \App\Uri::createHomeUrl('/profileEdit.html')->set('profileId', $this->profile->getId())->redirect();
        }
        \Tk\Uri::create()->redirect();
    }

    /**
     * show()
     *
     * @return \Dom\Template
     */
    public function show()
    {
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
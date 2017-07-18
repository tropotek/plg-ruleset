<?php
namespace Rs\Controller;

use Tk\Request;
use Tk\Form;
use Tk\Form\Event;
use Tk\Form\Field;

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
        $this->setPageTitle('Placement Rules Settings');
    }

    /**
     * doDefault
     *
     * @param Request $request
     */
    public function doDefault(Request $request)
    {
        $plugin = \Rs\Plugin::getInstance();
        $this->profile = \App\Db\ProfileMap::create()->find($request->get('zoneId'));

        $this->getActionPanel()->addButton(\Tk\Ui\Button::create('Rules', \App\Uri::createHomeUrl('/ruleManager.html')->
            set('profileId', $this->profile->getId()), 'fa fa-list-alt'));

        $this->data = \Tk\Db\Data::create($plugin->getName() . '.course.profile', $this->profile->getId());

        $this->form = \App\Factory::createForm('formEdit');
        $this->form->setParam('renderer', \App\Factory::createFormRenderer($this->form));

        $this->form->addField(new Field\Textarea('plugin.company.get.class'))->setLabel('Company Category Class')->
            setNotes('Add custom code to modify the company class calculation of Company::getCategoryClass() method')->
            addCss('tkCode')->setRequired(true);
        $this->form->addField(new Field\Checkbox('plugin.active'))->
            setNotes('Deactivate the rules and auto approval system for this course profile.')->
            setLabel('Active')->setRequired(true);
        
        $this->form->addField(new Event\Button('update', array($this, 'doSubmit')));
        $this->form->addField(new Event\Button('save', array($this, 'doSubmit')));
        $this->form->addField(new Event\LinkButton('cancel', \App\Factory::getCrumbs()->getBackUrl()));

        $this->form->load($this->data->toArray());
        $this->form->execute();

    }

    /**
     * doSubmit()
     *
     * @param Form $form
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
            \App\Uri::createHomeUrl('/course/profilePlugins.html')->set('profileId', $this->profile->getId())->redirect();
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
        $template->insertTemplate($this->form->getId(), $this->form->getParam('renderer')->show()->getTemplate());

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
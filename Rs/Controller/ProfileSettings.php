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
     * track the previous active state of the plugin
     * @var bool
     */
    private $prevActive = false;


    /**
     * ProfileSettings constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->setPageTitle('Rule Settings');
    }

    /**
     * @return \App\Db\Profile|null
     */
    public function getProfile()
    {
        return $this->profile;
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
        $this->profile = \App\Db\ProfileMap::create()->find($request->get('zoneId'));
        if (!$this->profile)
            $this->profile = \App\Db\ProfileMap::create()->find($request->get('profileId'));

        $this->getActionPanel()->add(\Tk\Ui\Button::create('Rules', \App\Uri::createHomeUrl('/ruleManager.html')
            ->set('profileId', $this->profile->getId()), 'fa fa-check'));

        $this->data = \Tk\Db\Data::create($plugin->getName() . '.subject.profile', $this->profile->getId());

        $this->form = \App\Config::getInstance()->createForm('formEdit');
        $this->form->setRenderer(\App\Config::getInstance()->createFormRenderer($this->form));

        $this->form->addField(new Field\Textarea('plugin.company.get.class'))->setLabel('Company Category Class')
            ->setNotes('Add custom code to modify the company class calculation of Company::getCategoryClass() method')
            ->setRequired(true)->addCss('code')->setAttr('data-mode', 'text/x-php');

        $this->form->addField(new Field\Checkbox('plugin.active'))
            ->setCheckboxLabel('Enable/disable the rules and auto approval system for this profile.')
            ->setLabel('Active')->setRequired(true);
        
        $this->form->addField(new Event\Submit('update', array($this, 'doSubmit')));
        $this->form->addField(new Event\Submit('save', array($this, 'doSubmit')));
        $this->form->addField(new Event\LinkButton('cancel', $this->getConfig()->getBackUrl()));


        $this->prevActive = ($this->data->get('plugin.active') == 'plugin.active');

        $this->form->load($this->data->toArray());
        $this->form->execute();
    }

    /**
     * doSubmit()
     *
     * @param Form $form
     * @param \Tk\Form\Event\Iface $event
     * @throws \Tk\Db\Exception
     */
    public function doSubmit($form, $event)
    {
        $values = $form->getValues();
        $this->data->replace($values);

        if ($this->form->hasErrors()) {
            return;
        }

        $this->data->save();

        // Set all company.autoApprove data field status to true
        if (!$this->prevActive && ($this->data->get('plugin.active') == 'plugin.active'))
        {
            $sql = <<<SQL
INSERT INTO company_data (`fid`, `fkey`, `key`, `value`)
    (
        SELECT a.id, 'App\\Db\\Company', 'autoApprove', 'autoApprove'
        FROM plugin_zone b, company a LEFT JOIN company_data c ON (a.id = c.fid AND c.fkey = 'App\\Db\\Company' AND c.`key` = 'autoApprove')
        WHERE b.zone_Id = ? AND a.profile_id = b.zone_id AND b.plugin_name = 'plg-ruleset' AND b.zone_name = 'profile' AND c.fid IS NULL
    )
SQL;
            $stm = $this->getConfig()->getDb()->prepare($sql);
            $stm->execute(array($this->profile->getId()));
        }

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
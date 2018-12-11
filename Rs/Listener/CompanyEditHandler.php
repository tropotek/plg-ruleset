<?php
namespace Rs\Listener;

use Tk\Event\Subscriber;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class CompanyEditHandler implements Subscriber
{

    /**
     * @var null|\App\Controller\Company\Edit
     */
    protected $controller = null;



    /**
     * @param \Tk\Event\ControllerEvent $event
     */
    public function onControllerInit(\Tk\Event\ControllerEvent $event)
    {
        $controller = $event->getControllerObject();
        if ($controller instanceof \App\Controller\Company\Edit && $controller->getConfig()->getUser()->isStaff()) {
            $plugin  =\Rs\Plugin::getInstance();
            $profilePluginData = \Tk\Db\Data::create($plugin->getName() . '.subject.profile', $controller->getProfileId());
            if ($profilePluginData->get('plugin.active')) {
                $this->controller = $controller;
            }
        }
    }

    /**
     * @param \Tk\Event\FormEvent $event
     * @throws \Exception
     */
    public function onFormInit(\Tk\Event\FormEvent $event)
    {
        if (!$this->controller) return;

        /** @var \Tk\Form $form */
        $form = $event->getForm();

        $form->appendField(new \Tk\Form\Field\Checkbox('autoApprove'), 'web')->setTabGroup('Details')
            ->setCheckboxLabel('Placements applying with this company can be Auto-Approved.');

        if ($form->getField('update'))
            $form->getField('update')->appendCallback(array($this, 'doSubmit'));
        if ($form->getField('save'))
            $form->getField('save')->appendCallback(array($this, 'doSubmit'));

    }

    /**
     * @param \Tk\Event\FormEvent $event
     * @throws \Exception
     */
    public function onFormLoad(\Tk\Event\FormEvent $event)
    {
        if (!$this->controller) return;
        /** @var \Tk\Form $form */
        $form = $event->getForm();
        $company = $this->controller->getCompany();

        $data = array('autoApprove' => $company->getData()->get('autoApprove'));
        $form->load($data);

    }

    /**
     * @param \Tk\Form $form
     * @param \Tk\Form\Event\Iface $event
     * @throws \Exception
     */
    public function doSubmit(\Tk\Form $form, \Tk\Form\Event\Iface $event)
    {
        // Load the object with data from the form using a helper object
        //\App\Db\CompanyMap::create()->mapForm($form->getValues(), $this->company);
        //vd($form->getValues());
        $this->controller->getCompany()->getData()->set('autoApprove', $form->getFieldValue('autoApprove'));

        //vd($form->getFieldValue('autoApprove'));
        $this->controller->getCompany()->getData()->save();
    }

    /**
     * @param \Tk\Event\Event $event
     * @throws \Exception
     */
    public function onControllerShow(\Tk\Event\Event $event)
    {
        if (!$this->controller) return;
        $template = $this->controller->getTemplate();

    }



    /**
     * @return array The event names to listen to
     * @api
     */
    public static function getSubscribedEvents()
    {
        return array(
            \Tk\Kernel\KernelEvents::CONTROLLER => array('onControllerInit', 0),
            \Tk\Form\FormEvents::FORM_INIT => array('onFormInit', 0),
            \Tk\Form\FormEvents::FORM_LOAD => array('onFormLoad', 0),
            \Tk\PageEvents::CONTROLLER_SHOW => array('onControllerShow', 0)
        );
    }

    
}
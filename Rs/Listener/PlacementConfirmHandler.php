<?php
namespace Rs\Listener;

use Tk\Event\Subscriber;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class PlacementConfirmHandler implements Subscriber
{

    /**
     * @var null|\App\Controller\Placement\Edit
     */
    protected $controller = null;

    /**
     * @var null|\App\Db\Placement
     */
    protected $placement = null;


    /**
     * @param \Tk\Event\ControllerEvent $event
     */
    public function onControllerInit(\Tk\Event\ControllerEvent $event)
    {
        $controller = $event->getController();
        if ($controller instanceof \App\Controller\Student\Placement\Confirm) {
            $this->controller = $controller;
        }
    }

    /**
     * @param \Tk\Event\FormEvent $event
     * @throws \Tk\Form\Exception
     */
    public function onFormInit(\Tk\Event\FormEvent $event)
    {
        /** @var \Tk\Form $form */
        $form = $event->getForm();
        if ($this->controller) {
            $this->placement = $this->controller->getPlacement();

            if ($form->getField('submitForApproval'))
                $form->addEventCallback('submitForApproval', array($this, 'doSubmit'));

        }
    }

    /**
     * @param \Tk\Form $form
     * @param \Tk\Form\Event\Iface $event
     */
    public function doSubmit($form, $event)
    {
        $selectedRules = \Rs\Calculator::findCompanyRuleList($this->placement->getCompany(), $this->placement->getSubject())->toArray('id');
        if($this->placement->getId() && !$form->hasErrors()) {
            \Rs\Db\RuleMap::create()->removePlacement(0, $this->placement->getVolatileId());
            foreach ($selectedRules as $ruleId) {
                \Rs\Db\RuleMap::create()->addPlacement($ruleId, $this->placement->getVolatileId());
            }
        }
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            \Tk\Kernel\KernelEvents::CONTROLLER => array('onControllerInit', 0),
            \Tk\Form\FormEvents::FORM_INIT => array('onFormInit', 0)
        );
    }
    
}
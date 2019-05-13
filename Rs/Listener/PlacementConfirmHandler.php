<?php
namespace Rs\Listener;

use Tk\Event\Subscriber;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
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
     * @param \Symfony\Component\HttpKernel\Event\ControllerEvent $event
     */
    public function onControllerInit(\Symfony\Component\HttpKernel\Event\ControllerEvent $event)
    {
        $controller = \Tk\Event\Event::findControllerObject($event);
        if ($controller instanceof \App\Controller\Student\Placement\Create || $controller instanceof \App\Controller\Student\Placement\Confirm) {
            $this->controller = $controller;
        }
    }

    /**
     * @param \Tk\Event\FormEvent $event
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
     * @throws \Exception
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
            KernelEvents::CONTROLLER => array('onControllerInit', 0),
            \Tk\Form\FormEvents::FORM_INIT => array('onFormInit', 0)
        );
    }
    
}
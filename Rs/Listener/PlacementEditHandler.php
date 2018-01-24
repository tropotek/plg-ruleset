<?php
namespace Rs\Listener;

use Tk\Event\Subscriber;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class PlacementEditHandler implements Subscriber
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
        if ($controller instanceof \App\Controller\Placement\Edit || $controller instanceof \App\Controller\Student\Placement\Create) {
            $this->controller = $controller;
        }
    }

    /**
     * @param \Tk\Event\FormEvent $event
     * @throws \Tk\Form\Exception
     */
    public function onFormInit(\Tk\Event\FormEvent $event)
    {
        $form = $event->getForm();
        if ($this->controller) {
            $this->placement = $this->controller->getPlacement();

            $profileRules = \Rs\Calculator::findProfileRuleList($this->placement->getCourse()->profileId);
            $placementRules = \Rs\Calculator::findPlacementRuleList($this->placement)->toArray('id');

            $field = new \Tk\Form\Field\CheckboxGroup('rules', \Tk\Form\Field\Option\ArrayObjectIterator::create($profileRules));
            $field->setLabel('Assessment Rules');
            $field->setValue($placementRules);

            if (!$this->controller instanceof \App\Controller\Student\Placement\Create) {
                $field->setTabGroup('Details');
                $form->addField($field);
            }

            if ($form->getField('update'))
                $form->addEventCallback('update', array($this, 'doSubmit'));
            if ($form->getField('save'))
                $form->addEventCallback('save', array($this, 'doSubmit'));
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
        $selectedRules = $form->getFieldValue('rules');
        if (!is_array($selectedRules)) $selectedRules = array();
        if ($this->controller instanceof \App\Controller\Student\Placement\Create) {
            //$selectedRules = \Rs\Calculator::findPlacementRuleList($this->placement, true)->toArray('id');
            $selectedRules = \Rs\Calculator::findCompanyRuleList($this->placement->getCompany(), $this->placement->getCourse())->toArray('id');
        }

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
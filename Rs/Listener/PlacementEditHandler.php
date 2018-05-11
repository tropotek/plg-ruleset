<?php
namespace Rs\Listener;

use Tk\Event\Subscriber;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
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
     * @throws \Tk\Db\Exception
     * @throws \Tk\Form\Exception
     */
    public function onFormInit(\Tk\Event\FormEvent $event)
    {
        /** @var \Tk\Form $form */
        $form = $event->getForm();
        if ($this->controller) {
            $this->placement = $this->controller->getPlacement();

            $companyRules = \Rs\Calculator::findCompanyRuleList($this->placement->getCompany(), $this->placement->getSubject(), true);
            $profileRules = \Rs\Calculator::findProfileRuleList($this->placement->getSubject()->profileId);
            $placementRules = \Rs\Calculator::findPlacementRuleList($this->placement)->toArray('id');

            $field = new \Tk\Form\Field\CheckboxGroup('rules', \Tk\Form\Field\Option\ArrayObjectIterator::create($profileRules));
            $field->setValue($placementRules);
            $field->setAttr('data-defaults', json_encode($companyRules));

            if ($this->controller instanceof \App\Controller\Student\Placement\Create) {
                $companyRules = \Rs\Calculator::findCompanyRuleList($this->placement->getCompany(), $this->placement->getSubject());
                $html = '';
                foreach ($companyRules as $rule) {
                    $html .= sprintf('<li>%s</li>', $rule->name) . "\n";
                }
                $html = rtrim($html , "\n");
                if ($html) $html = sprintf('<ul class="assessment-credit">%s</ul>', $html);

                $field = new \Tk\Form\Field\Html('rules', $html);
                $form->addFieldAfter('units', $field);

            } else {
                $field->setTabGroup('Details');
                $form->addField($field);
            }
            $field->setLabel('Assessment Credit');

            if ($form->getField('update'))
                $form->addEventCallback('update', array($this, 'doSubmit'));
            if ($form->getField('save'))
                $form->addEventCallback('save', array($this, 'doSubmit'));
            if ($form->getField('submitForApproval'))
                $form->addEventCallback('submitForApproval', array($this, 'doSubmit'));

            // TODO: style the list to look nice....?
            $css = <<<CSS
ul.assessment-credit {
  padding-left: 15px;
}
ul.assessment-credit li {
  font-weight: 600;
}
CSS;

            $this->controller->getTemplate()->appendCss($css);

            $js = <<<JS
jQuery(function ($) {
  $('.tk-rules').each(function () {
    var fieldGroup = $(this);
    var resetBtn = $('<p><button type="button" class="btn btn-default btn-xs" title="Reset the assessment to the company defaults."><i class="fa fa-refresh"></i> Reset</button></p>');
    fieldGroup.find(' > div').append(resetBtn);
    resetBtn.on('click', function () {
      $(this).parent().find('input[type=checkbox]').each(function () {
        var arr = $(this).data('defaults');
        if(jQuery.inArray(parseInt($(this).val()), arr) !== -1) {
          $(this).prop('checked', true);
        } else {
          $(this).prop('checked', false);
        }
      });
      return false;
    });
  });
  
});
JS;
            $this->controller->getTemplate()->appendJs($js);


        }
    }

    /**
     * @param \Tk\Form $form
     * @param \Tk\Form\Event\Iface $event
     * @throws \Tk\Db\Exception
     */
    public function doSubmit($form, $event)
    {
        $selectedRules = $form->getFieldValue('rules');
        if (!is_array($selectedRules)) $selectedRules = array();

        if ($this->controller instanceof \App\Controller\Student\Placement\Create) {
            $selectedRules = \Rs\Calculator::findCompanyRuleList($this->placement->getCompany(), $this->placement->getSubject())->toArray('id');
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
<?php
namespace Rs\Listener;

use Symfony\Component\HttpKernel\KernelEvents;
use Tk\Event\Subscriber;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class PlacementViewHandler implements Subscriber
{

    /**
     * @var null|\App\Controller\Placement\View
     */
    protected $controller = null;

    /**
     * @var null|\App\Db\Placement
     */
    protected $placement = null;


    /**
     * @param \Symfony\Component\HttpKernel\Event\FilterControllerEvent $event
     * @throws \Exception
     */
    public function onControllerInit($event)
    {
        $controller = \Tk\Event\Event::findControllerObject($event);
        if ($controller instanceof \App\Controller\Placement\View) {
            $this->controller = $controller;
        }
    }

    /**
     * @param \Tk\Event\Event $event
     * @throws \Dom\Exception
     * @throws \Exception
     */
    public function onControllerShow(\Tk\Event\Event $event)
    {
        if (!$this->controller) return;
        $placement = $this->controller->getPlacement();
        $rules = \Rs\Calculator::findPlacementRuleList($placement);
        $html = '';
        foreach ($rules as $rule) {
            $html .= sprintf('<li>%s</li>', $rule->getName()) . "\n";
        }
        $html = rtrim($html , "\n");
        if ($html) {
            //$html = sprintf('<ul style="padding: 0 0 0 0px; list-style: none;">%s</ul>', $html);
            $html = sprintf('<ul style="padding: 0 0 0 5px; list-style: none;">%s</ul>', $html);
            $this->controller->getTemplate()->appendHtml('dl-list', sprintf('<dt>Assessment Credit:</dt> <dd>%s</dd>', $html));
        }

    }

    /**
     * @param \Tk\Event\FormEvent $event
     * @throws \Exception
     */
    public function onFormInit(\Tk\Event\FormEvent $event)
    {
        /** @var \Tk\Form $form */
        $form = $event->getForm();
        if ($this->controller) {
            $this->placement = $this->controller->getPlacement();

            $courseRules = \Rs\Calculator::findSubjectRuleList($this->placement->getSubject());
            $companyRules = \Rs\Calculator::findCompanyRuleList($this->placement->getCompany(), $this->placement->getSubject(), $this->placement->getSupervisor())->toArray('id');
            $placementRules = \Rs\Calculator::findPlacementRuleList($this->placement)->toArray('id');

            $field = new \Tk\Form\Field\CheckboxGroup('rules', $courseRules);
            if (!$this->placement->getId()) {
                $field->setValue($companyRules);
            } else {
                $field->setValue($placementRules);
            }

            $field->setAttr('data-placement-id', $this->placement->getId());
            $field->setAttr('data-company-id', $this->placement->companyId);
            $field->setAttr('data-subject-id', $this->placement->subjectId);
            $field->setAttr('data-supervisor-id', $this->placement->supervisorId.'');

            if ($this->controller instanceof \App\Controller\Student\Placement\Create) {
                $field->setReadonly();
                $field->setDisabled(true);
                $form->appendField($field, 'units');
                $field->setAttr('data-hide-unselected');
            } else {
                $field->setTabGroup('Details');
                $form->appendField($field);
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
  
  function setCheckboxes(checkboxList) {
    var params = checkboxList.first().data();
    var hideUnselected = params.hideUnselected;
    params = $.extend({getRules: 'getRules'}, {
      placementId: params.placementId, 
      companyId: params.companyId, 
      subjectId: params.subjectId, 
      supervisorId: params.supervisorId
    });
    $(this).parent().find('input[type=checkbox]').prop('checked', false);
    $.post(document.location, params, function (data) {
      checkboxList.each(function () {
        if(jQuery.inArray(parseInt($(this).val()), data) !== -1) {
          $(this).prop('checked', true).closest('.checkbox').show();
        } else {
          $(this).prop('checked', false);
          if (hideUnselected) {
            $(this).closest('.checkbox').hide();
          }
        }
      });
    });
  }
  
  
  $('.tk-rules').each(function () {
    var fieldGroup = $(this);
    var checkboxList = fieldGroup.find('input[type=checkbox]');
    
    if (config.roleType !== 'student') {
        var resetBtn = $('<p><button type="button" class="btn btn-default btn-xs" title="Reset the assessment to the company defaults."><i class="fa fa-refresh"></i> Reset</button></p>');
        fieldGroup.find(' > div').append(resetBtn);
        resetBtn.on('click', function () {
          //var checkboxList = $(this).parent().find('input[type=checkbox]');
          setCheckboxes(checkboxList);
          return false;
        });
    }

    if (fieldGroup.find('.checkbox-group').data('placementId') === '0') {
      setCheckboxes(checkboxList);
      fieldGroup.closest('form').find('.tk-supervisorid select').on('change', function () {
        checkboxList.first().data('supervisor-id', $(this).val());
        setCheckboxes(checkboxList);
      });
    }
  });
  
});
JS;
            $this->controller->getTemplate()->appendJs($js);


        }
    }

    /**
     * @param \Tk\Form $form
     * @param \Tk\Form\Event\Iface $event
     * @throws \Exception
     */
    public function doSubmit($form, $event)
    {
        $selectedRules = $form->getFieldValue('rules');
        if (!is_array($selectedRules)) $selectedRules = array();

        if(!$form->hasErrors()) {
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
            \Tk\PageEvents::CONTROLLER_SHOW => array('onControllerShow', 0)
            //\Tk\Form\FormEvents::FORM_INIT => array('onFormInit', 0)
        );
    }
    
}
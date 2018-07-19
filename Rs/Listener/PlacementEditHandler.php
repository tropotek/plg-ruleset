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
     * @throws \Exception
     */
    public function onControllerInit(\Tk\Event\ControllerEvent $event)
    {
        $controller = $event->getController();
        if ($controller instanceof \App\Controller\Placement\Edit || $controller instanceof \App\Controller\Student\Placement\Create) {
            $this->controller = $controller;

            if ($controller->getRequest()->has('getRules')) {
                $this->doGetRules($controller->getRequest());
            }
        }
    }

    /**
     * @param \Tk\Request $request
     * @throws \Exception
     */
    public function doGetRules(\Tk\Request $request)
    {
        /** @var \App\Db\Placement $placement */
        $placement = \App\Db\CompanyMap::create()->find($request->get('placementId'));
        /** @var \App\Db\Company $company */
        $company = \App\Db\CompanyMap::create()->find($request->get('companyId'));
        /** @var \App\Db\Subject $subject */
        $subject = \App\Db\SubjectMap::create()->find($request->get('subjectId'));
        /** @var \App\Db\Supervisor $supervisor */
        $supervisor = \App\Db\SupervisorMap::create()->find($request->get('supervisorId'));

        $data = \Rs\Calculator::findCompanyRuleList($company, $subject, $supervisor)->toArray('id');
        if ($placement) {
            $data = \Rs\Calculator::findPlacementRuleList($placement)->toArray('id');
        }
        \Tk\ResponseJson::createJson($data)->send();
        exit;
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

            $companyRules = \Rs\Calculator::findCompanyRuleList($this->placement->getCompany(), $this->placement->getSubject(), $this->placement->getSupervisor())->toArray('id');
            $profileRules = \Rs\Calculator::findProfileRuleList($this->placement->getSubject()->profileId);
            $placementRules = \Rs\Calculator::findPlacementRuleList($this->placement)->toArray('id');

            $field = new \Tk\Form\Field\CheckboxGroup('rules', \Tk\Form\Field\Option\ArrayObjectIterator::create($profileRules));
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

            if ($this->controller->getUser()->isStaff()) {
                $js = <<<JS
jQuery(function ($) {
  
  function setCheckboxes(checkboxList) {
    var params = checkboxList.first().data();
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
          $(this).prop('checked', true);
        } else {
          $(this).prop('checked', false);
        }
      });
    });
  }
  
  
  $('.tk-rules').each(function () {
    var fieldGroup = $(this);
    var resetBtn = $('<p><button type="button" class="btn btn-default btn-xs" title="Reset the assessment to the company defaults."><i class="fa fa-refresh"></i> Reset</button></p>');
    fieldGroup.find(' > div').append(resetBtn);
    var checkboxList = fieldGroup.find('input[type=checkbox]');
    resetBtn.on('click', function () {
      //var checkboxList = $(this).parent().find('input[type=checkbox]');
      setCheckboxes(checkboxList);
      return false;
    });
    fieldGroup.closest('form').find('.tk-supervisorid select').on('change', function () {
      checkboxList.first().data('supervisor-id', $(this).val());
      setCheckboxes(checkboxList);
    });
  });
  
  
});
JS;
                $this->controller->getTemplate()->appendJs($js);
            }

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
            \Tk\Kernel\KernelEvents::CONTROLLER => array('onControllerInit', 0),
            \Tk\Form\FormEvents::FORM_INIT => array('onFormInit', 0)
        );
    }
    
}
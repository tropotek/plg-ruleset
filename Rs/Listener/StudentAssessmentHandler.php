<?php
namespace Rs\Listener;

use Tk\Event\Subscriber;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class StudentAssessmentHandler implements Subscriber
{


    /**
     * @param \Tk\Event\Event $event
     */
    public function onInit(\Tk\Event\Event $event)
    {
        /** @var \App\Ui\StudentAssessment $studentAssessment */
        $studentAssessment = $event->get('studentAssessment');
        $calc = \Rs\Calculator::createFromPlacementList($studentAssessment->getPlacementList());
        if (!$calc) return;
        $profileRuleList = $calc->getRuleList();
        /** @var \App\Db\Placement $placement */
        foreach ($studentAssessment->getPlacementList() as $placement) {
            $placementRules = \Rs\Calculator::findPlacementRuleList($placement);
            /** @var \Rs\Db\Rule $rule */
            foreach ($profileRuleList as $rule) {
                $units = 0;
                if (\Rs\Calculator::hasRule($rule, $placementRules)) {
                    $units = $placement->units;
                }
                $studentAssessment->addUnitColumn($rule->getLabel(), $placement->getId(), $units);
            }
        }
        
        $label = $calc->getCourse()->getProfile()->unitLabel;
        $totals = $calc->getRuleTotals();

        /** @var \Rs\Db\Rule $rule */
        foreach ($profileRuleList as $i => $rule) {
            if ($i == 0) {  // Unit totals
                if (!$studentAssessment->isMinMode())
                    $studentAssessment->addTotal('Pending', $label, $totals['total']['pending']);
                $studentAssessment->addTotal('Completed', $label, $totals['total']['completed'], $this->getValidCss($totals['total']['validCompleted']), $totals['total']['validCompletedMsg']);
                //if (!$studentAssessment->isMinMode()) {
                    $studentAssessment->addTotal('Min Targets', $label, $calc->getCourse()->getProfile()->minUnitsTotal);
                    $studentAssessment->addTotal('Max Targets', $label, $calc->getCourse()->getProfile()->maxUnitsTotal);
                //}
            }
            $t = $totals[$rule->getLabel()];
            if (!$studentAssessment->isMinMode())
                $studentAssessment->addTotal('Pending', $rule->getLabel(), $t['pending']);
            $studentAssessment->addTotal('Completed', $rule->getLabel(), $t['completed'], $this->getValidCss($t['validCompleted']), $t['validCompletedMsg']);
            //if (!$studentAssessment->isMinMode()) {
                $studentAssessment->addTotal('Min Targets', $rule->getLabel(), $rule->min);
                $studentAssessment->addTotal('Max Targets', $rule->getLabel(), $rule->max);
            //}
        }

    }

    private function getValidCss($validValue)
    {
        if ($validValue < 0) return 'less';
        if ($validValue > 0) return 'grater';
        return 'equal';
    }
    
    /**
     * @param \Tk\Event\Event $event
     */
    public function onShowRow(\Tk\Event\Event $event)
    {
        //vd('onShowRow');
    }

    /**
     * @param \Tk\Event\Event $event
     */
    public function onShow(\Tk\Event\Event $event)
    {
        //vd('onShow');
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            \App\UiEvents::STUDENT_ASSESSMENT_INIT      => array('onInit', 0)
//            ,
//            \App\UiEvents::STUDENT_ASSESSMENT_SHOW_ROW  => array('onShowRow', 0),
//            \App\UiEvents::STUDENT_ASSESSMENT_SHOW      => array('onShow', 0)
        );
    }

}
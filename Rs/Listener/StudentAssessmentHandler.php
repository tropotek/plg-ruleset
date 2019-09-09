<?php
namespace Rs\Listener;

use Tk\Event\Subscriber;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class StudentAssessmentHandler implements Subscriber
{


    /**
     * @param \Tk\Event\Event $event
     * @throws \Exception
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
        
        $label = $calc->getSubject()->getProfile()->unitLabel;
        $totals = $calc->getRuleTotals();

        /** @var \Rs\Db\Rule $rule */
        foreach ($profileRuleList as $i => $rule) {
            if ($i == 0) {  // Unit totals
                if (!$studentAssessment->isMinMode())
                    $studentAssessment->addTotal('Pending', $label, $totals['total']['pending']);
                $studentAssessment->addTotal('Completed', $label, $totals['total']['completed']);
                if (!$studentAssessment->isMinMode()) {
                    $studentAssessment->addTotal('Min Targets', $label, $calc->getSubject()->getMinUnitsTotal());
                    $studentAssessment->addTotal('Max Targets', $label, $calc->getSubject()->getMaxUnitsTotal());
                }
            }
            $t = $totals[$rule->getLabel()];
            if (!$studentAssessment->isMinMode())
                $studentAssessment->addTotal('Pending', $rule->getLabel(), $t['pending']);
            $studentAssessment->addTotal('Completed', $rule->getLabel(), $t['completed']);
            if (!$studentAssessment->isMinMode()) {
                $studentAssessment->addTotal('Min Targets', $rule->getLabel(), $rule->min);
                $studentAssessment->addTotal('Max Targets', $rule->getLabel(), $rule->max);
            }
        }

    }

    private function getValidCss($validValue)
    {
        if ($validValue < 0) return 'less';
        if ($validValue > 0) return 'grater';
        return 'equal';
    }


    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            \App\UiEvents::STUDENT_ASSESSMENT_INIT      => array('onInit', 0)
        );
    }

}
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
        vd('onInit');
        /** @var \App\Ui\StudentAssessment $studentAssessment */
        $studentAssessment = $event->get('studentAssessment');
        $calc = \Rs\Calculator::createFromPlacementList($studentAssessment->getPlacementList());


        vd($calc->getRuleTotals());

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

//        $totals = $calc->getRuleTotals();
//        /** @var \Rs\Db\Rule $rule */
//        foreach ($profileRuleList as $rule) {
//            $t = $totals[$rule->getLabel()];
//            $studentAssessment->addTotal('Pending', $t['pending']);
//            $studentAssessment->addTotal('Completed', $t['completed']);
//        }


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
            \App\UiEvents::STUDENT_ASSESSMENT_INIT      => array('onInit', 0),
            \App\UiEvents::STUDENT_ASSESSMENT_SHOW_ROW  => array('onShowRow', 0),
            \App\UiEvents::STUDENT_ASSESSMENT_SHOW      => array('onShow', 0)
        );
    }

}
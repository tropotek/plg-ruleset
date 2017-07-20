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
        //vd('onInit');
        /** @var \App\Ui\StudentAssessment $studentAssessment */
        $studentAssessment = $event->get('studentAssessment');
        $calc = \Rs\Calculator::createFromPlacementList($studentAssessment->getPlacementList());

// vd($calc->getRuleTotals());

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


        // TODO: ?????????????????????????????  TODO  ????????????????????????????
        // TODO:
        // TODO: This totals rows rendering is not right,
        // TODO:   if another plugin adds a column then we are stuffed.
        // TODO:   We need to make it more controlled by the Student Assessment object
        // TODO:
        // TODO: Maybe it should just render nothing in the cell as columns are added
        // TODO:  use the column name as the id rather than no numbered array
        // TODO:  for missing cols it renders '' strings ??????
        // TODO:
        // TODO:
        // TODO: ?????????????????????????????  TODO  ????????????????????????????

        $totals = $calc->getRuleTotals();
        $pending = array();
        $completed = array();
        $min = array();
        $max = array();

// vd($totals);

        /** @var \Rs\Db\Rule $rule */
        foreach ($profileRuleList as $i => $rule) {
            if ($i == 0) {  // Unit totals
                $pending[] = $totals['total']['pending'];
                $completed[] = $totals['total']['completed'];
                $min[] = $calc->getCourse()->getProfile()->minUnitsTotal;
                $max[] = $calc->getCourse()->getProfile()->maxUnitsTotal;
            }
            $t = $totals[$rule->getLabel()];
            $pending[] = $t['pending'];
            $completed[] = $t['completed'];
            $min[] = $rule->min;
            $max[] = $rule->max;

        }
        $studentAssessment->addTotal('Pending', $pending);
        $studentAssessment->addTotal('Completed', $completed);
        $studentAssessment->addTotal('Min Targets', $min);
        $studentAssessment->addTotal('Max Targets', $max);


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
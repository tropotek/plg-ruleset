<?php
namespace Rs\Listener;

use Tk\Event\Subscriber;

/**
 *
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class AssessmentUnitsHandler implements Subscriber
{

    /**
     * @param \Tk\Event\Event $event
     */
    public function onShow(\Tk\Event\Event $event)
    {
        /** @var \App\Ui\StudentAssessment $studentAssessment */
        $studentAssessment = $event->get('studentAssessment');
        //$unitCols = $studentAssessment->getUnitCols();

        $calc = \Rs\Calculator::createFromPlacementList($studentAssessment->getPlacementList());
        if (!$calc) return;
        $profileRuleList = $calc->getRuleList();

        $label = $calc->getCourse()->getProfile()->unitLabel;
        $totals = $calc->getRuleTotals();

        /** @var \Rs\Db\Rule $rule */
        foreach ($profileRuleList as $i => $rule) {
            $t = $totals[$rule->getLabel()];
            $studentAssessment->addTotal($label . ' Total', $rule->getLabel(), $t['total'], $this->getValidCss($t['validTotal']), $t['validMsg']);
        }
        $studentAssessment->addTotal($label . ' Total', $label, $totals['total']['total'], $this->getValidCss($totals['total']['validTotal']), $totals['total']['validMsg']);

        $event->stopPropagation();
    }

    private function getValidCss($validValue)
    {
        if ($validValue < 0) return 'less';
        if ($validValue > 0) return 'grater';
        return 'equal';
    }

    /**
     * getSubscribedEvents
     * 
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            \App\UiEvents::STUDENT_ASSESSMENT_SHOW => array('onShow', 10)
        );
    }

}



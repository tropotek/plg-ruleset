<?php
namespace Rs\Listener;

use Tk\Event\Subscriber;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class PlacementValidateHandler implements Subscriber
{

    /**
     *
     * @param \App\Event\PlacementValidEvent $event
     */
    public function onAppValidate(\App\Event\PlacementValidEvent $event)
    {
        $placement = $event->getPlacement();

        // Check placement rules and totals.
        $list = \App\Db\PlacementMap::create()->findFiltered(array(
            'courseId' => $placement->courseId,
            'userId'  => $placement->userId,
            'status'     => self::getStatusFilter()
        ));

        //   The calculator may need to be refactored also
        $calc = \Rs\Calculator::createFromPlacementList($list);
        $ruleInfo = $calc->getRuleTotals();
        //vd($ruleInfo);

        // Check rules for the placement
        $placeRules = \Rs\Calculator::findPlacementRuleList($placement);
        if (!$placement->getId()) {
            $placeRules = \Rs\Calculator::findCompanyRuleList($placement->getCompany(), $placement->getCourse());
        }

        $rulesIdList= array();
        foreach ($placeRules as $rule) {
            $rulesIdList[] = $rule->id;
        }
        //vd($ruleInfo, $placeRules, $rulesIdList);
        foreach ($ruleInfo as $label => $info) {
            if ($label == 'Total' || empty($info['assessmentRule'])) continue;
            if (!in_array($info['assessmentRule']->id, $rulesIdList)) continue;  // Restrict checking to only the placement rules.
            if ($info['validTotal'] > 0) {
                $event->addError($label, $info['validMsg']);
            }
        }

    }

    /**
     * @return array
     */
    protected function getStatusFilter()
    {
        return array(\App\Db\Placement::STATUS_APPROVED, \App\Db\Placement::STATUS_COMPLETED,
            \App\Db\Placement::STATUS_ASSESSING, \App\Db\Placement::STATUS_EVALUATING);
    }

    /**
     * getSubscribedEvents
     * 
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            \App\AppEvents::PLACEMENT_CREATE_APPROVE => 'onAppValidate'
        );
    }
}


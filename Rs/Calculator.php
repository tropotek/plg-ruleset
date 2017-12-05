<?php
namespace Rs;

use Rs\Db\Rule;


/**
 * Calculate rules and max/total/min
 *
 */
class Calculator extends \Tk\Object
{

    /**
     * @var \App\Db\User
     */
    protected $user = null;

    /**
     * @var \App\Db\Course
     */
    protected $course = null;

    /**
     * @var \Tk\Db\Map\ArrayObject
     */
    protected $placementList = null;

    /**
     * @var \Tk\Db\Map\ArrayObject
     */
    protected $ruleList = null;

    /**
     * @var array
     */
    protected $minTargets = null;

    /**
     * @var array
     */
    protected $maxTargets = null;

    /**
     * @var array
     */
    protected $totals = array();

    /**
     * @var array
     */
    protected $ruleTotals = array();


    /**
     * @param \App\Db\Course $course
     * @param \App\Db\User $user
     */
    protected function __construct($course, $user)
    {
        $this->course = $course;
        $this->user = $user;
        $this->placementList = $this->findPlacementList($course->getId(), $user->getId());
        $this->ruleList = self::findProfileRuleList($course->getProfile()->getId());
    }

    /**
     * calculate and return an instance of this object holding the calculated data
     *
     * @param \App\Db\Course $course
     * @param \App\Db\User $user
     * @return Calculator
     */
    public static function create($course, $user)
    {
        $calc = new self($course, $user);
        $calc->init();
        return $calc;
    }

    /**
     * Calculate and return an instance of this object holding the calculated data
     *
     * @param \Tk\Db\Map\ArrayObject $placementList
     * @return Calculator
     */
    public static function createFromPlacementList($placementList)
    {
        $calc = null;
        /** @var \App\Db\Placement $placement */
        $placement = $placementList->rewind()->current();
        if ($placement) {
            $calc = new self($placement->getCourse(), $placement->getUser());
            $calc->init();
        }
        return $calc;
    }

    /**
     * init()
     * @todo: This all may need to be refactored
     */
    private function init()
    {
        $totals = array();
        $termTot = 0;

        /* @var $placement \App\Db\Placement */
        foreach ($this->placementList as $placement) {
            $placeRules = self::findPlacementRuleList($placement);
            $units = 0;
            if ($placement->getPlacementType() && $placement->getPlacementType()->gradable) {
                $units = $placement->units;
            }

            /** @var Rule $rule */
            foreach ($this->ruleList as $rule) {
                if (!isset($totals[$rule->getLabel()])) {
                    $totals[$rule->getLabel()]['total'] = 0;
                    $totals[$rule->getLabel()]['completed'] = 0;
                    $totals[$rule->getLabel()]['pending'] = 0;
                }
                if (self::hasRule($rule, $placeRules)) {
                    $totals[$rule->getLabel()]['total'] += $units;
                    if ($placement->status == \App\Db\Placement::STATUS_COMPLETED) {
                        $totals[$rule->getLabel()]['completed'] += $units;
                    } else {
                        $totals[$rule->getLabel()]['pending'] += $units;
                    }
                }
            }

            if (!isset($totals['total'])) {
                $totals['total'] = 0;
                $totals['completed'] = 0;
                $totals['pending'] = 0;
            }
            $totals['total'] += $units;
            if ($placement->status == \App\Db\Placement::STATUS_COMPLETED) {
                $totals['completed'] += $units;
            } else {
                $totals['pending'] += $units;
            }
            $termTot += $units;
        }
        $this->totals = $totals;

        if (count($totals)) {
            /* @var $rule \Rs\Db\Rule */
            foreach ($this->ruleList as $rule) {
                $this->ruleTotals[$rule->getLabel()] = array();
                $this->ruleTotals[$rule->getLabel()]['ruleTotal'] = $rule->getMaxTarget() ? $rule->getMaxTarget() : $rule->getMinTarget();
                $this->ruleTotals[$rule->getLabel()]['total'] = $totals[$rule->getLabel()]['total'];
                $this->ruleTotals[$rule->getLabel()]['pending'] = $totals[$rule->getLabel()]['pending'];
                $this->ruleTotals[$rule->getLabel()]['completed'] = $totals[$rule->getLabel()]['completed'];
                $this->ruleTotals[$rule->getLabel()]['validCompleted'] = $rule->isTotalValid($totals[$rule->getLabel()]['completed']);
                $this->ruleTotals[$rule->getLabel()]['validCompletedMsg'] = $rule->getValidMessage($totals[$rule->getLabel()]['completed']);
                $this->ruleTotals[$rule->getLabel()]['validTotal'] = $rule->isTotalValid($totals[$rule->getLabel()]['total']);
                $this->ruleTotals[$rule->getLabel()]['validMsg'] = $rule->getValidMessage($totals[$rule->getLabel()]['total']);
                $this->ruleTotals[$rule->getLabel()]['assessmentRule'] = $rule;
            }
        }

        $this->ruleTotals['total'] = array();
        $this->ruleTotals['total']['ruleTotal'] = $this->course->getProfile()->maxUnitsTotal ? $this->course->getProfile()->maxUnitsTotal : $this->course->getProfile()->minUnitsTotal;
        $this->ruleTotals['total']['total'] = $totals['total'];
        $this->ruleTotals['total']['pending'] = $totals['pending'];
        $this->ruleTotals['total']['completed'] = $totals['completed'];
        $this->ruleTotals['total']['validCompleted'] = Rule::validateUnits($totals['completed'], $this->course->getProfile()->minUnitsTotal, $this->course->getProfile()->maxUnitsTotal);
        $this->ruleTotals['total']['validCompletedMsg'] = Rule::getValidateMessage($totals['completed'], $this->course->getProfile()->minUnitsTotal, $this->course->getProfile()->maxUnitsTotal);
        $this->ruleTotals['total']['validTotal'] = Rule::validateUnits($totals['total'], $this->course->getProfile()->minUnitsTotal, $this->course->getProfile()->maxUnitsTotal);
        $this->ruleTotals['total']['validMsg'] = Rule::getValidateMessage($totals['total'], $this->course->getProfile()->minUnitsTotal, $this->course->getProfile()->maxUnitsTotal);
        $this->ruleTotals['total']['assessmentRule'] = null;

        //vd($this->ruleInfo);
    }


    /**
     * Return an array with the term min target values
     * TODO: Is this used to validate placements?
     *
     * @param bool $total
     * @return array
     */
    public function getMinTargets($total = true)
    {
        if (!$this->minTargets) {
            /** @var Rule $rule */
            foreach ($this->ruleList as $rule) {
                $this->minTargets[$rule->getLabel()] = $rule->min;
            }
            if ($total)
                $this->minTargets['total'] = $this->course->getProfile()->minUnitsTotal;
        }
        return $this->minTargets;
    }

    /**
     * Return an array with the term max target values
     * TODO: Is this used to validate placements?
     * 
     * @param bool $total
     * @return array
     */
    public function getMaxTargets($total = true)
    {
        if (!$this->maxTargets) {
            /** @var Rule $rule */
            foreach ($this->ruleList as $rule) {
                $this->maxTargets[$rule->getLabel()] = $rule->max;
            }
            if ($total)
                $this->maxTargets['total'] = $this->course->getProfile()->minUnitsTotal;
        }
        return $this->maxTargets;
   }

    /**
     * Return an array with the placement totals for each rule set
     *
     * @return array
     */
    public function getTotals()
    {
        return $this->totals;
    }

    /**
     * Return total placement units for term
     *
     * @return int
     */
    public function getTermTotal()
    {
        return $this->totals['total'];
    }

    /**
     * Get all rule validation information
     * calculated from the list
     *
     * @return array
     */
    public function getRuleTotals()
    {
        return $this->ruleTotals;
    }

    /**
     * @return \App\Db\User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return \App\Db\Course
     */
    public function getCourse()
    {
        return $this->course;
    }

    /**
     * @return \Tk\Db\Map\ArrayObject
     */
    public function getPlacementList()
    {
        return $this->placementList;
    }

    /**
     * @return \Tk\Db\Map\ArrayObject
     */
    public function getRuleList()
    {
        return $this->ruleList;
    }

    /**
     * @param Rule $rule
     * @param \Tk\Db\Map\ArrayObject $ruleList
     * @return int
     */
    public static function hasRule($rule, $ruleList)
    {
        /** @var Rule $r */
        foreach ($ruleList as $r) {
            if ($rule->id == $r->id) return true;
        }
        return false;
    }

    /**
     * @param \App\Db\Placement $placement
     * @return Rule[]|\Tk\Db\Map\ArrayObject
     */
    public static function findPlacementRuleList($placement)
    {
        $list = null;
        if ($placement->getId()) {
            $list = \Rs\Db\RuleMap::create()->findFiltered(array('placementId' => $placement->getVolatileId()), \Tk\Db\Tool::create('order_by'));
        } else {    // Get default rules based on the company and course object
            $list = self::findCompanyRuleList($placement->getCompany(), $placement->getCourse());
        }
        return $list;
    }

    /**
     * @param \App\Db\Course $course
     * @param \App\Db\Company $company
     * @return Rule[]|\Tk\Db\Map\ArrayObject
     */
    public static function findCompanyRuleList($company, $course)
    {
        $list = \Rs\Db\RuleMap::create()->findFiltered(array('profileId' => $course->profileId), \Tk\Db\Tool::create('order_by'));
        $valid = array();
        /** @var \Rs\Db\Rule $rule */
        foreach ($list as $rule) {
            if ($rule->evaluate($course, $company))
                $valid[] = $rule;
        }
        return new \Tk\Db\Map\ArrayObject($valid);
    }

    /**
     * @param $profileId
     * @return Rule[]|\Tk\Db\Map\ArrayObject
     */
    public static function findProfileRuleList($profileId)
    {
        return \Rs\Db\RuleMap::create()->findFiltered(array('profileId' => $profileId), \Tk\Db\Tool::create('order_by'));
    }

    /**
     * @param int $courseId
     * @param int $userId
     * @return \App\Db\Placement[]|\Tk\Db\Map\ArrayObject
     */
    public static function findPlacementList($courseId, $userId)
    {
        return \App\Db\PlacementMap::create()->findFiltered(array(
            'userId' => $userId,
            'courseId' => $courseId,
            'status' => array(\App\Db\Placement::STATUS_APPROVED, \App\Db\Placement::STATUS_ASSESSING, \App\Db\Placement::STATUS_EVALUATING, \App\Db\Placement::STATUS_COMPLETED)
        ));
    }

}

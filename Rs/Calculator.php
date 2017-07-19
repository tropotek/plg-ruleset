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
    protected $ruleInfo = array();



    /**
     * @param \App\Db\Course $course
     * @param \App\Db\User $user
     */
    protected function __construct($course, $user)
    {
        $this->course = $course;
        $this->user = $user;
        $this->getPlacementList();
        $this->getProfileRuleList();
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
     * init()
     */
    private function init()
    {
        $totals = array();
        $termTot = 0;

        /* @var $placement \App\Db\Placement */
        foreach ($this->getPlacementList() as $placement) {
            $placeRules = $this->getPlacementRuleList($placement);
            $units = 0;
            if ($placement->getPlacementType() && $placement->getPlacementType()->gradable) {
                $units = $placement->units;
            }

            /** @var Rule $rule */
            foreach ($this->getProfileRuleList() as $rule) {
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
            foreach ($this->getProfileRuleList() as $rule) {
                $this->ruleInfo[$rule->getLabel()] = array();
                $this->ruleInfo[$rule->getLabel()]['ruleTotal'] = $rule->getMaxTarget() ? $rule->getMaxTarget() : $rule->getMinTarget();
                $this->ruleInfo[$rule->getLabel()]['total'] = $totals[$rule->getLabel()]['total'];
                $this->ruleInfo[$rule->getLabel()]['pending'] = $totals[$rule->getLabel()]['pending'];
                $this->ruleInfo[$rule->getLabel()]['completed'] = $totals[$rule->getLabel()]['completed'];
                $this->ruleInfo[$rule->getLabel()]['validCompleted'] = $rule->isTotalValid($totals[$rule->getLabel()]['completed']);
                $this->ruleInfo[$rule->getLabel()]['validCompletedMsg'] = $rule->getValidMessage($totals[$rule->getLabel()]['completed']);
                $this->ruleInfo[$rule->getLabel()]['validTotal'] = $rule->isTotalValid($totals[$rule->getLabel()]['total']);
                $this->ruleInfo[$rule->getLabel()]['validMsg'] = $rule->getValidMessage($totals[$rule->getLabel()]['total']);
                $this->ruleInfo[$rule->getLabel()]['assessmentRule'] = $rule;
            }
        }

        $this->ruleInfo['total'] = array();
        $this->ruleInfo['total']['ruleTotal'] = $this->course->getProfile()->maxUnitsTotal ? $this->course->getProfile()->maxUnitsTotal : $this->course->getProfile()->minUnitsTotal;
        $this->ruleInfo['total']['total'] = $totals['total'];
        $this->ruleInfo['total']['pending'] = $totals['pending'];
        $this->ruleInfo['total']['completed'] = $totals['completed'];
        $this->ruleInfo['total']['validCompleted'] = Rule::validateUnits($totals['completed'], $this->course->getProfile()->minUnitsTotal, $this->course->getProfile()->maxUnitsTotal);
        $this->ruleInfo['total']['validCompletedMsg'] = Rule::getValidateMessage($totals['completed'], $this->course->getProfile()->minUnitsTotal, $this->course->getProfile()->maxUnitsTotal);
        $this->ruleInfo['total']['validTotal'] = Rule::validateUnits($totals['total'], $this->course->getProfile()->minUnitsTotal, $this->course->getProfile()->maxUnitsTotal);
        $this->ruleInfo['total']['validMsg'] = Rule::getValidateMessage($totals['total'], $this->course->getProfile()->minUnitsTotal, $this->course->getProfile()->maxUnitsTotal);
        $this->ruleInfo['total']['assessmentRule'] = null;

        vd($this->ruleInfo);
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
     * Return an array with the term min target values
     *
     * @param bool $total
     * @return array
     */
    public function getMinTargets($total = true)
    {
        if (!$this->minTargets) {
            /** @var Rule $rule */
            foreach ($this->getProfileRuleList() as $rule) {
                $this->minTargets[$rule->getLabel()] = $rule->min;
            }
            if ($total)
                $this->minTargets['total'] = $this->course->getProfile()->minUnitsTotal;
        }
        return $this->minTargets;
    }

    /**
     * Return an array with the term max target values
     *
     * @param bool $total
     * @return array
     */
    public function getMaxTargets($total = true)
    {
        if (!$this->maxTargets) {
            /** @var Rule $rule */
            foreach ($this->getProfileRuleList() as $rule) {
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
    public function getRuleInfo()
    {
        return $this->ruleInfo;
    }

    /**
     * @param \App\Db\Placement $placement
     * @return Rule[]|\Tk\Db\Map\ArrayObject
     */
    public function getPlacementRuleList($placement)
    {
        $list = null;
        if ($placement->getId()) {
            $list = \Rs\Db\RuleMap::create()->findFiltered(array('placementId' => $placement->getId()));
        } else {
            // Get default rules based on the company and course object
            $list = $this->getCompanyRuleList($placement->getCourse(), $placement->getCompany());
        }
        return $list;
    }

    /**
     * @param \App\Db\Course $course
     * @param \App\Db\Company $company
     * @return Rule[]|\Tk\Db\Map\ArrayObject
     */
    public function getCompanyRuleList($course, $company)
    {
        $list = \Rs\Db\RuleMap::create()->findFiltered(array('profileId' => $course->profileId));
        $valid = array();
        /** @var \Rs\Db\Rule $rule */
        foreach ($list as $rule) {
            if ($rule->evaluate($course, $company))
                $valid[] = $rule;
        }
        return new \Tk\Db\Map\ArrayObject($valid);
    }

    /**
     * @return Rule[]|\Tk\Db\Map\ArrayObject
     */
    public function getProfileRuleList()
    {
        if (!$this->ruleList) {
            $this->ruleList = \Rs\Db\RuleMap::create()->findFiltered(array('profileId' => $this->course->profileId));
        }
        return $this->ruleList;
    }

    /**
     * @return \App\Db\Placement[]|\Tk\Db\Map\ArrayObject
     */
    public function getPlacementList()
    {
        if (!$this->placementList) {
            $this->placementList = \App\Db\PlacementMap::create()->findFiltered(array(
                'userId' => $this->user->getId(),
                'courseId' => $this->course->getid(),
                'status' => array(\App\Db\Placement::STATUS_APPROVED, \App\Db\Placement::STATUS_ASSESSING, \App\Db\Placement::STATUS_EVALUATING, \App\Db\Placement::STATUS_COMPLETED)
            ));
        }
        return $this->placementList;
    }

}

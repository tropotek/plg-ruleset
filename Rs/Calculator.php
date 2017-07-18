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
     * @var \App\Db\Course
     */
    protected $course = null;

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
     * Construct
     *
     * @param \App\Db\Course $course
     * @param array $totals
     */
    protected function __construct($course = null, $totals = array())
    {
        $this->course = $course;
        $this->totals = $totals;
        $this->init();
    }


    /**
     * calculate and return an instance of this object holding the calculated data
     *
     * @param array $placementList
     * @return null|Calculator
     */
    static function create($placementList)
    {
        if (!count($placementList)) return null;

        /* @var $placement \App\Db\Placement */
        $placement = null;
        if ($placementList instanceof \Tk\Db\Map\ArrayObject) {
            $placement = $placementList->get(0);
        } else if (is_array($placementList)) {
            $placement = current($placementList);
        }

        $term = $placement->getTerm();
        $semRules = $term->getRuleList();
        unset($placement);


        $totals = array();
        $termTot = 0;

        /* @var $placement \App\Db\Placement */
        foreach ($placementList as $placement) {
            $placeRules = $placement->getAssesmentRulesList();
            $units = 0;
            if ($placement->getPlacementType()->gradable) {
                $units = $placement->units;
            }

            /** @var Rule $rule */
            foreach ($semRules as $rule) {
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

        return new self($term, $totals, $termTot);
    }


    private function init()
    {
        $termRules = $this->course->getRuleList();

        if (count($this->totals)) {
            /* @var $rule \Rs\Db\Rule */
            foreach ($termRules as $rule) {
                $this->ruleInfo[$rule->getLabel()] = array();
                $this->ruleInfo[$rule->getLabel()]['requiredTotal'] = $rule->getMaxTarget() ? $rule->getMaxTarget() : $rule->getMinTarget();
                $this->ruleInfo[$rule->getLabel()]['assessmentRule'] = $rule;
                $this->ruleInfo[$rule->getLabel()]['studentTotal'] = $this->totals[$rule->getLabel()]['total'];
                $this->ruleInfo[$rule->getLabel()]['studentPending'] = $this->totals[$rule->getLabel()]['pending'];
                $this->ruleInfo[$rule->getLabel()]['studentCompleted'] = $this->totals[$rule->getLabel()]['completed'];

                $this->ruleInfo[$rule->getLabel()]['validCompleted'] = $rule->isTotalValid($this->totals[$rule->getLabel()]['completed']);
                $this->ruleInfo[$rule->getLabel()]['validCompletedMsg'] = $rule->getValidMessage($this->totals[$rule->getLabel()]['completed']);

                $this->ruleInfo[$rule->getLabel()]['validTotal'] = $rule->isTotalValid($this->totals[$rule->getLabel()]['total']);
                $this->ruleInfo[$rule->getLabel()]['validMsg'] = $rule->getValidMessage($this->totals[$rule->getLabel()]['total']);
            }
        }

        $this->ruleInfo['total'] = array();
        $this->ruleInfo['total']['assessmentRule'] = null;
        $this->ruleInfo['total']['requiredTotal'] = $this->course->maxTotalUnits ? $this->course->maxTotalUnits : $this->course->minTotalUnits;
        $this->ruleInfo['total']['studentTotal'] = $this->totals['total'];
        $this->ruleInfo['total']['studentPending'] = $this->totals['pending'];
        $this->ruleInfo['total']['studentCompleted'] = $this->totals['completed'];
        $this->ruleInfo['total']['validCompleted'] = Rule::validateUnits($this->totals['completed'], $this->course->minTotalUnits, $this->course->maxTotalUnits);
        $this->ruleInfo['total']['validCompletedMsg'] = Rule::validateMessage($this->totals['completed'], $this->course->minTotalUnits, $this->course->maxTotalUnits);

        $this->ruleInfo['total']['validTotal'] = Rule::validateUnits($this->totals['total'], $this->course->minTotalUnits, $this->course->maxTotalUnits);
        $this->ruleInfo['total']['validMsg'] = Rule::validateMessage($this->totals['total'], $this->course->minTotalUnits, $this->course->maxTotalUnits);
    }

    
    /**
     *
     * @param Rule $rule
     * @param \Tk\Db\Map\ArrayObject $ruleList
     * @return int
     */
    static function hasRule($rule, $ruleList)
    {
        /** @var Rule $r */
        foreach ($ruleList as $r) {
            if ($rule->id == $r->id) return true;
        }
        return false;
    }


    /**
     * Get all rule validation infomation
     * calculated from the list
     *
     * @return array
     */
    function getRuleInfo()
    {
        return $this->ruleInfo;
    }

    /**
     * Return an array with the term min target values
     *
     * @param bool $total
     * @return array
     */
    function getMinTargets($total = true)
    {
        if (!$this->minTargets) {
            $termRules = $this->course->getRuleList();
            foreach ($termRules as $rule) {
                $this->minTargets[$rule->getLabel()] = $rule->minUnits;
            }
            if ($total)
                $this->minTargets['total'] = $this->course->minTotalUnits;
        }
        return $this->minTargets;
    }

    /**
     * Return an array with the term max target values
     *
     * @param bool $total
     * @return array
     */
    function getMaxTargets($total = true)
    {
        if (!$this->maxTargets) {
            $termRules = $this->course->getRuleList();
            foreach ($termRules as $rule) {
                $this->maxTargets[$rule->getLabel()] = $rule->maxUnits;
            }
            if ($total)
                $this->maxTargets['total'] = $this->course->maxTotalUnits;
        }
        return $this->maxTargets;
   }

    /**
     * Return an array with the placement totals for each rule set
     *
     * @return array
     */
    function getTotals()
    {
        return $this->totals;
    }

    /**
     * Return total placement units for term
     *
     * @return int
     */
    function getTermTotal()
    {
        return $this->totals['total'];
    }



}

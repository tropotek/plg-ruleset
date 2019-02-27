<?php
namespace Rs\Db;


use Rs\Plugin;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class Rule extends \Tk\Db\Map\Model implements \Tk\ValidInterface
{
    const VALID_NULL = 128;

    const VALID_BELOW = -1;
    const VALID_OK = 0;
    const VALID_OUT = 1;


    /**
     * @var int
     */
    public $id = 0;

    /**
     * @var int
     */
    public $uid = 0;

    /**
     * @var int
     */
    public $profileId = 0;

    /**
     * @var int
     */
    public $subjectId = 0;

    /**
     * @var string
     */
    public $name = '';

    /**
     * @var string
     */
    public $label = '';

    /**
     * @var string
     */
    public $description = '';

    /**
     * @var float
     */
    public $min = 0.0;

    /**
     * @var float
     */
    public $max = 0.0;

    /**
     * @var string
     */
    public $assert = '';

    /**
     * @var string
     */
    public $script = '';

    /**
     * @var bool
     */
    public $active = true;

    /**
     * @var int
     */
    public $orderBy = 0;

    /**
     * @var \DateTime
     */
    public $created = null;



    /**
     * constructor.
     */
    public function __construct()
    {
        $this->created = \Tk\Date::create();
    }

    /**
     * eval() and return the result of the script
     *
     * @param \App\Db\Subject $subject
     * @param \App\Db\Company $company
     * @param \App\Db\Supervisor|null $supervisor
     * @return boolean
     */
    public function evaluate($subject, $company, $supervisor = null)
    {
        // TODO: place any global objects required for eval() here.
        if ($this->script) {
            return eval($this->script);
        }
        return false;
    }


    /**
     * Use this to get the rule label
     * as it returns the name if no label
     * available.
     *
     * @return string
     */
    public function getLabel()
    {
        if (!$this->label) return $this->name;
        return $this->label;
    }

    /**
     * return the target units required for this rule.
     *
     * @return int
     */
    public function getMinTarget()
    {
        if (!$this->min && $this->max)
            return $this->max;
        return $this->min;
    }

    /**
     * return the target units required for this rule.
     *
     * @return int
     */
    public function getMaxTarget()
    {
        if (!$this->max && $this->min)
            return $this->min;
        return $this->max;
    }

    /**
     * validate total for a student
     * Returns:
     *   o -n When units supplied are less than required units
     *   o  0 When units are equal to total or there is no problem with units in their current value
     *   o  n When units are over the total or maximum amount
     *
     * @param int $units
     * @return int
     */
    final public function isTotalValid($units)
    {
        return self::validateUnits($units, $this->min, $this->max);
    }

    /**
     * Get the worded string representing the current rule state.
     *
     * @param int $units
     * @return string
     */
    final public function getValidMessage($units)
    {
        return self::getValidateMessage($units, $this->min, $this->max);
    }


    /**
     * @param null|string $current
     * @return array
     */
    public static function getAssertList($current = null)
    {
        $path = \App\Config::getInstance()->getSitePath() . Plugin::getInstance()->getPluginPath().'/Rs/Assert';
        $list = array();
        foreach (scandir($path) as $i => $file) {
            if ($file[0] == '.') continue;
            if (strpos($file, 'Iface') === 0) continue;
            $class = str_replace('.php', '', $file);
            $label = $class;
            if ($current && $current == '\\Rs\\Assert\\' . $class)
                $label .= ' (selected)';
            $list[$label] = '\\Rs\\Assert\\' . $class;
        }
        ksort($list);
        return $list;
    }

    /**
     * validate total for a student
     * Returns:
     *   o -n When units supplied are less than required units
     *   o  0 When units are equal to total or there is no problem with units in their current value
     *   o  n When units are over the total or maximum amount
     *
     *
     * @param int $units
     * @param int $min
     * @param int $max
     * @return int
     */
    static function validateUnits($units, $min = 0, $max = 0)
    {
        $units = (float)$units;
        $min = (float)$min;
        $max = (float)$max;

        if (!$min && !$max) {
            return self::VALID_NULL;
        }
        if (!$min) {
            if ($units < $max) {
                return self::VALID_BELOW;
            } else if ($units > $max) {
                return self::VALID_OUT;
            }
        } else if (!$max) {
            if ($units < $min) {
                return self::VALID_BELOW;
            } else if ($units >= $min) {
                return self::VALID_OK;
            }
        } else if ($units < $min) {
            return self::VALID_BELOW;
        } else if ($units > $max) {
            return self::VALID_OUT;
        }
        return self::VALID_OK;
    }

    /**
     * Get the worded string representing the current rule state.
     *
     * @param int $units
     * @param int $min
     * @param int $max
     * @return string
     */
    static function getValidateMessage($units, $min = 0, $max = 0)
    {
        $res = self::validateUnits($units, $min, $max);
        switch ($res) {
            case self::VALID_BELOW:
                return sprintf('You are below the minimum required units of %d', (($min == 0) ? $max : $min) );
            case self::VALID_OUT:
                return sprintf('You have exceeded the maximum required units of %d', (($max == 0) ? $min : $max));
            case self::VALID_OK:
                return sprintf('You have reached the required number of units of %d', (($max == 0) ? $min : $max));
        }
        return '';
    }




    /**
     *
     * @param null|\App\Db\Profile $profile
     * @return array
     */
    public function validate($profile = null)
    {
        $errors = array();

        if ((int)$this->profileId <= 0) {
            $errors['profileId'] = 'Invalid  Profile ID';        }

        if (!$this->name) {
            $errors['name'] = 'Please enter a valid value';
        }

        if (!$this->label) {
            $errors['label'] = 'Please enter a valid value';
        }

        if (!$this->description) {
            $errors['description'] = 'Please enter a valid value';
        }

        if (!preg_match('/^[0-9]*(.[0-9]*)?$/', $this->min)) {
            $errors['min'] = 'Invalid Min. units Value.';
        }
        if (!preg_match('/^[0-9]*(.[0-9]*)?$/', $this->max)) {
            $errors['max'] = 'Invalid Max. units Value.';
        }
        if (!$this->min && !$this->max) {
            $errors['min'] = 'Min and/or Max Units must have a valid value.';
        }
        if ($this->min && $this->max && ($this->min > $this->max)) {
            $errors['max'] = 'Max Unit must be greater than Min Units';
        }

        if (!$this->script) {
            $errors['script'] = 'A rule code is required to make the rule active.';
        }

        return $errors;
    }

}
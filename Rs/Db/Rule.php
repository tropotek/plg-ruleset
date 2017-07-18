<?php
namespace Rs\Db;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class Rule extends \Tk\Db\Map\Model implements \Tk\ValidInterface
{

    /**
     * @var int
     */
    public $id = 0;

    /**
     * @var int
     */
    public $profileId = 0;

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
    public $script = '';

    /**
     * @var int
     */
    public $orderBy = 0;

    /**
     * @var \DateTime
     */
    public $created = null;



    /**
     * Course constructor.
     */
    public function __construct()
    {
        $this->created = \Tk\Date::create();
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
            $errors['profileId'] = 'Invalid Course Profile ID';
        }

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
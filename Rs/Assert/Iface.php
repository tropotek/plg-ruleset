<?php

namespace Rs\Assert;

/**
 * Note objects cannot have a constructor and cannot be passed
 * parameters as the rule will only have the class name
 * All objects will be instantiated via:
 *
 *   $assert = new \Rs\Assert\Iface();
 *
 *
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2019 Michael Mifsud
 */
abstract class Iface
{

    /**
     * Given the rule and company record return a bool
     *
     * @param \Rs\Db\Rule $rule
     * @param \App\Db\Company $company
     * @return boolean
     */
    abstract public function execute($rule, $company);


    /**
     * @return \App\Config|\Tk\Config
     */
    public function getConfig()
    {
        return \App\Config::getInstance();
    }

}
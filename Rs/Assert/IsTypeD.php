<?php
namespace Rs\Assert;


/**
 * @author Michael Mifsud <http://www.tropotek.com/>
 * @link http://www.tropotek.com/
 * @license Copyright 2019 Michael Mifsud
 */
class IsTypeD extends Iface
{

    /**
     * Given the rule and company record return a bool
     *
     * @param \Rs\Db\Rule $rule
     * @param \App\Db\Company $company
     * @return boolean
     */
    public function execute($rule, $company)
    {
        try {
            return ($company->getCategoryClass() == 'D');
        } catch (\Exception $e) { }
        return false;
    }
}
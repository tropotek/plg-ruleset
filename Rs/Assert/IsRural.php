<?php
namespace Rs\Assert;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2019 Michael Mifsud
 */
class IsRural extends Iface
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
            return ($company->districtType == \App\Db\Company::TYPE_RURAL);
        } catch (\Exception $e) { }
        return false;
    }
}
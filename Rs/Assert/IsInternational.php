<?php
namespace Rs\Assert;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2019 Michael Mifsud
 */
class IsInternational extends Iface
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
            // TODO: this should be removed once the below is tested to work
            if (!preg_match('|^aust|i', $company->country)) {
                return true;
            }
            $institution = $this->getConfig()->getInstitution();
            if ($institution && $institution->country && $company->country && strtolower(trim($institution->country)) != strtolower(trim($company->country))) {
                return true;
            }
        } catch (\Exception $e) { }
        return false;
    }
}
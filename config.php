<?php
$config = \Tk\Config::getInstance();

/** @var \Composer\Autoload\ClassLoader $composer */
$composer = $config->getComposer();
if ($composer) {
    $composer->add('Rs\\', dirname(__FILE__));
}

/** @var \Tk\Routing\RouteCollection $routes */
$routes = $config['site.routes'];
if (!$routes) return;

$params = array('role' => 'admin');
//$routes->add('Rules Admin Settings', new \Tk\Routing\Route('/ruleset/adminSettings.html', 'Rs\Controller\SystemSettings::doDefault', $params));

$params = array('role' => array('admin', 'client'));
//$routes->add('Rules Institution Settings', new \Tk\Routing\Route('/ruleset/institutionSettings.html', 'Rs\Controller\InstitutionSettings::doDefault', $params));
$routes->add('client-rule-manager', new \Tk\Routing\Route('/client/ruleManager.html', 'Rs\Controller\RuleManager::doDefault', $params));
$routes->add('client-rule-edit', new \Tk\Routing\Route('/client/ruleEdit.html', 'Rs\Controller\RuleEdit::doDefault', $params));

$params = array('role' => array('client', 'staff'));
$routes->add('Rules Profile Settings', new \Tk\Routing\Route('/staff/ruleSettings.html', 'Rs\Controller\ProfileSettings::doDefault', $params));
$routes->add('staff-rule-manager', new \Tk\Routing\Route('/staff/ruleManager.html', 'Rs\Controller\RuleManager::doDefault', $params));
$routes->add('staff-rule-edit', new \Tk\Routing\Route('/staff/ruleEdit.html', 'Rs\Controller\RuleEdit::doDefault', $params));


$params['subjectCode'] = '';
$routes->add('Student Assessment Report', new \Tk\Routing\Route('/staff/{subjectCode}/ruleReport.html', 'Rs\Controller\RuleReport::doDefault', $params));
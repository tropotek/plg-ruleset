<?php
$config = \Tk\Config::getInstance();

/** @var \Composer\Autoload\ClassLoader $composer */
$composer = $config->getComposer();
if ($composer)
    $composer->add('Rs\\', dirname(__FILE__));

/** @var \Tk\Routing\RouteCollection $routes */
$routes = $config['site.routes'];

$params = array('role' => 'admin');
$routes->add('Rules Admin Settings', new \Tk\Routing\Route('/ruleset/adminSettings.html', 'Rs\Controller\SystemSettings::doDefault', $params));

$params = array('role' => array('admin', 'client'));
$routes->add('Rules Institution Settings', new \Tk\Routing\Route('/ruleset/institutionSettings.html', 'Rs\Controller\InstitutionSettings::doDefault', $params));

$params = array('role' => array('client', 'staff'));
$routes->add('Rules Profile Settings', new \Tk\Routing\Route('/ruleset/courseProfileSettings.html', 'Rs\Controller\CourseProfileSettings::doDefault', $params));

$params = array('role' => array('client', 'staff'));
$routes->add('Rules Course Settings', new \Tk\Routing\Route('/ruleset/courseSettings.html', 'Rs\Controller\CourseSettings::doDefault', $params));


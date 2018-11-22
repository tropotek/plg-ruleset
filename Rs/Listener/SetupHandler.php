<?php
namespace Rs\Listener;

use Tk\Event\Subscriber;
use Rs\Plugin;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class SetupHandler implements Subscriber
{

    /**
     * @param \Tk\Event\GetResponseEvent $event
     * @throws \Tk\Db\Exception
     * @throws \Tk\Exception
     */
    public function onRequest(\Tk\Event\GetResponseEvent $event)
    {
        $dispatcher = \App\Config::getInstance()->getEventDispatcher();
        $plugin = Plugin::getInstance();

//        $institution = \Uni\Config::getInstance()->getInstitution();
//        if($institution && $plugin->isZonePluginEnabled(Plugin::ZONE_INSTITUTION, $institution->getId())) {
//            \Tk\Log::debug($plugin->getName() . ': Sample init client plugin stuff: ' . $institution->name);
//            $dispatcher->addSubscriber(new \Rs\Listener\ExampleHandler(Plugin::ZONE_INSTITUTION, $institution->getId()));
//        }

//        $subject = \Uni\Config::getInstance()->getSubject();
//        if ($subject && $plugin->isZonePluginEnabled(Plugin::ZONE_SUBJECT, $subject->getId())) {
//            \Tk\Log::debug($plugin->getName() . ': Sample init subject plugin stuff: ' . $subject->name);
//            $dispatcher->addSubscriber(new \Rs\Listener\ExampleHandler(Plugin::ZONE_SUBJECT, $subject->getId()));
//        }

        $profile = \App\Config::getInstance()->getProfile();
        if ($profile && $plugin->isZonePluginEnabled(Plugin::ZONE_SUBJECT_PROFILE, $profile->getId())) {
            //\Tk\Log::debug($plugin->getName() . ': Sample init subject profile plugin stuff: ' . $profile->name);
            $dispatcher->addSubscriber(new \Rs\Listener\ProfileEditHandler());
            $dispatcher->addSubscriber(new \Rs\Listener\SubjectDashboardHandler());
            $dispatcher->addSubscriber(new \Rs\Listener\CategoryClassHandler());

            $dispatcher->addSubscriber(new \Rs\Listener\PlacementEditHandler());
            $dispatcher->addSubscriber(new \Rs\Listener\PlacementConfirmHandler());
            $dispatcher->addSubscriber(new \Rs\Listener\PlacementValidateHandler());

            $dispatcher->addSubscriber(new \Rs\Listener\StudentAssessmentHandler());
            $dispatcher->addSubscriber(new \Rs\Listener\AssessmentUnitsHandler());
            $dispatcher->addSubscriber(new \Rs\Listener\CompanyViewHandler());
            $dispatcher->addSubscriber(new \Rs\Listener\StaffSideMenuHandler());

            $dispatcher->addSubscriber(new \Rs\Listener\CompanyEditHandler());
        }

    }

    public function onInit(\Tk\Event\KernelEvent $event)
    {
        //vd('onInit');
    }

    public function onController(\Tk\Event\ControllerEvent $event)
    {
        //vd('onController');
    }
    

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     *  * The method name to call (priority defaults to 0)
     *  * An array composed of the method name to call and the priority
     *  * An array of arrays composed of the method names to call and respective
     *    priorities, or 0 if unset
     *
     * For instance:
     *
     *  * array('eventName' => 'methodName')
     *  * array('eventName' => array('methodName', $priority))
     *  * array('eventName' => array(array('methodName1', $priority), array('methodName2'))
     *
     * @return array The event names to listen to
     *
     * @api
     */
    public static function getSubscribedEvents()
    {
        return array(
            //\Tk\Kernel\KernelEvents::INIT => array('onInit', 0),
            //\Tk\Kernel\KernelEvents::CONTROLLER => array('onController', 0),
            \Tk\Kernel\KernelEvents::REQUEST => array('onRequest', -10)
        );
    }
    
    
}
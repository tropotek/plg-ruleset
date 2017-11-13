<?php
namespace Rs\Listener;

use Tk\Event\Subscriber;
use Rs\Plugin;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class SetupHandler implements Subscriber
{


    public function onRequest(\Tk\Event\GetResponseEvent $event)
    {
        $dispatcher = \App\Factory::getEventDispatcher();
        $plugin = Plugin::getInstance();

//        $institution = \App\Factory::getInstitution();
//        if($institution && $plugin->isZonePluginEnabled(Plugin::ZONE_INSTITUTION, $institution->getId())) {
//            \Tk\Log::debug($plugin->getName() . ': Sample init client plugin stuff: ' . $institution->name);
//            $dispatcher->addSubscriber(new \Rs\Listener\ExampleHandler(Plugin::ZONE_INSTITUTION, $institution->getId()));
//        }

//        $course = \App\Factory::getCourse();
//        if ($course && $plugin->isZonePluginEnabled(Plugin::ZONE_COURSE, $course->getId())) {
//            \Tk\Log::debug($plugin->getName() . ': Sample init course plugin stuff: ' . $course->name);
//            $dispatcher->addSubscriber(new \Rs\Listener\ExampleHandler(Plugin::ZONE_COURSE, $course->getId()));
//        }

        $profile = \App\Factory::getProfile();
        if ($profile && $plugin->isZonePluginEnabled(Plugin::ZONE_COURSE_PROFILE, $profile->getId())) {
            //\Tk\Log::debug($plugin->getName() . ': Sample init course profile plugin stuff: ' . $profile->name);
            $dispatcher->addSubscriber(new \Rs\Listener\CategoryClassHandler());
            $dispatcher->addSubscriber(new \Rs\Listener\PlacementEditHandler());
            $dispatcher->addSubscriber(new \Rs\Listener\PlacementValidateHandler());
            $dispatcher->addSubscriber(new \Rs\Listener\StudentAssessmentHandler());
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
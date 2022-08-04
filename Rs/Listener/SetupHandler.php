<?php
namespace Rs\Listener;

use Rs\Plugin;
use Symfony\Component\HttpKernel\KernelEvents;
use Tk\Event\Subscriber;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class SetupHandler implements Subscriber
{

    /**
     * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
     * @throws \Tk\Db\Exception
     * @throws \Tk\Exception
     */
    public function onRequest($event)
    {
        $dispatcher = \App\Config::getInstance()->getEventDispatcher();
        $plugin = Plugin::getInstance();

        $dispatcher->addSubscriber(new \Rs\Listener\StudentAssessmentHandler());
        $dispatcher->addSubscriber(new \Rs\Listener\StatusMailHandler());

        if ($plugin->isZonePluginEnabled(Plugin::ZONE_COURSE, \App\Config::getInstance()->getCourseId())) {
            $dispatcher->addSubscriber(new \Rs\Listener\CourseEditHandler());
            $dispatcher->addSubscriber(new \Rs\Listener\SubjectEditHandler());
            $dispatcher->addSubscriber(new \Rs\Listener\SubjectDashboardHandler());
            $dispatcher->addSubscriber(new \Rs\Listener\CategoryClassHandler());

            $dispatcher->addSubscriber(new \Rs\Listener\PlacementManagerHandler());
            $dispatcher->addSubscriber(new \Rs\Listener\PlacementViewHandler());
            $dispatcher->addSubscriber(new \Rs\Listener\PlacementEditHandler());
            $dispatcher->addSubscriber(new \Rs\Listener\PlacementConfirmHandler());
            $dispatcher->addSubscriber(new \Rs\Listener\PlacementValidateHandler());

            $dispatcher->addSubscriber(new \Rs\Listener\AssessmentUnitsHandler());
            //$dispatcher->addSubscriber(new \Rs\Listener\CompanyViewHandler());
            $dispatcher->addSubscriber(new \Rs\Listener\StaffSideMenuHandler());

            $dispatcher->addSubscriber(new \Rs\Listener\PlacementImportHandler());
        }

    }

    /**
     * @param \Symfony\Component\Console\Event\ConsoleCommandEvent $event
     * @throws \Exception
     */
    public function onCommand(\Symfony\Component\Console\Event\ConsoleCommandEvent $event)
    {
        $config = \Uni\Config::getInstance();
        $dispatcher = $config->getEventDispatcher();

        $dispatcher->addSubscriber(new \Rs\Listener\StudentAssessmentHandler());
        $dispatcher->addSubscriber(new \Rs\Listener\CategoryClassHandler());


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
            KernelEvents::REQUEST => array('onRequest', -10),
            \Symfony\Component\Console\ConsoleEvents::COMMAND  => array('onCommand', -10)
        );
    }
    
    
}
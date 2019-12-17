<?php
namespace Rs\Listener;

use Tk\Event\Subscriber;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class CourseEditHandler implements Subscriber
{

    /**
     * @param \Tk\Event\Event $event
     * @throws \Exception
     */
    public function onControllerInit(\Tk\Event\Event $event)
    {
        /** @var \App\Controller\Course\Edit $controller */
        $controller = $event->get('controller');
        if ($controller instanceof \App\Controller\Course\Edit) {

            if ($controller->getUser()->isStaff() && $controller->getCourse()) {
                /** @var \Tk\Ui\Admin\ActionPanel $actionPanel */
                $actionPanel = $controller->getActionPanel();
                $actionPanel->append(\Tk\Ui\Link::createBtn(\App\Db\Phrase::findValue('placement', $controller->getCourse()->getId()) . ' Rules',
                    \Uni\Uri::createHomeUrl('/ruleSettings.html')
                        ->set('courseId', $controller->getCourse()->getId()), 'fa fa-check'));
            }

        }
    }

    /**
     * @param \Tk\Event\Event $event
     */
    public function onControllerShow(\Tk\Event\Event $event) { }

    /**
     * @return array The event names to listen to
     * @api
     */
    public static function getSubscribedEvents()
    {
        return array(
            \Tk\PageEvents::CONTROLLER_INIT => array('onControllerInit', 0),
            \Tk\PageEvents::CONTROLLER_SHOW => array('onControllerShow', 0)
        );
    }
    
}
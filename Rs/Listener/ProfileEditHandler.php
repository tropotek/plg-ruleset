<?php
namespace Rs\Listener;

use Tk\Event\Subscriber;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class ProfileEditHandler implements Subscriber
{

    /**
     * @param \Tk\Event\Event $event
     * @throws \Exception
     */
    public function onControllerInit(\Tk\Event\Event $event)
    {
        /** @var \App\Controller\Profile\Edit $controller */
        $controller = $event->get('controller');
        if ($controller instanceof \App\Controller\Profile\Edit) {

            if ($controller->getUser()->isStaff() && $controller->getProfile()) {
                /** @var \Tk\Ui\Admin\ActionPanel $actionPanel */
                $actionPanel = $controller->getActionPanel();
                $actionPanel->add(\Tk\Ui\Button::create(\App\Db\Phrase::findValue('placement', $controller->getProfile()->getId()) . ' Rules',
                    \App\Uri::createHomeUrl('/ruleSettings.html')
                        ->set('profileId', $controller->getProfile()->getId()), 'fa fa-check'));
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
<?php
namespace Rs\Listener;

use Bs\DbEvents;
use Rs\Db\Rule;
use Tk\ConfigTrait;
use Tk\Event\Subscriber;
use Uni\Db\Subject;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class SubjectEditHandler implements Subscriber
{
    use ConfigTrait;

    /**
     * @param \Tk\Event\Event $event
     * @throws \Exception
     */
    public function onControllerInit(\Tk\Event\Event $event)
    {
        /** @var \App\Controller\Subject\Edit $controller */
        $controller = $event->get('controller');
        if ($controller instanceof \App\Controller\Subject\Edit) {
            if ($controller->getSubject()->getId() && $controller->getAuthUser()->isStaff() && $controller->getSubject()->getCourse()) {
                /** @var \Tk\Ui\Admin\ActionPanel $actionPanel */
                $actionPanel = $controller->getActionPanel();
                $actionPanel->append(\Tk\Ui\Link::createBtn(\App\Db\Phrase::findValue('placement', $controller->getSubject()->getCourseId()) .
                    ' Rules', \Uni\Uri::createSubjectUrl('/ruleManager.html'), 'fa fa-check'));
            }
        }
    }

    /**
     * @param \Tk\Event\Event $event
     */
    public function onControllerShow(\Tk\Event\Event $event) { }


    /**
     * @var null|Subject
     */
    protected $currSubject = null;

    /**
     * @param \Bs\Event\DbEvent $event
     * @throws \Exception
     */
    public function onModelInsert(\Bs\Event\DbEvent $event)
    {
        if (!$event->getModel() instanceof Subject) {
            return;
        }
        $this->currSubject = $this->getConfig()->getCourse()->getCurrentSubject();

    }


    /**
     * @param \Bs\Event\DbEvent $event
     * @throws \Exception
     */
    public function onModelInsertPost(\Bs\Event\DbEvent $event)
    {
        if (!$event->getModel() instanceof Subject) {
            return;
        }
        if ($this->currSubject) {
            // Copy Rs Active Placement rules

            /** @var Subject $subject */
            $subject = $event->getModel();
            $filter = ['subjectId' => $this->currSubject->getId()];
            /** @var Rule[] $list */
            $list = \Rs\Db\RuleMap::create()->findFiltered($filter);
            foreach ($list as $rule) {
                if ($rule->isActive($this->currSubject->getId())) {
                    \Rs\Db\RuleMap::create()->setActive($rule->getId(), $subject->getId(), true);
                }
            }
        }
    }

    /**
     * @return array The event names to listen to
     * @api
     */
    public static function getSubscribedEvents()
    {
        return array(
            \Tk\PageEvents::CONTROLLER_INIT => array('onControllerInit', 0),
            \Tk\PageEvents::CONTROLLER_SHOW => array('onControllerShow', 0),
            DbEvents::MODEL_INSERT => 'onModelInsert',
            DbEvents::MODEL_INSERT_POST => 'onModelInsertPost'
        );
    }
    
}
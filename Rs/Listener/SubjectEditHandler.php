<?php
namespace Rs\Listener;

use Tk\Event\Subscriber;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class SubjectEditHandler implements Subscriber
{

    /**
     * @param \Tk\Event\Event $event
     * @throws \Exception
     */
    public function onControllerInit(\Tk\Event\Event $event)
    {
        /** @var \App\Controller\Subject\Edit $controller */
        $controller = $event->get('controller');
        if ($controller instanceof \App\Controller\Subject\Edit) {

            if ($controller->getUser()->isStaff() && $controller->getProfile()) {
                /** @var \Tk\Ui\Admin\ActionPanel $actionPanel */
                $actionPanel = $controller->getActionPanel();
                $actionPanel->append(\Tk\Ui\Link::createBtn(\App\Db\Phrase::findValue('placement', $controller->getProfile()->getId()) . ' Rules',
                    \App\Uri::createSubjectUrl('/ruleManager.html'), 'fa fa-check'));
            }

        }
    }

    /**
     * Ensure this is run after Bs\Listener\CrumbsHandler::onFinishRequest()
     *
     * @param \Tk\Event\Event $event
     * @throws \Exception
     */
    public function onMigrateStudent(\Tk\Event\Event $event)
    {
        $config = \App\Config::getInstance();
        /** @var \App\Db\Subject $subjectFrom */
        $subjectFrom = $config->getSubjectMapper()->find($event->get('subjectFromId'));
        /** @var \App\Db\Subject $subjectTo */
        $subjectTo = $config->getSubjectMapper()->find($event->get('subjectToId'));
        /** @var \App\Db\User $user */
        $user = $config->getUserMapper()->find($event->get('userId'));

        // Migrate placements
        if (!$subjectFrom || !$subjectTo || !$user) return;     // TODO: could throw an error here or something later

        $placementList = \App\Db\PlacementMap::create()->findFiltered(array(
            'subjectId' => $subjectFrom->getId(),
            'userId' => $user->getId()
        ));
        foreach ($placementList as $placement) {

            //$profileRules = \Rs\Calculator::findSubjectRuleList($placement->subjectId);
            $companyRules = \Rs\Calculator::findCompanyRuleList($placement->getCompany(), $placement->getSubject(), $placement->getSupervisor());
            $placementRules = \Rs\Calculator::findPlacementRuleList($placement);
            // TODO: We need to update rule_has_placement.rule_id to the new subjects rule_id

            // else if none found

            // reset the placement to the default ruleset

            // TODO:
            // TODO:
            // TODO: Also check what happens when a new subject is created, do we copy the rules across at some point?
            // TODO:
            // TODO:

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
            \Tk\PageEvents::CONTROLLER_SHOW => array('onControllerShow', 0),
            \App\AppEvents::SUBJECT_MIGRATE_USER => 'onMigrateStudent'
        );
    }
    
}
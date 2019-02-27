<?php
namespace Rs\Listener;

use Tk\Event\Subscriber;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class SubjectDashboardHandler implements Subscriber
{

    /**
     * @param \Tk\Event\Event $event
     * @throws \Exception
     */
    public function onControllerInit(\Tk\Event\Event $event)
    {
        /** @var \App\Controller\Student\SubjectDashboard $controller */
        $controller = $event->get('controller');
        if ($controller instanceof \App\Controller\Student\SubjectDashboard) {
            if ($controller->getSubject()) {
                $table = $controller->getPlacementList()->getTable();

                $css = <<<CSS
.student-placement-table .mCredit {
    clear: left;
    float: left;
  }
CSS;
                $table->getRenderer()->getTemplate()->appendCss($css);

                $table->prependCell(new \Tk\Table\Cell\Text('credit'), 'actions')->setOnCellHtml(function ($cell, $obj, $html) {
                    /** @var \Tk\Table\Cell\Iface $cell */
                    /** @var \App\Db\Placement $obj */
                    $list = \Rs\Calculator::findPlacementRuleList($obj);
                    $html = '';
                    foreach ($list as $rule) {
                        $html .= sprintf('<span class="rule"><i class="fa fa-check text-success"></i> <span>%s</span></span> | ', $rule->getLabel());
                    }
                    if ($html) {
                        $html = '<div class="assessment">' . rtrim($html, ' | ') . '</div>';
                    }
                    return $html;
                });
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
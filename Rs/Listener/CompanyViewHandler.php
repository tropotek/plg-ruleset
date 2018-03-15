<?php
namespace Rs\Listener;

use Tk\Event\Subscriber;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class CompanyViewHandler implements Subscriber
{

    /**
     * @var null|\App\Controller\Company\View
     */
    protected $controller = null;



    /**
     * @param \Tk\Event\ControllerEvent $event
     */
    public function onControllerInit(\Tk\Event\ControllerEvent $event)
    {
        $controller = $event->getController();
        if ($controller instanceof \App\Controller\Company\View) {
            $this->controller = $controller;
        }
    }

    /**
     * @param \Tk\Event\Event $event
     * @throws \Dom\Exception
     */
    public function onControllerShow(\Tk\Event\Event $event)
    {
        if (!$this->controller) return;

        $companyRules = \Rs\Calculator::findCompanyRuleList($this->controller->getCompany(), $this->controller->getSubject());
        $template = $this->controller->getTemplate();

        $repeat = $template->getRepeat('infoData');
        $repeat->insertText('label', 'Assessment Credit:');
        $html = '';
        foreach ($companyRules as $rule) {
            $html .= sprintf('<li>%s</li>', $rule->name) . "\n";
        }
        $html = rtrim($html , "\n");
        if ($html) $html = sprintf('<ul>%s</ul>', $html);
        $repeat->insertHtml('data', $html);
        $repeat->addCss('data', 'force-block');
        $repeat->appendRepeat();

    }

    /**
     * @return array The event names to listen to
     * @api
     */
    public static function getSubscribedEvents()
    {
        return array(
            \Tk\Kernel\KernelEvents::CONTROLLER => array('onControllerInit', 0),
            \Tk\PageEvents::CONTROLLER_SHOW => array('onControllerShow', 0)
        );
    }

    
}
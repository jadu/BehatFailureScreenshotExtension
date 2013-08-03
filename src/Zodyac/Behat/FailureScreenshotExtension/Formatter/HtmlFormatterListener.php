<?php

namespace Zodyac\Behat\FailureScreenshotExtension\Formatter;

use Behat\Behat\Event\StepEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Zodyac\Behat\ExtensibleHtmlFormatter\Event\FormatterEvent;
use Zodyac\Behat\ExtensibleHtmlFormatter\Event\FormatterStepEvent;
use Zodyac\Behat\FailureScreenshotExtension\Listener\FailureScreenshotListener;
use Zodyac\Behat\PerceptualDiffExtension\Exception\PerceptualDiffException;

class HtmlFormatterListener implements EventSubscriberInterface
{
    protected $screenshotListener;

    public function __construct(FailureScreenshotListener $screenshotListener)
    {
        $this->screenshotListener = $screenshotListener;
    }

    public static function getSubscribedEvents()
    {
        return array(
            'formatter.html.step' => 'printScreenshot',
            'formatter.html.head' => 'printStyles'
        );
    }

    /**
     * Outputs the failure screenshot
     *
     * @param FormatterStepEvent $event
     */
    public function printScreenshot(FormatterStepEvent $event)
    {
        if ($event->getResult() === StepEvent::FAILED && !$event->getException() instanceof PerceptualDiffException) {
            $screenshotPath = $this->screenshotListener->getScreenshotPath($event->getStep());
            $event->writeln('<a href="file://' . $screenshotPath . '" target="new"><img class="screenshot" src="file://' . $screenshotPath . '" /></a>');
        }
    }

    /**
     * Outputs additional CSS for the pdiff section
     *
     * @param FormatterEvent $event
     */
    public function printStyles(FormatterEvent $event)
    {
        $styles = <<<TEMPLATE
        <style type="text/css">
        #behat img.screenshot {
            width:300px;
            margin:5px;
            border:2px solid #aaa;
        }
        </style>
TEMPLATE;

        $event->writeln($styles);
    }
}

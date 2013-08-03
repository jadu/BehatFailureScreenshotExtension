<?php

namespace Zodyac\Behat\FailureScreenshotExtension\Listener;

use Behat\Behat\Event\ScenarioEvent;
use Behat\Behat\Event\StepEvent;
use Behat\Gherkin\Node\StepNode;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Zodyac\Behat\PerceptualDiffExtension\Exception\PerceptualDiffException;

class FailureScreenshotListener implements EventSubscriberInterface
{
    protected $path;

    /**
     * When the suite was started.
     *
     * @var \DateTime
     */
    protected $started;

    /**
     * The scenario currently being tested.
     *
     * @var ScenarioNode
     */
    protected $currentScenario;

    /**
     * Current step (reset for each scenario)
     *
     * @var int
     */
    protected $stepNumber;

    public function __construct($path)
    {
        $this->path = rtrim($path, '/') . '/';
        $this->started = new \DateTime();
    }

    public static function getSubscribedEvents()
    {
        return array(
            'beforeScenario' => 'resetStepCounter',
            'afterStep' => 'screenshotFailedStep'
        );
    }

    /**
     * Keep track of the current scenario and step number for use in the file name
     *
     * @param ScenarioEvent $event
     */
    public function resetStepCounter(ScenarioEvent $event)
    {
        $this->currentScenario = $event->getScenario();
        $this->stepNumber = 0;
    }

    /**
     * Takes a screenshot of the failed step.
     *
     * @param StepEvent $event
     */
    public function screenshotFailedStep(StepEvent $event)
    {
        // Increment the step number
        $this->stepNumber++;

        if ($event->getResult() !== StepEvent::FAILED) {
            return;
        }

        // Don't take failed screenshots if it failed due to a perceptual diff
        if ($event->getException() instanceof PerceptualDiffException) {
            return;
        }

        $screenshotFile = $this->getScreenshotPath($event->getStep());
        $this->ensureDirectoryExists($screenshotFile);

        // Save the screenshot
        file_put_contents($screenshotFile, $event->getContext()->getSession()->getScreenshot());
    }

    /**
     * Ensure the directory where the file will be saved exists.
     *
     * @param string $file
     * @return boolean Returns true if the directory exists and false if it could not be created
     */
    protected function ensureDirectoryExists($file)
    {
        $dir = dirname($file);
        if (!is_dir($dir)) {
            return mkdir($dir, 0777, true);
        }

        return true;
    }


    /**
     * Returns the screenshot path
     *
     * @return string
     */
    public function getScreenshotPath(StepNode $step)
    {
        return $this->path . $this->started->format('YmdHis') . '/' . $this->getFilename($step);
    }

    /**
     * Returns the filename for the given step
     *
     * @param StepNode $step
     * @return string
     */
    protected function getFilename(StepNode $step)
    {
        return sprintf('%s/%s/%d-%s.png',
            $this->formatString($this->currentScenario->getFeature()->getTitle()),
            $this->formatString($this->currentScenario->getTitle()),
            $this->stepNumber,
            $this->formatString($step->getText())
        );
    }

    /**
     * Formats a title string into a filename friendly string
     *
     * @param string $string
     * @return string
     */
    protected function formatString($string)
    {
        $string = preg_replace('/[^\w\s\-]/', '', $string);
        $string = preg_replace('/[\s\-]+/', '-', $string);

        return $string;
    }
}

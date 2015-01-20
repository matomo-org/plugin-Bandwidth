<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Plugins\Bandwidth\tests\Fixtures;

use Piwik\Date;
use Piwik\Filesystem;
use Piwik\Plugin;
use Piwik\Config;
use Piwik\Tests\Framework\Fixture;
use PiwikTracker;

/**
 * Track actions with bandwidth
 */
class OneVisitWithBandwidth extends Fixture
{
    public $dateTime = '2010-01-03 11:22:33';
    public $idSite = 1;

    /**
     * @var \PiwikTracker
     */
    private $tracker;

    public function setUp()
    {
        Plugin\Manager::getInstance()->loadPlugin('Bandwidth');
        Plugin\Manager::getInstance()->installLoadedPlugins();
        Filesystem::deleteAllCacheOnUpdate();

        $this->setUpWebsite();
        $this->trackVisits();
    }

    private function setUpWebsite()
    {
        // tests run in UTC, the Tracker in UTC
        if (!self::siteCreated($idSite = 1)) {
            self::createWebsite($this->dateTime);
        }

        self::createSuperUser();
    }

    public function trackVisits()
    {
        $this->tracker = self::getTracker($this->idSite, $this->dateTime, $useDefault = true, $uselocal = false);
        $this->tracker->setUrl('http://www.example.org/page');
        $this->tracker->setGenerationTime(333);

        $this->trackPageview('Test Title', 550);
        $this->trackPageview('Test 2', 200928);
        $this->trackPageview('Test Title');

        $this->moveTimeForward(2);
        $this->trackDownload('/test/xyz.png', 23929);
        $this->trackDownload('/app.apk', 194948483939);
        $this->trackPageview('Test Title 4', 10);
        $this->trackPageview('Test Title');
        $this->moveTimeForward(3);
        $this->trackPageview('Test 3', 52);
        $this->trackPageview('Test Title', 20);
        $this->trackPageview('Test 3', 13);
        $this->moveTimeForward(4);
        $this->trackPageview('Test Title 4', 6889);
        $this->trackDownload('/app.apk', 194948483939);

        $this->trackPageview('No bandwidth');

        $this->moveTimeForward(4.5);
        $this->trackPageview('Test Title', 95);
        $this->trackPageview('Index', 19493);
        $this->trackPageview('Index', 29);
        $this->trackPageview('No bandwidth');
        $this->trackPageview('Index', 455);
    }
    
    private function trackPageview($title, $bytes = null)
    {
        $this->setBandwidthTrackerParam($bytes);
        $url = str_replace(' ', '/', strtolower($title));
        $this->tracker->setUrl('http://www.example.org/' . $url);
        self::checkResponse($this->tracker->doTrackPageView($title));
    }

    private function trackDownload($path, $bytes = null)
    {
        $this->setBandwidthTrackerParam($bytes);
        self::checkResponse($this->tracker->doTrackAction('http://www.example.org' . $path, 'download'));
    }

    private function setBandwidthTrackerParam($bytes)
    {
        if ($bytes !== null) {
            $this->tracker->setDebugStringAppend('bw_bytes=' . $bytes);
        } else {
            $this->tracker->setDebugStringAppend('');
        }
    }

    private function moveTimeForward($minutes)
    {
        $hour = $minutes / 60;
        $this->tracker->setForceVisitDateTime(Date::factory($this->dateTime)->addHour($hour)->getDatetime());
    }

    public function tearDown()
    {
    }
}
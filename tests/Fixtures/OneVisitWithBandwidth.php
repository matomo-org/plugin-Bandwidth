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

        $this->setUpWebsiteAndUser();
        $this->trackVisits();
    }

    private function setUpWebsiteAndUser()
    {
        // tests run in UTC, the Tracker in UTC
        if (!self::siteCreated($idSite = 1)) {
            self::createWebsite($this->dateTime);
        }

        if (!self::siteCreated($idSite = 2)) {
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
        $this->trackPageview('Index', 204948283939);
        $this->trackPageview('No bandwidth');
        $this->trackPageview('Index', 455);


        // we want to track pageviews for a different day in the same month year to verify it will still show
        // bandwidth columns on that day even there were no bytes tracked but there were some tracked in the same month
        $this->tracker = self::getTracker($this->idSite, '2010-01-05 23:23:23', $useDefault = true, $uselocal = false);
        $this->trackPageview('Test Title');
        $this->trackPageview('Test Title');
        $this->trackPageview('Test Title 2');
        $this->trackPageview('Index');

        // we want to track some more pageviews with no bytes to make sure columns are not shown here as there are no
        // tracked bytes for this month
        $this->tracker = self::getTracker($this->idSite, '2010-02-05 23:23:23', $useDefault = true, $uselocal = false);
        $this->trackPageview('Test Title');
        $this->trackPageview('Test Title');
        $this->trackPageview('Index');
        $this->trackDownload('/test/xyz.png');
        $this->trackDownload('/app.apk');
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
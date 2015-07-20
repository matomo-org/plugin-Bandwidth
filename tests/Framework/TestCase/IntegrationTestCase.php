<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\Bandwidth\tests\Framework\TestCase;

use Piwik\Db;
use Piwik\Plugin;
use Piwik\Tests\Framework\Fixture;
use Piwik\Tests\Framework\Mock\FakeAccess;
use Piwik\DataAccess\ArchiveTableCreator;

/**
 * @group Bandwidth
 */
class IntegrationTestCase extends \Piwik\Tests\Framework\TestCase\IntegrationTestCase
{
    protected $date;

    public function setUp()
    {
        parent::setUp();
        $this->setSuperUser();

        Fixture::createSuperUser();
        Fixture::createWebsite('2014-01-01 00:00:00');

        Plugin\Manager::getInstance()->loadPlugin('Bandwidth');
        try {
            Plugin\Manager::getInstance()->activatePlugin('Bandwidth');
        } catch (\Exception $e) {

        }
    }

    public function tearDown()
    {
        // clean up your test here if needed
        $tables = ArchiveTableCreator::getTablesArchivesInstalled();
        if (!empty($tables)) {
            Db::dropTables($tables);
        }
        parent::tearDown();
    }

    protected function setUser()
    {
        FakeAccess::$superUser = false;
        FakeAccess::$idSitesView = array(1);
        FakeAccess::$identity = 'aUser';
    }

    protected function setSuperUser()
    {
        FakeAccess::$superUser = true;
    }

    protected function setAnonymousUser()
    {
        FakeAccess::clearAccess();
        FakeAccess::$identity = 'anonymous';
    }

    protected function getTracker()
    {
        $tracker = Fixture::getTracker(1, $this->date . ' 00:01:01', true, true);
        $tracker->setTokenAuth(Fixture::getTokenAuth());
        return $tracker;
    }

    protected function trackPageviews($bytes)
    {
        $tracker = $this->getTracker();

        foreach ($bytes as $byte) {
            $this->trackPageview($tracker, $byte);
        }
    }

    protected function trackPageview(\PiwikTracker $tracker, $byte, $url = null)
    {
        if (null !== $url) {
            $tracker->setUrl('http://www.example.org' . $url);
        }

        if (null === $byte) {
            $tracker->setDebugStringAppend('');
        } else {
            $tracker->setDebugStringAppend('bw_bytes=' . $byte);
        }

        $title = $url ? : 'test';

        $tracker->doTrackPageView($title);
    }

    protected function trackDownloads($bytes)
    {
        $tracker = $this->getTracker();

        foreach ($bytes as $byte) {
            if (null === $byte) {
                $tracker->setDebugStringAppend('');
            } else {
                $tracker->setDebugStringAppend('bw_bytes=' . $byte);
            }

            $tracker->doTrackAction('http://www.example.com/test', 'download');
        }
    }

    public function provideContainerConfig()
    {
        return array(
            'Piwik\Access' => new FakeAccess()
        );
    }

}

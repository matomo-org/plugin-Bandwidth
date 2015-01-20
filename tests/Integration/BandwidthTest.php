<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\Bandwidth\tests\Integration;

use Piwik\Access;
use Piwik\API\Request;
use Piwik\DataAccess\ArchiveTableCreator;
use Piwik\DataTable;
use Piwik\Db;
use Piwik\Plugin;
use Piwik\Plugins\Bandwidth\API;
use Piwik\Tests\Framework\Fixture;
use Piwik\Tests\Framework\Mock\FakeAccess;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;

/**
 * @group Bandwidth
 * @group APITest
 * @group Plugins
 */
class BandwidthTest extends IntegrationTestCase
{
    /**
     * @var API
     */
    private $api;

    public function setUp()
    {
        parent::setUp();
        $this->api = API::getInstance();
        $this->setSuperUser();

        Fixture::createSuperUser();
        Fixture::createWebsite('2014-01-01 00:00:00');

        Plugin\Manager::getInstance()->installLoadedPlugins();
        $this->setUser();
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

    public function test_shouldEnrichPageUrls()
    {
        $this->trackBytes(array(1, 10, null, 5, null, 3949, 399));

        $result = $this->requestAction('getPageUrls');

        $row = $result->getFirstRow();
        $this->assertSame($row->getColumn('max_bandwidth'), 3949);
        $this->assertSame($row->getColumn('min_bandwidth'), 1);
        $this->assertSame($row->getColumn('sum_bandwidth'), 4364);
        $this->assertSame($row->getColumn('avg_bandwidth'), 872);
    }

    public function test_shouldEnrichPageUrls_ZeroShouldCountToAverageCount()
    {
        $this->trackBytes(array(1, 10, 5, 0, null, 3949, 0, null, 399));
        $result = $this->requestAction('getPageUrls');

        $row = $result->getFirstRow();
        $this->assertSame($row->getColumn('avg_bandwidth'), 623);
    }

    public function test_shouldEnrichPageUrls_ShouldNotFailIfNoPageHasBandwidth()
    {
        $this->trackBytes(array(null, null, null, null));
        $result = $this->requestAction('getPageUrls');

        $row = $result->getFirstRow();
        $this->assertSame($row->getColumn('max_bandwidth'), false);
        $this->assertSame($row->getColumn('min_bandwidth'), false);
        $this->assertSame($row->getColumn('sum_bandwidth'), 0);
        $this->assertSame($row->getColumn('avg_bandwidth'), 0);
    }

    public function test_shouldEnrichPageTitles()
    {
        $this->trackBytes(array(1, 10, null, 5, null, 3949, 399));

        $result = $this->requestAction('getPageTitles');

        $row = $result->getFirstRow();
        $this->assertSame($row->getColumn('max_bandwidth'), 3949);
        $this->assertSame($row->getColumn('min_bandwidth'), 1);
        $this->assertSame($row->getColumn('sum_bandwidth'), 4364);
        $this->assertSame($row->getColumn('avg_bandwidth'), 872);
    }

    public function test_shouldEnrichDownloads()
    {
        $this->trackDownloadBytes(array(1, 10, null, 5, null, 3949, 399));

        $result = $this->requestAction('getDownloads');

        $row = $result->getFirstRow();
        $this->assertSame($row->getColumn('max_bandwidth'), 3949);
        $this->assertSame($row->getColumn('min_bandwidth'), 1);
        $this->assertSame($row->getColumn('sum_bandwidth'), 4364);
        $this->assertSame($row->getColumn('avg_bandwidth'), 872);
    }

    private function trackBytes($bytes)
    {
        $tracker = $this->getTracker();

        foreach ($bytes as $byte) {
            if (null === $byte) {
                $tracker->setDebugStringAppend('');
            } else {
                $tracker->setDebugStringAppend('bw_bytes=' . $byte);
            }

            $tracker->doTrackPageView('test');
        }
    }

    private function trackDownloadBytes($bytes)
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

    private function setUser()
    {
        $pseudoMockAccess = new FakeAccess();
        FakeAccess::setSuperUserAccess(false);
        FakeAccess::$idSitesView = array(1);
        FakeAccess::$identity = 'aUser';
        Access::setSingletonInstance($pseudoMockAccess);
    }

    private function setSuperUser()
    {
        $pseudoMockAccess = new FakeAccess();
        $pseudoMockAccess::setSuperUserAccess(true);
        Access::setSingletonInstance($pseudoMockAccess);
    }

    private function requestAction($action)
    {
        /** @var DataTable $result */
        $result = Request::processRequest('Actions.' . $action, array(
            'idSite' => 1,
            'period' => 'day',
            'date' => '2014-04-04'
        ), $defaultParams = array());
        return $result;
    }

    /**
     * @return \PiwikTracker
     */
    private function getTracker()
    {
        $tracker = Fixture::getTracker(1, '2014-04-04 00:01:01', true, true);
        $tracker->setTokenAuth(Fixture::getTokenAuth());
        return $tracker;
    }

}

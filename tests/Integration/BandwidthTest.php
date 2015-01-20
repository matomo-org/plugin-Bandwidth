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
 * Bandidth Class and Bandwidth Tracker test
 *
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
        $this->assertBandwidthStats($row, $maxB = 3949, $minB = 1, $sumB = 4364, $avgB = 872);
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
        $this->assertBandwidthStats($row, $maxB = false, $minB = false, $sumB = 0, $avgB = 0);
    }

    public function test_shouldEnrichPageUrls_ShouldDefineASegment()
    {
        $this->trackBytes(array(1, 10, 5, 0, null, 3949, 0, null, 399));
        $result = $this->requestAction('getPageUrls', array('segment' => 'bandwidth>=34'));

        $row = $result->getFirstRow();

        $this->assertBandwidthStats($row, $maxB = 3949, $minB = 399, $sumB = 4348, $avgB = 2174);
    }

    public function test_shouldEnrichPageTitles()
    {
        $this->trackBytes(array(1, 10, null, 5, null, 3949, 399));

        $result = $this->requestAction('getPageTitles');

        $row = $result->getFirstRow();
        $this->assertBandwidthStats($row, $maxB = 3949, $minB = 1, $sumB = 4364, $avgB = 872);
    }

    public function test_shouldEnrichDownloads()
    {
        $this->trackDownloadBytes(array(1, 10, null, 5, null, 3949, 397));

        $result = $this->requestAction('getDownloads');

        $row = $result->getFirstRow();
        $this->assertBandwidthStats($row, $maxB = 3949, $minB = 1, $sumB = 4362, $avgB = 872);
    }

    public function test_manyDifferentUrlsWithFolders_ShouldAggregateStats()
    {
        $tracker = $this->getTracker();
        $this->trackUrlByte($tracker, 10, '/index');
        $this->trackUrlByte($tracker, 20, '/blog/2014/test');
        $this->trackUrlByte($tracker, 15, '/blog/2014/test2');
        $this->trackUrlByte($tracker, 3, '/team/contact');
        $this->trackUrlByte($tracker, null, '/index');
        $this->trackUrlByte($tracker, 10, '/index');

        $result = $this->requestAction('getPageUrls');
        $this->assertSame(3, $result->getRowsCount());

        $row = $result->getRowFromLabel('/index');
        $this->assertBandwidthStats($row, $maxB = 10, $minB = 10, $sumB = 20, $avgB = 10);
        $row = $result->getRowFromLabel('team');
        $this->assertBandwidthStats($row, $maxB = 3, $minB = 3, $sumB = 3, $avgB = 3);
        $row = $result->getRowFromLabel('blog');
        $this->assertBandwidthStats($row, $maxB = 35, $minB = 35, $sumB = 35, $avgB = 17);

        // request subtable /blog
        $result = $this->requestAction('getPageUrls', array('idSubtable' => $row->getIdSubDataTable()));
        $row    = $result->getRowFromLabel('2014');
        $this->assertBandwidthStats($row, $maxB = 35, $minB = 35, $sumB = 35, $avgB = 17);

        // request subtable /blog/2014
        $result = $this->requestAction('getPageUrls', array('idSubtable' => $row->getIdSubDataTable()));
        $row    = $result->getRowFromLabel('/test');
        $this->assertBandwidthStats($row, $maxB = 20, $minB = 20, $sumB = 20, $avgB = 20);
    }

    private function assertBandwidthStats(DataTable\Row $row, $maxB, $minB, $sumB, $avgB)
    {
        $this->assertSame($row->getColumn('max_bandwidth'), $maxB);
        $this->assertSame($row->getColumn('min_bandwidth'), $minB);
        $this->assertSame($row->getColumn('sum_bandwidth'), $sumB);
        $this->assertSame($row->getColumn('avg_bandwidth'), $avgB);
    }

    private function trackBytes($bytes)
    {
        $tracker = $this->getTracker();

        foreach ($bytes as $byte) {
            $this->trackUrlByte($tracker, $byte);
        }
    }

    private function trackUrlByte(\PiwikTracker $tracker, $byte, $url = null)
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

    /**
     * @param string $action
     * @param bool|string $segment
     * @return DataTable
     */
    private function requestAction($action, $additionalParams = array())
    {
        $params = array(
            'idSite' => 1,
            'period' => 'day',
            'date' => '2014-04-04'
        );

        if (!empty($additionalParams)) {
            $params = array_merge($params, $additionalParams);
        }

        return Request::processRequest('Actions.' . $action, $params);
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

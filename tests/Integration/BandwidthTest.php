<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\Bandwidth\tests\Integration;

use Piwik\API\Request;
use Piwik\DataTable;
use Piwik\Db;
use Piwik\Plugin;
use Piwik\Plugins\Bandwidth\API;
use Piwik\Plugins\Bandwidth\tests\Framework\TestCase\IntegrationTestCase;

/**
 * Bandidth Class and Bandwidth Tracker test
 *
 * @group Bandwidth
 * @group BandwidthTest
 * @group Plugins
 */
class BandwidthTest extends IntegrationTestCase
{
    /**
     * @var API
     */
    private $api;

    protected $date = '2014-04-04';

    public function setUp()
    {
        parent::setUp();
        $this->api = API::getInstance();

        $this->setUser();
    }

    public function test_shouldEnrichPageUrls()
    {
        $this->trackPageviews(array(1, 10, null, 5, null, 3949, 399));

        $result = $this->requestAction('getPageUrls');

        $row = $result->getFirstRow();
        $this->assertBandwidthStats($row, $maxB = 3949, $minB = 1, $sumB = 4364, $avgB = 872);
    }

    public function test_shouldEnrichPageUrls_ZeroShouldCountToAverageCount()
    {
        $this->trackPageviews(array(1, 10, 5, 0, null, 3949, 0, null, 399));
        $result = $this->requestAction('getPageUrls');

        $row = $result->getFirstRow();
        $this->assertSame($row->getColumn('avg_bandwidth'), 623);
    }

    public function test_shouldEnrichPageUrls_ShouldNotFailIfNoPageHasBandwidth()
    {
        $this->trackPageviews(array(null, null, null, null));
        $result = $this->requestAction('getPageUrls');

        $row = $result->getFirstRow();
        $this->assertBandwidthStats($row, $maxB = false, $minB = false, $sumB = 0, $avgB = 0);
    }

    public function test_shouldEnrichPageUrls_ShouldDefineASegment()
    {
        $this->trackPageviews(array(1, 10, 5, 0, null, 3949, 0, null, 399));
        $result = $this->requestAction('getPageUrls', array('segment' => 'bandwidth>=34'));

        $row = $result->getFirstRow();

        $this->assertBandwidthStats($row, $maxB = 3949, $minB = 399, $sumB = 4348, $avgB = 2174);
    }

    public function test_shouldEnrichPageTitlesAndFormat_IfRequested()
    {
        $this->trackPageviews(array(1, 10, null, 5, null, 3949, 399));

        $result = $this->requestAction('getPageTitles', array('format_metrics' => '1'));

        $row = $result->getFirstRow();
        $this->assertBandwidthStats($row, $maxB = '3.86 K', $minB = '1 B', $sumB = '4.26 K', $avgB = '872 B');
    }

    public function test_shouldEnrichPageTitles()
    {
        $this->trackPageviews(array(1, 10, null, 5, null, 3949, 399));

        $result = $this->requestAction('getPageTitles');

        $row = $result->getFirstRow();
        $this->assertBandwidthStats($row, $maxB = 3949, $minB = 1, $sumB = 4364, $avgB = 872);
    }

    public function test_shouldEnrichDownloads()
    {
        $this->trackDownloads(array(1, 10, null, 5, null, 3949, 397));

        $result = $this->requestAction('getDownloads');

        $row = $result->getFirstRow();
        $this->assertBandwidthStats($row, $maxB = 3949, $minB = 1, $sumB = 4362, $avgB = 872);
    }

    public function test_manyDifferentUrlsWithFolders_ShouldAggregateStats()
    {
        $tracker = $this->getTracker();
        $this->trackPageview($tracker, 10, '/index');
        $this->trackPageview($tracker, 20, '/blog/2014/test');
        $this->trackPageview($tracker, 15, '/blog/2014/test2');
        $this->trackPageview($tracker, 3, '/team/contact');
        $this->trackPageview($tracker, null, '/index');
        $this->trackPageview($tracker, 10, '/index');

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

    /**
     * @param string $action
     * @param array $additionalParams
     * @return DataTable
     */
    private function requestAction($action, $additionalParams = array())
    {
        $params = array(
            'idSite' => 1,
            'period' => 'day',
            'date'   => $this->date
        );

        if (!empty($additionalParams)) {
            $params = array_merge($params, $additionalParams);
        }

        return Request::processRequest('Actions.' . $action, $params);
    }

}

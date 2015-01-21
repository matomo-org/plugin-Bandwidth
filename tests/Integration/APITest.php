<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\Bandwidth\tests\Integration;

use Piwik\DataTable;
use Piwik\Db;
use Piwik\Plugin;
use Piwik\Plugins\Bandwidth\API;
use Piwik\Plugins\Bandwidth\Metrics;
use Piwik\Plugins\Bandwidth\tests\Framework\TestCase\IntegrationTestCase;

/**
 * @group Bandwidth
 * @group APITest
 * @group Plugins
 */
class APITest extends IntegrationTestCase
{
    /**
     * @var API
     */
    private $api;
    
    protected $date = '2014-04-04';
    private $idSite = 1;

    public function setUp()
    {
        parent::setUp();
        $this->api = API::getInstance();
        $this->setUser();
    }

    public function test_get_shouldReturnADataTable()
    {
        $this->trackPageviews(array(1));

        $result = $this->api->get($this->idSite, 'month', $this->date);
        $result->applyQueuedFilters();

        $this->assertTrue($result instanceof DataTable);
    }

    public function test_get_shouldReturnZero_IfNoTrackedBandwidth()
    {
        $result = $this->api->get($this->idSite, 'day', $this->date);

        $this->assertTotalBandwidthValue(0, 0, 0, $result);
    }

    public function test_get_shouldReturnTheSumOfAllOnlyPageviews()
    {
        $this->trackPageviews(array(1, 10, 20, 348));
        $result = $this->api->get($this->idSite, 'day', $this->date);

        $this->assertTotalBandwidthValue(379, 379, 0, $result);
    }

    public function test_get_shouldReturnTheSumOfAllOnlyDownloads()
    {
        $this->trackDownloads(array(1, 10, 20, 348));
        $result = $this->api->get($this->idSite, 'day', $this->date);

        $this->assertTotalBandwidthValue(379, 0, 379, $result);
    }

    public function test_get_shouldReturnTheSumOfAll_MixedPageviewsAndDownloads()
    {
        $this->trackPageviews(array(1, 10, 20, 49));
        $this->trackDownloads(array(59, 4, 1, 34, 592));

        $result = $this->api->get($this->idSite, 'day', $this->date);
        $result->applyQueuedFilters();

        $this->assertTotalBandwidthValue(770, 80, 690, $result);
    }

    public function test_get_shouldReturnTheSumOfAll_DifferentPeriod()
    {
        $this->trackPageviews(array(1, 10, 20, 49));
        $this->trackDownloads(array(59, 4, 1, 34, 592));

        $result = $this->api->get($this->idSite, 'month', $this->date);
        $result->applyQueuedFilters();

        $this->assertTotalBandwidthValue(770, 80, 690, $result);
    }

    public function test_get_shouldReturnFalse_IfColumnShallNotBeDisplayed()
    {
        $this->trackPageviews(array(1, 10, 20, 49));
        $this->trackDownloads(array(59, 4, 1, 34));

        $result = $this->api->get($this->idSite, 'day', $this->date, false, 'nb_visits');
        $result->applyQueuedFilters();

        $this->assertTotalBandwidthValue(false, false, false, $result);
    }

    public function test_get_shouldReturnSomeColumns_IfValidOnesRequested()
    {
        $this->trackPageviews(array(1, 10, 20, 49));
        $this->trackDownloads(array(59, 4, 1, 34));

        $displayColumns = Metrics::COLUMN_TOTAL_DOWNLOAD_BANDWIDTH . ',' . Metrics::COLUMN_TOTAL_PAGEVIEW_BANDWIDTH;
        $result = $this->api->get($this->idSite, 'day', $this->date, false, $displayColumns);
        $result->applyQueuedFilters();

        $this->assertTotalBandwidthValue(false, 80, 98, $result);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage checkUserHasViewAccess
     */
    public function test_get_shouldFailIfUserHasNoPermission()
    {
        $this->setAnonymousUser();
        $this->api->get($this->idSite, 'day', $this->date);
    }

    private function assertTotalBandwidthValue($expectedOverall, $expectedPageview, $expectedDownload, DataTable $dataTable)
    {
        $row = $dataTable->getFirstRow();
        $this->assertSame($expectedOverall, $row->getColumn(Metrics::COLUMN_TOTAL_OVERALL_BANDWIDTH));
        $this->assertSame($expectedPageview, $row->getColumn(Metrics::COLUMN_TOTAL_PAGEVIEW_BANDWIDTH));
        $this->assertSame($expectedDownload, $row->getColumn(Metrics::COLUMN_TOTAL_DOWNLOAD_BANDWIDTH));
    }
    
}

<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\Bandwidth\tests\Integration;

use Piwik\Access;
use Piwik\DataAccess\ArchiveTableCreator;
use Piwik\DataTable;
use Piwik\Db;
use Piwik\Plugin;
use Piwik\Plugins\Bandwidth\API;
use Piwik\Plugins\Bandwidth\Metrics;
use Piwik\Tests\Framework\Fixture;
use Piwik\Tests\Framework\Mock\FakeAccess;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;

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

    public function test_get_shouldReturnADataTable()
    {
        $this->trackBytes(array(1));

        $result = $this->api->get(1, 'month', '2014-04-04');
        $result->applyQueuedFilters();

        $this->assertTrue($result instanceof DataTable);
    }

    public function test_get_shouldReturnTheSumOfAll()
    {
        $this->trackBytes(array(1, 10, 20, 348));
        $result = $this->api->get(1, 'day', '2014-04-04');

        $this->assertTotalBandwidthValue(379, $result);
    }

    public function test_get_shouldReturnZeroIfNoTrackedBandwidth()
    {
        $result = $this->api->get(1, 'day', '2014-04-04');

        $this->assertTotalBandwidthValue(0, $result);
    }

    public function test_get_shouldReturnFalseIfColumnShallNotBeDisplayed()
    {
        $result = $this->api->get(1, 'day', '2014-04-04', false, 'nb_visits');
        $result->applyQueuedFilters();

        $this->assertTotalBandwidthValue(false, $result);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage checkUserHasViewAccess
     */
    public function test_get_shouldFailIfUserHasNoPermission()
    {
        $this->setAnonymousUser();
        $this->api->get(1, 'day', '2014-04-04');
    }

    private function assertTotalBandwidthValue($expectedValue, DataTable $dataTable)
    {
        $this->assertSame($expectedValue, $dataTable->getFirstRow()->getColumn(Metrics::METRIC_COLUMN_TOTAL_BANDWIDTH));
    }

    private function trackBytes($bytes)
    {
        $tracker = Fixture::getTracker(1, '2014-04-04 00:01:01', true, true);
        $tracker->setTokenAuth(Fixture::getTokenAuth());

        foreach ($bytes as $byte) {
            $tracker->setDebugStringAppend('bw_bytes=' . $byte);
            $tracker->doTrackPageView('test');
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

    private function setAnonymousUser()
    {
        $pseudoMockAccess = new FakeAccess();
        $pseudoMockAccess::setSuperUserAccess(false);
        $pseudoMockAccess::$identity = 'anonymous';
        Access::setSingletonInstance($pseudoMockAccess);
    }

}

<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\Bandwidth\tests\Unit;

use Piwik\Plugins\Bandwidth\Bandwidth;
use Piwik\Tests\Framework\TestCase\UnitTestCase;

/**
 * @group Bandwidth
 * @group BandwidthTest
 * @group Plugins
 */
class BandwidthTest extends UnitTestCase
{
    /**
     * @var Bandwidth
     */
    private $bandwidth;

    public function setUp()
    {
        parent::setUp();
        $this->bandwidth = new Bandwidth();
    }

    public function test_addMetricTranslations_shouldAddToExistTranslations()
    {
        $translations = array(
            'my' => 'test',
        );

        $this->bandwidth->addMetricTranslations($translations);

        $expected = array(
            'my' => 'test',
            'nb_hits_with_bandwidth' => 'Bandwidth_ColumnHitsWithBandwidth',
            'max_bandwidth' => 'Bandwidth_ColumnMaxBandwidth',
            'min_bandwidth' => 'Bandwidth_ColumnMinBandwidth',
            'sum_bandwidth' => 'Bandwidth_ColumnSumBandwidth',
            'avg_bandwidth' => 'Bandwidth_ColumnAvgBandwidth',
            'nb_total_overall_bandwidth' => 'Bandwidth_ColumnTotalBandwidth'
        );

        $this->assertEquals($expected, $translations);
    }

    public function test_addActionMetrics_shouldAddToExistingMetrics()
    {
        $metric  = array('aggregation' => 'sum', 'query' => 'count(*)');
        $metrics = array(
            3 => $metric,
        );

        $this->bandwidth->addActionMetrics($metrics);

        // should have added metrics
        $this->assertGreaterThanOrEqual(5, count($metrics));

        // should still have original metric
        $this->assertSame($metric, $metrics[3]);

        // make sure each is properly configured
        foreach ($metrics as $metric) {
            $this->assertNotEmpty($metric['aggregation']);
            $this->assertNotEmpty($metric['query']);
        }
    }

}

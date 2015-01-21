<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\Bandwidth\tests\Unit;

use Piwik\Plugins\Bandwidth\Metrics;
use Piwik\Tests\Framework\TestCase\UnitTestCase;

/**
 * @group Bandwidth
 * @group MetricsTest
 * @group Plugins
 */
class MetricsTest extends UnitTestCase
{

    public function test_getMetricTranslations()
    {
        $translations = Metrics::getMetricTranslations();
        $expected     = array(
            'nb_hits_with_bandwidth' => 'Bandwidth_ColumnHitsWithBandwidth',
            'max_bandwidth' => 'Bandwidth_ColumnMaxBandwidth',
            'min_bandwidth' => 'Bandwidth_ColumnMinBandwidth',
            'sum_bandwidth' => 'Bandwidth_ColumnSumBandwidth',
            'avg_bandwidth' => 'Bandwidth_ColumnAvgBandwidth',
            'nb_total_overall_bandwidth'  => 'Bandwidth_ColumnTotalOverallBandwidth',
            'nb_total_pageview_bandwidth' => 'Bandwidth_ColumnTotalPageviewBandwidth',
            'nb_total_download_bandwidth' => 'Bandwidth_ColumnTotalDownloadBandwidth'
        );

        $this->assertEquals($expected, $translations);
    }

}

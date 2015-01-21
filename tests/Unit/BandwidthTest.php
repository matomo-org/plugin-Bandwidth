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

        $this->assertGreaterThanOrEqual(9, count($translations));
        $this->assertSame('test', $translations['my']); // make sure this one is not overwritten
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

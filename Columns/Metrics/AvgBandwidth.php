<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Plugins\Bandwidth\Columns\Metrics;

use Piwik\DataTable\Row;
use Piwik\Metrics\Formatter;
use Piwik\Piwik;
use Piwik\Plugins\Bandwidth\Metrics;

/**
 * The average amount bandwidth per page.
 */
class AvgBandwidth extends Base
{
    public function getName()
    {
        return 'avg_bandwidth';
    }

    public function getTranslatedName()
    {
        return Piwik::translate('Bandwidth_ColumnAvgBandwidth');
    }

    public function compute(Row $row)
    {
        $hits = $this->getMetricAsIntSafe($row, Metrics::METRICS_NB_HITS_WITH_BANDWIDTH);
        $sum  = $this->getMetricAsIntSafe($row, Metrics::METRICS_PAGE_SUM_BANDWIDTH);

        if (!empty($hits) && !empty($sum)) {
            $avg = floor($sum / $hits);
        } else {
            $avg = 0;
        }

        return (int) $avg;
    }

    public function getDependentMetrics()
    {
        return array('nb_hits_with_bandwidth', 'sum_bandwidth');
    }
}
<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\Bandwidth\Columns\Metrics;

use Piwik\DataTable\Row;
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
        // NOTE: Fetching columns by name or id is required in some cases (like flattened reports)
        //       This should be changed in the future: see https://github.com/piwik/piwik/issues/10916
        if ($row->hasColumn('nb_hits_with_bandwidth')) {
            $hits = $this->getMetricAsIntSafe($row, 'nb_hits_with_bandwidth');
        } else {
            $hits = $this->getMetricAsIntSafe($row, Metrics::METRICS_NB_HITS_WITH_BANDWIDTH);
        }

        if ($row->hasColumn('sum_bandwidth')) {
            $sum = $this->getMetricAsIntSafe($row, 'sum_bandwidth');
        } else {
            $sum = $this->getMetricAsIntSafe($row, Metrics::METRICS_PAGE_SUM_BANDWIDTH);
        }

        if (!empty($hits) && !empty($sum)) {
            $avg = floor($sum / $hits);
        } else {
            $avg = 0;
        }

        return (int)$avg;
    }

    public function getDependentMetrics()
    {
        return ['nb_hits_with_bandwidth', 'sum_bandwidth'];
    }
}
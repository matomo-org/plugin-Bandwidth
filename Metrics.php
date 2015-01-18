<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\Bandwidth;
use Piwik\DataTable;
use Piwik\Metrics\Formatter;
use Piwik\Piwik;
use Piwik\Plugins\Bandwidth\Columns\Bandwidth as BandwidthColumn;
use Piwik\Url;

class Metrics
{

    const METRICS_PAGE_SUM_BANDWIDTH = 1090;
    const METRICS_PAGE_MIN_BANDWIDTH = 1091;
    const METRICS_PAGE_MAX_BANDWIDTH = 1092;
    const METRICS_NB_HITS_WITH_BANDWIDTH   = 1093;

    public static function getMetricTranslations()
    {
        return array(
            'avg_bandwidth' => Piwik::translate('Bandwidth_ColumnAvgBandwidth'),
            'sum_bandwidth' => Piwik::translate('Bandwidth_ColumnSumBandwidth'),
        );
    }

    public static function getActionMetrics()
    {
        $column = new BandwidthColumn();
        $column = $column->getColumnName();

        $metricsConfig = array();
        $metricsConfig[self::METRICS_PAGE_SUM_BANDWIDTH] = array(
            'aggregation' => 'sum',
            'query' => "sum(
                    case when $column is null
                        then 0
                        else $column
                    end
            )"
        );
        $metricsConfig[self::METRICS_NB_HITS_WITH_BANDWIDTH] = array(
            'aggregation' => 'sum',
            'query' => "sum(
                case when $column is null
                    then 0
                    else 1
                end
            )"
        );
        $metricsConfig[self::METRICS_PAGE_MIN_BANDWIDTH] = array(
            'aggregation' => 'min',
            'query' => "min($column)"
        );
        $metricsConfig[self::METRICS_PAGE_MAX_BANDWIDTH] = array(
            'aggregation' => 'max',
            'query' => "max($column)"
        );

        return $metricsConfig;
    }

}

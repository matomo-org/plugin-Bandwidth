<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\Plugins\Bandwidth;

use Piwik\Piwik;
use Piwik\Plugins\Bandwidth\Columns\Bandwidth as BandwidthColumn;
use Piwik\Plugins\Bandwidth\Columns\Metrics\AvgBandwidth;
use Piwik\Plugins\Bandwidth\Columns\Metrics\DownloadBandwidth;
use Piwik\Plugins\Bandwidth\Columns\Metrics\HitsWithBandwidth;
use Piwik\Plugins\Bandwidth\Columns\Metrics\MaxBandwidth;
use Piwik\Plugins\Bandwidth\Columns\Metrics\MinBandwidth;
use Piwik\Plugins\Bandwidth\Columns\Metrics\OverallBandwidth;
use Piwik\Plugins\Bandwidth\Columns\Metrics\PageviewBandwidth;
use Piwik\Plugins\Bandwidth\Columns\Metrics\SumBandwidth;

class Metrics
{
    const METRICS_PAGE_SUM_BANDWIDTH = 1090;
    const METRICS_PAGE_MIN_BANDWIDTH = 1091;
    const METRICS_PAGE_MAX_BANDWIDTH = 1092;
    const METRICS_NB_HITS_WITH_BANDWIDTH = 1093;

    const COLUMN_TOTAL_OVERALL_BANDWIDTH = 'nb_total_overall_bandwidth';
    const COLUMN_TOTAL_PAGEVIEW_BANDWIDTH = 'nb_total_pageview_bandwidth';
    const COLUMN_TOTAL_DOWNLOAD_BANDWIDTH = 'nb_total_download_bandwidth';

    public static function getNumericRecordNameToColumnsMapping()
    {
        return [
            Archiver::BANDWIDTH_TOTAL_RECORD    => Metrics::COLUMN_TOTAL_OVERALL_BANDWIDTH,
            Archiver::BANDWIDTH_PAGEVIEW_RECORD => Metrics::COLUMN_TOTAL_PAGEVIEW_BANDWIDTH,
            Archiver::BANDWIDTH_DOWNLOAD_RECORD => Metrics::COLUMN_TOTAL_DOWNLOAD_BANDWIDTH,
        ];
    }

    /**
     * @return \Piwik\Plugins\Bandwidth\Columns\Metrics\Base[]
     */
    public static function getBandwidthMetrics()
    {
        return [
            new HitsWithBandwidth(),
            new MaxBandwidth(),
            new MinBandwidth(),
            new SumBandwidth(),
            new AvgBandwidth(),
        ];
    }

    /**
     * @return \Piwik\Plugin\Metric[]
     */
    public static function getOverallMetrics()
    {
        return [
            new DownloadBandwidth(),
            new PageviewBandwidth(),
            new OverallBandwidth(),
        ];
    }

    public static function getMetricTranslations()
    {
        $translations = [];
        foreach (self::getBandwidthMetrics() as $metric) {
            $translations[$metric->getName()] = $metric->getTranslatedName();
        }

        $translations[Metrics::COLUMN_TOTAL_OVERALL_BANDWIDTH]  = Piwik::translate('Bandwidth_ColumnTotalOverallBandwidth');
        $translations[Metrics::COLUMN_TOTAL_PAGEVIEW_BANDWIDTH] = Piwik::translate('Bandwidth_ColumnTotalPageviewBandwidth');
        $translations[Metrics::COLUMN_TOTAL_DOWNLOAD_BANDWIDTH] = Piwik::translate('Bandwidth_ColumnTotalDownloadBandwidth');

        return $translations;
    }

    public static function getActionMetrics()
    {
        $column = new BandwidthColumn();
        $column = $column->getColumnName();

        $metricsConfig                                       = [];
        $metricsConfig[self::METRICS_PAGE_SUM_BANDWIDTH]     = [
            'aggregation' => 'sum',
            'query'       => "sum(
                    case when $column is null
                        then 0
                        else $column
                    end
            )",
        ];
        $metricsConfig[self::METRICS_NB_HITS_WITH_BANDWIDTH] = [
            'aggregation' => 'sum',
            'query'       => "sum(
                case when $column is null
                    then 0
                    else 1
                end
            )",
        ];
        $metricsConfig[self::METRICS_PAGE_MIN_BANDWIDTH]     = [
            'aggregation' => 'min',
            'query'       => "min($column)",
        ];
        $metricsConfig[self::METRICS_PAGE_MAX_BANDWIDTH]     = [
            'aggregation' => 'max',
            'query'       => "max($column)",
        ];

        return $metricsConfig;
    }
}

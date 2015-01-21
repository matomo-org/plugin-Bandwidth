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
use Piwik\Plugins\Bandwidth\Columns\Metrics\AvgBandwidth;
use Piwik\Plugins\Bandwidth\Columns\Metrics\HitsWithBandwidth;
use Piwik\Plugins\Bandwidth\Columns\Metrics\MaxBandwidth;
use Piwik\Plugins\Bandwidth\Columns\Metrics\MinBandwidth;
use Piwik\Plugins\Bandwidth\Columns\Metrics\SumBandwidth;
use Piwik\Url;

class Metrics
{

    const METRICS_PAGE_SUM_BANDWIDTH = 1090;
    const METRICS_PAGE_MIN_BANDWIDTH = 1091;
    const METRICS_PAGE_MAX_BANDWIDTH = 1092;
    const METRICS_NB_HITS_WITH_BANDWIDTH = 1093;

    const COLUMN_TOTAL_OVERALL_BANDWIDTH  = 'nb_total_overall_bandwidth';
    const COLUMN_TOTAL_PAGEVIEW_BANDWIDTH = 'nb_total_pageview_bandwidth';
    const COLUMN_TOTAL_DOWNLOAD_BANDWIDTH = 'nb_total_download_bandwidth';

    public static function getNumericRecordNameToColumnsMapping()
    {
        return array(
            Archiver::BANDWIDTH_TOTAL_RECORD    => Metrics::COLUMN_TOTAL_OVERALL_BANDWIDTH,
            Archiver::BANDWIDTH_PAGEVIEW_RECORD => Metrics::COLUMN_TOTAL_PAGEVIEW_BANDWIDTH,
            Archiver::BANDWIDTH_DOWNLOAD_RECORD => Metrics::COLUMN_TOTAL_DOWNLOAD_BANDWIDTH,
        );
    }

    /**
     * @return \Piwik\Plugin\ProcessedMetric[]
     */
    public static function getBandwidthMetrics()
    {
        return array(
            new HitsWithBandwidth(),
            new MaxBandwidth(),
            new MinBandwidth(),
            new SumBandwidth(),
            new AvgBandwidth()
        );
    }

    public static function getMetricTranslations()
    {
        $translations = array();
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

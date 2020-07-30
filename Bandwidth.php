<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\Plugins\Bandwidth;

use Piwik\Common;
use Piwik\DataTable;
use Piwik\FrontController;
use Piwik\Metrics\Formatter;
use Piwik\Plugin\ViewDataTable;
use Piwik\Plugins\Bandwidth\Columns\Bandwidth as BandwidthColumn;
use Piwik\Plugins\CoreVisualizations\Visualizations\Sparklines;

class Bandwidth extends \Piwik\Plugin
{
    // module => action. The ones that are defined here will be enriched by bandwidth columns when displayed in the UI
    private $reportsToEnrich = [
        'Actions'          => [
            'getPageUrls',
            'getPageUrl',
            'getPageTitles',
            'getPageTitle',
            'getDownloads',
            'getDownload',
            'getOutlink',
            'getOutlinks',
            'getEntryPageTitles',
            'getEntryPageUrls',
            'getExitPageTitles',
            'getExitPageUrls',
            'getSiteSearchKeywords',
            'getSiteSearchNoResultKeywords',
            'getPageTitlesFollowingSiteSearch',
            'getPageUrlsFollowingSiteSearch',
        ],
        'CustomDimensions' => [
            'getCustomDimension',
        ],
    ];

    // we will only show columns in that report in the UI if there was at least one byte tracked for the defined metric
    private $enrichReportIfTotalHasValue = [
        'Actions.getPageUrls'   => Metrics::COLUMN_TOTAL_PAGEVIEW_BANDWIDTH,
        'Actions.getPageTitles' => Metrics::COLUMN_TOTAL_PAGEVIEW_BANDWIDTH,
        'Actions.getDownloads'  => Metrics::COLUMN_TOTAL_DOWNLOAD_BANDWIDTH,
        '*'                     => Metrics::COLUMN_TOTAL_OVERALL_BANDWIDTH // for all other reports use this
    ];

    /**
     * @see \Piwik\Plugin::registerEvents
     */
    public function registerEvents()
    {
        $hooks = [
            'ViewDataTable.configure'                        => 'configureViewDataTable',
            'Actions.Archiving.addActionMetrics'             => 'addActionMetrics',
            'Metrics.getDefaultMetricTranslations'           => 'addMetricTranslations',
            'Actions.getCustomActionDimensionFieldsAndJoins' => 'provideActionDimensionFields',
            'Metrics.addMetricIdToNameMapping'               => 'addMetricIdToNameMapping',
        ];

        foreach ($this->reportsToEnrich as $module => $actions) {
            foreach ($actions as $action) {
                $hooks['API.' . $module . '.' . $action . '.end'] = 'enrichApi';
            }
        }

        return $hooks;
    }

    public function renderSparklines(&$out)
    {
        $out .= FrontController::getInstance()->dispatch('Bandwidth', 'sparklines');
    }

    public function addMetricTranslations(&$translations)
    {
        $metrics      = Metrics::getMetricTranslations();
        $translations = array_merge($translations, $metrics);
    }

    public function addActionMetrics(&$metricsConfig)
    {
        foreach (Metrics::getActionMetrics() as $metric => $config) {
            $metricsConfig[$metric] = $config;
        }
    }

    public function addMetricIdToNameMapping(&$mapping)
    {
        foreach (Metrics::getBandwidthMetrics() as $metric) {
            $metricId = $metric->getMetricId();
            if (!is_int($metricId)) {
                continue;
            }
            $mapping[$metricId] = $metric->getName();
        }
    }

    public function configureViewDataTable(ViewDataTable $view)
    {
        $module = $view->requestConfig->getApiModuleToRequest();
        $method = $view->requestConfig->getApiMethodToRequest();

        if ($module === 'API' && $method === 'get' && property_exists($view->config, 'selectable_columns')) {
            // here we want to make sure the total column is selectable
            $selectable = $view->config->selectable_columns ?: [];
            $columns    = array_values(Metrics::getNumericRecordNameToColumnsMapping());

            $view->config->selectable_columns = array_merge($selectable, $columns);
            $view->config->addTranslations(Metrics::getMetricTranslations());
        }

        if ($module === 'API' && $method === 'get' && $view->isViewDataTableId(Sparklines::ID)) {
            // TODO: should use a metric class or something so we don't have to manually support comparisons here

            /** @var Sparklines $view */
            $view->config->addSparklineMetric(Metrics::COLUMN_TOTAL_OVERALL_BANDWIDTH);
            $view->config->filters[] = function (DataTable $table) use ($view) {
                $firstRow = $table->getFirstRow();
                $this->formatTotalBandwidth($firstRow);

                $comparisons = $firstRow->getComparisons();
                if (!empty($comparisons)) {
                    foreach ($comparisons->getRows() as $compareRow) {
                        $this->formatTotalBandwidth($compareRow);
                    }
                }
            };
        }

        if (array_key_exists($module, $this->reportsToEnrich) && in_array($method, $this->reportsToEnrich[$module])) {

            $idSite = Common::getRequestVar('idSite');
            $date   = Common::getRequestVar('date');
            $period = Common::getRequestVar('period', 'month', 'string');

            if (array_key_exists($module . '.' . $method, $this->enrichReportIfTotalHasValue)) {
                $columnToCompare = $this->enrichReportIfTotalHasValue[$module . '.' . $method];
            } else {
                $columnToCompare = $this->enrichReportIfTotalHasValue['*'];
            }

            $bandwidthDimension = new BandwidthColumn();
            $isUsed             = $bandwidthDimension->isUsedInSite($idSite, $period, $date, $columnToCompare);

            if (!$isUsed) {
                return;
            }

            $view->config->columns_to_display[] = 'avg_bandwidth';
            $view->config->columns_to_display[] = 'sum_bandwidth';
            $view->config->addTranslations(Metrics::getMetricTranslations());
        }
    }

    public function enrichApi(DataTable\DataTableInterface $dataTable, $params)
    {
        $dataTable->filter(function (DataTable $dataTable) {
            $extraProcessedMetrics = $dataTable->getMetadata(DataTable::EXTRA_PROCESSED_METRICS_METADATA_NAME);

            if (empty($extraProcessedMetrics)) {
                $extraProcessedMetrics = [];
            }

            foreach (Metrics::getBandwidthMetrics() as $metric) {
                $extraProcessedMetrics[] = $metric;
            }
            $dataTable->setMetadata(DataTable::EXTRA_PROCESSED_METRICS_METADATA_NAME, $extraProcessedMetrics);
        });

        $dataTable->filter(function (DataTable $dataTable) {
            $metricIdsToName = [];
            foreach (Metrics::getBandwidthMetrics() as $metric) {
                $metricId = $metric->getMetricId();
                if (!empty($metricId)) {
                    $metricIdsToName[$metricId] = $metric->getName();
                }
            }
            $dataTable->queueFilter('ReplaceColumnNames', [$metricIdsToName]);

        });

    }

    public function provideActionDimensionFields(&$fields, &$joins)
    {
        $column   = new BandwidthColumn();
        $fields[] = $column->getColumnName();
    }

    private function formatTotalBandwidth(DataTable\Row $firstRow)
    {
        $nbTotalBandwidth = $firstRow->getColumn(Metrics::COLUMN_TOTAL_OVERALL_BANDWIDTH);

        if (is_numeric($nbTotalBandwidth)) {
            $formatter        = new Formatter();
            $nbTotalBandwidth = $formatter->getPrettySizeFromBytes((int)$nbTotalBandwidth, null, 2);
            $firstRow->setColumn(Metrics::COLUMN_TOTAL_OVERALL_BANDWIDTH, $nbTotalBandwidth);
        }

        return $nbTotalBandwidth;
    }
}

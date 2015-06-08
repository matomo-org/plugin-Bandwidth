<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\Bandwidth;

use Piwik\Common;
use Piwik\DataTable;
use Piwik\FrontController;
use Piwik\Metrics\Formatter;
use Piwik\Period\Range;
use Piwik\Piwik;
use Piwik\Plugin;
use Piwik\Plugin\ViewDataTable;
use Piwik\Url;
use Piwik\Plugins\Bandwidth\Columns\Bandwidth as BandwidthColumn;

class Bandwidth extends \Piwik\Plugin
{
    // module => action. The ones that are defined here will be enriched by bandwidth columns when displayed in the UI
    private $reportsToEnrich = array(
        'Actions' => array('getPageUrls', 'getPageTitles', 'getDownloads'),
    );

    // we will only show columns in that report in the UI if there was at least one byte tracked for the defined metric
    private $enrichReportIfTotalHasValue = array(
        'Actions.getPageUrls'   => Metrics::COLUMN_TOTAL_PAGEVIEW_BANDWIDTH,
        'Actions.getPageTitles' => Metrics::COLUMN_TOTAL_PAGEVIEW_BANDWIDTH,
        'Actions.getDownloads'  => Metrics::COLUMN_TOTAL_DOWNLOAD_BANDWIDTH,
        '*' => Metrics::COLUMN_TOTAL_OVERALL_BANDWIDTH // for all other reports use this
    );

    /**
     * @see Piwik\Plugin::getListHooksRegistered
     */
    public function getListHooksRegistered()
    {
        $hooks = array(
            'ViewDataTable.configure' => 'configureViewDataTable',
            'Actions.Archiving.addActionMetrics' => 'addActionMetrics',
            'Metrics.getDefaultMetricTranslations' => 'addMetricTranslations',
            'Template.VisitsSummaryOverviewSparklines' => 'renderSparklines'
        );

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

    public function configureViewDataTable(ViewDataTable $view)
    {
        $module = $view->requestConfig->getApiModuleToRequest();
        $method = $view->requestConfig->getApiMethodToRequest();

        if ($module === 'API' && $method === 'get' && property_exists($view->config, 'selectable_columns')) {
            // here we want to make sure the total column is selectable
            $selectable = $view->config->selectable_columns ? : array();
            $columns = array_values(Metrics::getNumericRecordNameToColumnsMapping());

            $view->config->selectable_columns = array_merge($selectable, $columns);
            $view->config->addTranslations(Metrics::getMetricTranslations());
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
            $isUsed = $bandwidthDimension->isUsedInSite($idSite, $period, $date, $columnToCompare);

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
                $extraProcessedMetrics = array();
            }

            foreach (Metrics::getBandwidthMetrics() as $metric) {
                $extraProcessedMetrics[] = $metric;
            }

            $dataTable->setMetadata(DataTable::EXTRA_PROCESSED_METRICS_METADATA_NAME, $extraProcessedMetrics);
        });
    }

}

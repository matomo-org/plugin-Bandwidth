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
use Piwik\FrontController;
use Piwik\Metrics\Formatter;
use Piwik\Piwik;
use Piwik\Plugin;
use Piwik\Plugin\ViewDataTable;
use Piwik\Url;

class Bandwidth extends \Piwik\Plugin
{
    private $reportsToEnrich = array(
        'Actions' => array('getPageUrls', 'getPageTitles', 'getDownloads'),
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
        $translations[Metrics::COLUMN_TOTAL_OVERALL_BANDWIDTH] = Piwik::translate('Bandwidth_ColumnTotalBandwidth');
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
            $selectable = $view->config->selectable_columns ? : array();
            $metric  = Metrics::COLUMN_TOTAL_OVERALL_BANDWIDTH;
            $columns = array($metric);

            $view->config->selectable_columns = array_merge($selectable, $columns);
            $view->config->addTranslation($metric, Piwik::translate('Bandwidth_ColumnTotalBandwidth'));
        }

        if (array_key_exists($module, $this->reportsToEnrich) && in_array($method, $this->reportsToEnrich[$module])) {
            $view->config->columns_to_display[] = 'avg_bandwidth';
            $view->config->columns_to_display[] = 'sum_bandwidth';
            $view->config->addTranslations(Metrics::getMetricTranslations());
        }
    }

    public function enrichApi(DataTable $dataTable, $params)
    {
        $extraProcessedMetrics = $dataTable->getMetadata(DataTable::EXTRA_PROCESSED_METRICS_METADATA_NAME);

        if (empty($extraProcessedMetrics)) {
            $extraProcessedMetrics = array();
        }

        foreach (Metrics::getBandwidthMetrics() as $metric) {
            $extraProcessedMetrics[] = $metric;
        }

        $dataTable->setMetadata(DataTable::EXTRA_PROCESSED_METRICS_METADATA_NAME, $extraProcessedMetrics);
    }

}

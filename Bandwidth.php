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
        );

        foreach ($this->reportsToEnrich as $module => $actions) {
            foreach ($actions as $action) {
                $hooks['API.' . $module . '.' . $action . '.end'] = 'enrichApi';
            }
        }

        return $hooks;
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

        if (property_exists($view->config, 'selectable_columns') &&
            (($module === 'API' && $method === 'get') || ($module === 'VisitsSummary' && $method === 'getEvolutionGraph'))) {
            $columns = array(Metrics::METRIC_COLUMN_TOTAL_BANDWIDTH);
            $view->config->selectable_columns = array_merge($view->config->selectable_columns ? : array(), $columns);
            $view->config->addTranslation('nb_total_bandwidth', Piwik::translate('Bandwidth_ColumnTotalBandwidth'));
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

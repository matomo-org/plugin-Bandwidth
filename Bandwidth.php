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
use Piwik\Plugin\ViewDataTable;
use Piwik\Url;

class Bandwidth extends \Piwik\Plugin
{
    private $reportsToEnrich = array(
        'Actions' => array('getPageUrls', 'getPageTitles')
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
        if (!array_key_exists($module, $this->reportsToEnrich)) {
            return;
        }

        $method  = $view->requestConfig->getApiMethodToRequest();
        $methods = $this->reportsToEnrich[$module];

        if (!in_array($method, $methods)) {
            return;
        }

        $view->config->columns_to_display[] = 'avg_bandwidth';
        $view->config->columns_to_display[] = 'sum_bandwidth';

        $view->config->addTranslations(Metrics::getMetricTranslations());

        $view->config->tooltip_metadata_name = 'tooltip';

        $view->config->filters[] = function (DataTable $dataTable) {
            $formatter = new Formatter();

            foreach ($dataTable->getRows() as $row) {
                foreach (array('sum_bandwidth', 'avg_bandwidth') as $column) {
                    $value = $row->getColumn($column);
                    $formatted = $formatter->getPrettyBytes($value);
                    $row->setColumn($column, $formatted);
                }
            }
        };
    }

    public function enrichApi(DataTable $dataTable, $params)
    {
        $dataTable->queueFilter('ReplaceColumnNames', array(Metrics::$mappingFromIdToName));
        $dataTable->queueFilter(function (DataTable $dataTable) {

            foreach ($dataTable->getRows() as $row) {
                $hits      = $row->getColumn('nb_hits_with_bandwidth');
                $bandwidth = $row->getColumn('sum_bandwidth');
                if (empty($hits) || empty($bandwidth)) {
                    $avg = 0;
                } else {
                    $avg = floor($bandwidth / $hits);
                }
                $row->setColumn('avg_bandwidth', $avg);

                foreach (array('min_bandwidth', 'max_bandwidth', 'sum_bandwidth', 'avg_bandwidth', 'nb_hits_with_bandwidth') as $column) {
                    $value = $row->getColumn($column);
                    if (false !== $value) {
                        $row->setColumn($column, (int) $value);
                    }
                }
            }
        });
    }

}

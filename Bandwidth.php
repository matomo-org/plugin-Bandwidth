<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\Bandwidth;
use Piwik\API\Proxy;
use Piwik\API\Request;
use Piwik\DataTable;
use Piwik\Metrics\Formatter;
use Piwik\Plugin\ViewDataTable;
use Piwik\Url;

/**
 */
class Bandwidth extends \Piwik\Plugin
{
    /**
     * @see Piwik\Plugin::getListHooksRegistered
     */
    public function getListHooksRegistered()
    {
        return array(
            'ViewDataTable.configure'     => 'configureViewDataTable',
            'API.Actions.getPageUrls.end' => 'enrichGetPageUrls',
        );
    }

    public function configureViewDataTable(ViewDataTable $view)
    {
        if ('Actions' == $view->requestConfig->getApiModuleToRequest()
            && 'getPageUrls' == $view->requestConfig->getApiMethodToRequest()) {
            $view->config->columns_to_display[] = 'avg_bandwidth';
            $view->config->columns_to_display[] = 'sum_bandwidth';
            $view->config->columns_to_display[] = 'min_bandwidth';
            $view->config->columns_to_display[] = 'max_bandwidth';

            $view->config->filters[] = function (DataTable $dataTable) {
                $formatter = new Formatter();

                foreach ($dataTable->getRows() as $row) {
                    foreach (array('min_bandwidth', 'max_bandwidth', 'sum_bandwidth', 'avg_bandwidth') as $column) {
                        $value = $row->getColumn($column);
                        $formatted = $formatter->getPrettyBytes($value);
                        $row->setColumn($column, $formatted);
                    }
                }
            };
        }
    }

    public function enrichGetPageUrls($pageUrlsDataTable, $params)
    {
        /** @var DataTable $pageUrlsDataTable */
        $pageUrlParams = $params['parameters'];

        $idsubtable = $pageUrlParams[5];

        $bandwidthDatatable = API::getInstance()->getBandwidth($pageUrlParams[0], $pageUrlParams[1], $pageUrlParams[2], $pageUrlParams[3], $pageUrlParams[4], $pageUrlParams[5]);
        $bandwidthDatatable->applyQueuedFilters();

       // var_dump($bandwidthDatatable);
/*
 *         $finalParameters = $params['parameters'];
        $finalParameters['method'] = 'Bandwidth.getBandwidth';
        $finalParameters['format'] = 'original';

        var_dump($finalParameters);



 *    $url = Url::getQueryStringFromParameters($finalParameters);
        $request = new Request($url);
        $bandwidthDatatable = $request->process();
        $bandwidthDatatable = Proxy::getInstance()->call('\\' . __NAMESPACE__ . '\\API', 'getBandwidth', $finalParameters);
*/
        /*
        var_dump($bandwidthDatatable->getFirstRow());
var_dump($pageUrlsDataTable->getFirstRow());
        */

        /*
        foreach ($pageUrlsDataTable->getRows() as $row) {
            $label = ($row->getColumn('label'));
            $bandwidthRow = $bandwidthDatatable->getRowFromLabel($label);

            foreach (array('min_bandwidth', 'max_bandwidth', 'sum_bandwidth') as $column) {
                if ($bandwidthRow) {
                    $row->setColumn($column, $bandwidthRow->getColumn($column));
                } else {
                    $row->setColumn($column, 0);
                }
            }
        }
*/
       $pageUrlsDataTable->addDataTable($bandwidthDatatable);
    }

}

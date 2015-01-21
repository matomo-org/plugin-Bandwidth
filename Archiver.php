<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\Plugins\Bandwidth;
use Piwik\Plugins\Bandwidth\Columns\Bandwidth as BandwidthColumn;
use Piwik\Tracker\Action;

/**
 * Class Archiver
 * @package Piwik\Plugins\Bandwidth
 *
 * Archiver is class processing raw data into ready ro read reports.
 * It must implement two methods for aggregating daily reports
 * aggregateDayReport() and other for summing daily reports into periods
 * like week, month, year or custom range aggregateMultipleReports().
 *
 * For more detailed information about Archiver please visit Piwik developer guide
 * http://developer.piwik.org/api-reference/Piwik/Plugin/Archiver
 *
 */
class Archiver extends \Piwik\Plugin\Archiver
{
    /**
     * It is a good practice to store your archive names (reports stored in database)
     * in Archiver class constants. You can define as many record names as you want
     * for your plugin.
     *
     * Also important thing is that record name must be prefixed with plugin name.
     *
     * This is only an example record name, so feel free to change it to suit your needs.
     */
    const BANDWIDTH_TOTAL_RECORD    = "Bandwidth_nb_total_overall";
    const BANDWIDTH_PAGEVIEW_RECORD = "Bandwidth_nb_total_pageurl";
    const BANDWIDTH_DOWNLOAD_RECORD = "Bandwidth_nb_total_download";

    public function aggregateDayReport()
    {
        $column = new BandwidthColumn();
        $column = $column->getColumnName();
        $table  = 'log_link_visit_action';
        $field  = 'sum_bandwidth';
        $where  = "$table.$column is not null";

        $selects = array(
            "sum($table.$column) as `$field`"
        );

        $query = $this->getLogAggregator()->queryActionsByDimension(array($column), $where, $selects);
        $this->sumAndInsertNumericRecord($query, self::BANDWIDTH_TOTAL_RECORD, $field);

        $joinLogActionOnColumn = array('idaction_url');
        $whereLogType = "$where AND log_action1.type = ";

        $query = $this->getLogAggregator()->queryActionsByDimension(array($column), $whereLogType . Action::TYPE_PAGE_URL, $selects, false, null, $joinLogActionOnColumn);
        $this->sumAndInsertNumericRecord($query, self::BANDWIDTH_PAGEVIEW_RECORD, $field);

        $query = $this->getLogAggregator()->queryActionsByDimension(array($column), $whereLogType . Action::TYPE_DOWNLOAD, $selects, false, null, $joinLogActionOnColumn);
        $this->sumAndInsertNumericRecord($query, self::BANDWIDTH_DOWNLOAD_RECORD, $field);
    }

    /**
     * @param \Zend_Db_Statement $query
     * @param string $metric
     * @param string $field
     */
    private function sumAndInsertNumericRecord($query, $metric, $field)
    {
        $total = 0;

        while ($row = $query->fetch()) {
            if (!empty($row[$field])) {
                $total += $row[$field];
            }
        }

        $this->getProcessor()->insertNumericRecord($metric, (int) $total);
    }

    public function aggregateMultipleReports()
    {
        $this->getProcessor()->aggregateNumericMetrics(array(
            self::BANDWIDTH_TOTAL_RECORD,
            self::BANDWIDTH_PAGEVIEW_RECORD,
            self::BANDWIDTH_DOWNLOAD_RECORD
        ));
    }

}

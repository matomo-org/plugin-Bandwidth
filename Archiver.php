<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\Plugins\Bandwidth;

use Piwik\Plugins\Bandwidth\Columns\Bandwidth as BandwidthColumn;
use Piwik\Tracker\Action;

/**
 *
 */
class Archiver extends \Piwik\Plugin\Archiver
{
    const BANDWIDTH_TOTAL_RECORD = "Bandwidth_nb_total_overall";
    const BANDWIDTH_PAGEVIEW_RECORD = "Bandwidth_nb_total_pageurl";
    const BANDWIDTH_DOWNLOAD_RECORD = "Bandwidth_nb_total_download";

    public function aggregateDayReport()
    {
        $column = new BandwidthColumn();
        $column = $column->getColumnName();
        $table  = 'log_link_visit_action';
        $field  = 'sum_bandwidth';
        $where  = "$table.$column is not null";

        $selects = [
            "sum($table.$column) as `$field`",
        ];

        $query = $this->getLogAggregator()->queryActionsByDimension([$column], $where, $selects);
        $this->sumAndInsertNumericRecord($query, self::BANDWIDTH_TOTAL_RECORD, $field);

        $joinLogActionOnColumn = ['idaction_url'];
        $whereLogType          = "$where AND log_action1.type = ";

        $query = $this->getLogAggregator()->queryActionsByDimension([$column], $whereLogType . Action::TYPE_PAGE_URL, $selects, false, null, $joinLogActionOnColumn);
        $this->sumAndInsertNumericRecord($query, self::BANDWIDTH_PAGEVIEW_RECORD, $field);

        $query = $this->getLogAggregator()->queryActionsByDimension([$column], $whereLogType . Action::TYPE_DOWNLOAD, $selects, false, null, $joinLogActionOnColumn);
        $this->sumAndInsertNumericRecord($query, self::BANDWIDTH_DOWNLOAD_RECORD, $field);
    }

    /**
     * @param \Zend_Db_Statement $query
     * @param string             $metric
     * @param string             $field
     */
    private function sumAndInsertNumericRecord($query, $metric, $field)
    {
        $total = 0;

        while ($row = $query->fetch()) {
            if (!empty($row[$field])) {
                $total += $row[$field];
            }
        }

        $this->getProcessor()->insertNumericRecord($metric, (int)$total);
    }

    public function aggregateMultipleReports()
    {
        $this->getProcessor()->aggregateNumericMetrics([
            self::BANDWIDTH_TOTAL_RECORD,
            self::BANDWIDTH_PAGEVIEW_RECORD,
            self::BANDWIDTH_DOWNLOAD_RECORD,
        ]);
    }

}

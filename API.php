<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\Plugins\Bandwidth;

use Piwik\Archive;
use Piwik\DataTable;
use Piwik\Piwik;

/**
 * Bandwidth API lets you request bandwidth metrics for page views and downloads.
 *
 * @method static \Piwik\Plugins\Bandwidth\API getInstance()
 */
class API extends \Piwik\Plugin\API
{
    /**
     * Returns bandwidth metrics for the requested site, period, and segment.
     *
     * @param int $idSite The numeric ID of the website to query.
     * @param string $period The period to process, processes data for the period containing the specified date.
     *                       Allowed values: "day", "week", "month", "year", "range".
     * @param string $date The date or date range to process.
     *                     'YYYY-MM-DD', magic keywords (today, yesterday, lastWeek, lastMonth, lastYear),
     *                     or date range (ie, 'YYYY-MM-DD,YYYY-MM-DD', lastX, previousX).
     * @param string|false $segment (Optional) Custom segment to filter the report.
     *                              Example: "referrerName==twitter.com"
     *                              Supports AND (;) and OR (,) operators.
     *                              [See documentation:](https://developer.matomo.org/api-reference/reporting-api-segmentation)
     * @param string|array|false $columns Optional metric columns to return. Accepts a comma-separated list,
     *                                    an array of metric names, or false to return all available metrics.
     * @return DataTable|DataTable\Map Bandwidth metrics with integer values.
     */
    public function get($idSite, $period, $date, $segment = false, $columns = false)
    {
        Piwik::checkUserHasViewAccess($idSite);

        $archive = Archive::build($idSite, $period, $date, $segment);

        $columnNames        = Metrics::getNumericRecordNameToColumnsMapping();
        $archiveRecordNames = array_keys($columnNames);
        $metricColumnNames  = array_values($columnNames);

        $dataTable = $archive->getDataTableFromNumeric($archiveRecordNames);
        $dataTable->filter('ReplaceColumnNames', [$columnNames]);
        $dataTable->filter(function (DataTable $dataTable) use ($metricColumnNames) {
            foreach ($dataTable->getRows() as $row) {
                foreach ($metricColumnNames as $metric) {
                    $row->setColumn($metric, (int)$row->getColumn($metric));
                }
            }
        });

        $requestedColumns = Piwik::getArrayFromApiParameter($columns);
        $columnsToShow    = $requestedColumns ?: $metricColumnNames;
        $dataTable->queueFilter('ColumnDelete', [$columnsToRemove = [], $columnsToShow]);

        return $dataTable;
    }
}

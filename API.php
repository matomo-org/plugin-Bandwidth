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
 * Exposes reporting API endpoints for aggregated bandwidth metrics.
 *
 * @method static \Piwik\Plugins\Bandwidth\API getInstance()
 */
class API extends \Piwik\Plugin\API
{
    /**
     * Returns aggregated bandwidth metrics for the requested site selection and archive period.
     * Includes overall, pageview, and download totals, optionally limited to specific metric columns.
     *
     * @param int|string|int[] $idSite Website ID(s) to query.
     *                         - Single site ID (e.g. 1)
     *                         - Multiple site IDs (e.g. [1, 4, 5])
     *                         - Comma-separated list ("1,4,5") or "all"
     * @param 'day'|'week'|'month'|'year'|'range' $period The period to process, processes data for the period
     *                                                    containing the specified date.
     * @param string $date The date or date range to process.
     *                     'YYYY-MM-DD', magic keywords (today, yesterday, lastWeek, lastMonth, lastYear),
     *                     or date range (ie, 'YYYY-MM-DD,YYYY-MM-DD', lastX, previousX).
     * @param string|null|false $segment Custom segment to filter the report.
     *                                   Example: "referrerName==example.com"
     *                                   Supports AND (;) and OR (,) operators.
     * @param string|array|false $columns Metric columns to return.
     *                                    Accepts a comma-separated list, an array of metric names,
     *                                    or false to return all available bandwidth metrics.
     * @return DataTable|DataTable\Map A table containing the requested bandwidth metric totals as integers.
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

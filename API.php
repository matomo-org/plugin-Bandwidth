<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\Bandwidth;

use Piwik\Archive;
use Piwik\DataTable;
use Piwik\DataTable\Row;
use Piwik\Period;
use Piwik\Piwik;
use Piwik\Plugin\Dimension\VisitDimension;

/**
 * @method static \Piwik\Plugins\Bandwidth\API getInstance()
 */
class API extends \Piwik\Plugin\API
{
    public function get($idSite, $period, $date, $segment = false, $columns = false)
    {
        Piwik::checkUserHasViewAccess($idSite);

        $archive = Archive::build($idSite, $period, $date, $segment);

        $columnNames = array(
            Archiver::BANDWIDTH_TOTAL_RECORD    => Metrics::COLUMN_TOTAL_OVERALL_BANDWIDTH,
            Archiver::BANDWIDTH_PAGEVIEW_RECORD => Metrics::COLUMN_TOTAL_PAGEVIEW_BANDWIDTH,
            Archiver::BANDWIDTH_DOWNLOAD_RECORD => Metrics::COLUMN_TOTAL_DOWNLOAD_BANDWIDTH,
        );

        $dataTable = $archive->getDataTableFromNumeric(array_keys($columnNames));
        $dataTable->filter('ReplaceColumnNames', array($columnNames));
        $dataTable->filter(function(DataTable $dataTable) use ($columnNames) {
            foreach ($dataTable->getRows() as $row) {
                foreach ($columnNames as $metric) {
                    $row->setColumn($metric, (int) $row->getColumn($metric));
                }
            }
        });

        $allColumns = array_values($columnNames);

        $requestedColumns = Piwik::getArrayFromApiParameter($columns);
        $columnsToShow    = $requestedColumns ?: $allColumns;
        $dataTable->queueFilter('ColumnDelete', array($columnsToRemove = array(), $columnsToShow));

        return $dataTable;
    }

}

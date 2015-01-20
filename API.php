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

        $columns     = array(Archiver::BANDWIDTH_TOTAL_RECORD);
        $columnNames = array(Archiver::BANDWIDTH_TOTAL_RECORD => Metrics::METRIC_COLUMN_TOTAL_BANDWIDTH);

        $dataTable = $archive->getDataTableFromNumeric($columns);
        $dataTable->filter('ReplaceColumnNames', array($columnNames));
        $dataTable->filter(function(DataTable $dataTable) {
            foreach ($dataTable->getRows() as $row) {
                $metric = Metrics::METRIC_COLUMN_TOTAL_BANDWIDTH;
                $row->setColumn($metric, (int) $row->getColumn($metric));
            }
        });

        $allColumns = array(Metrics::METRIC_COLUMN_TOTAL_BANDWIDTH);

        $requestedColumns = Piwik::getArrayFromApiParameter($columns);
        $columnsToShow    = $requestedColumns ?: $allColumns;
        $dataTable->queueFilter('ColumnDelete', array($columnsToRemove = array(), $columnsToShow));

        return $dataTable;
    }

}

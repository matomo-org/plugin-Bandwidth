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
 * @method static \Piwik\Plugins\API\API getInstance()
 */
class API extends \Piwik\Plugin\API
{
    public function get($idSite, $period, $date, $segment = false, $columns = false)
    {
        Piwik::checkUserHasViewAccess($idSite);

        $archive = Archive::build($idSite, $period, $date, $segment);

        $requestedColumns = Piwik::getArrayFromApiParameter($columns);

        $columns     = array(Archiver::BANDWIDTH_TOTAL_RECORD);
        $columnNames = array(Archiver::BANDWIDTH_TOTAL_RECORD => Metrics::METRIC_COLUMN_TOTAL_BANDWIDTH);

        $dataTable = $archive->getDataTableFromNumeric($columns);
        $dataTable->filter('ReplaceColumnNames', array($columnNames));

        $allColumns = array(Metrics::METRIC_COLUMN_TOTAL_BANDWIDTH);

        $columnsToShow = $requestedColumns ?: $allColumns;
        $dataTable->queueFilter('ColumnDelete', array($columnsToRemove = array(), $columnsToShow));

        return $dataTable;
    }

}

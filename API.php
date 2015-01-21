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

        $columnNames = Metrics::getNumericRecordNameToColumnsMapping();
        $archiveRecordNames = array_keys($columnNames);
        $metricColumnNames  = array_values($columnNames);

        $dataTable = $archive->getDataTableFromNumeric($archiveRecordNames);
        $dataTable->filter('ReplaceColumnNames', array($columnNames));
        $dataTable->filter(function(DataTable $dataTable) use ($metricColumnNames) {
            foreach ($dataTable->getRows() as $row) {
                foreach ($metricColumnNames as $metric) {
                    $row->setColumn($metric, (int) $row->getColumn($metric));
                }
            }
        });

        $requestedColumns = Piwik::getArrayFromApiParameter($columns);
        $columnsToShow    = $requestedColumns ?: $metricColumnNames;
        $dataTable->queueFilter('ColumnDelete', array($columnsToRemove = array(), $columnsToShow));

        return $dataTable;
    }

}

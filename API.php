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
use Piwik\Metrics;
use Piwik\Piwik;

/**
 * API for plugin Bandwidth
 *
 * @method static \Piwik\Plugins\Bandwidth\API getInstance()
 */
class API extends \Piwik\Plugin\API
{

    /**
     * Another example method that returns a data table.
     * @param int    $idSite
     * @param string $period
     * @param string $date
     * @param bool|string $segment
     * @return DataTable
     */
    public function getBandwidth($idSite, $period, $date, $segment = false, $expanded = false, $idSubtable = false)
    {
        $recordName = Archiver::BANDWIDTH_ARCHIVE_RECORD;
        return $this->getDataTable($recordName, $idSite, $period, $date, $segment, $expanded, $idSubtable);
    }

    private function getDataTable($recordName, $idSite, $period, $date, $segment, $expanded, $idSubtable)
    {
        Piwik::checkUserHasViewAccess($idSite);
        $dataTable  = Archive::getDataTableFromArchive($recordName, $idSite, $period, $date, $segment, $expanded, $idSubtable);
        $this->filterDataTable($dataTable);
        return $dataTable;
    }

    /**
     * @param DataTable $dataTable
     */
    private function filterDataTable($dataTable)
    {
        $dataTable->filter('Sort', array(Metrics::INDEX_NB_VISITS));

        $mapping = Metrics::$mappingFromIdToName;
        $mapping[Archiver::METRICS_INDEX_PAGE_MAX_BANDWIDTH] = 'max_bandwidth';
        $mapping[Archiver::METRICS_INDEX_PAGE_MIN_BANDWIDTH] = 'min_bandwidth';
        $mapping[Archiver::METRICS_INDEX_PAGE_SUM_BANDWIDTH] = 'sum_bandwidth';

        $dataTable->queueFilter('ReplaceColumnNames', array($mapping));
        $dataTable->queueFilter('ReplaceSummaryRowLabel');

        $dataTable->queueFilter(function (DataTable $dataTable) {
           foreach ($dataTable->getRows() as $row) {
               $hits = $row->getColumn('nb_hits');
               $bandwidth = $row->getColumn('sum_bandwidth');
               if (empty($hits) || empty($bandwidth)) {
                   $avg = 0;
               } else {
                   $avg = floor($bandwidth / $hits);
               }
               $row->setColumn('avg_bandwidth', $avg);
           }
        });
    }
}

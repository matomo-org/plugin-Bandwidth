<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\Bandwidth;

use Piwik\Metrics;
use Piwik\Plugins\Actions\ArchivingHelper;

/**
 * The DataArray is a data structure used to aggregate datasets,
 * ie. sum arrays made of rows made of columns,
 * data from the logs is stored in a DataArray before being converted in a DataTable
 *
 */

class DataArray extends \Piwik\DataArray
{
    public function sumMetricsBandwidth($label, $row)
    {
        if (!isset($this->data[$label])) {
            $this->data[$label] = ArchivingHelper::updateActionsRowWithRowQuery($row, 'idaction_url', $datatable);
        }
        $this->doSumBandwidthMetrics($row, $this->data[$label]);
    }

    protected static function makeEmptyContentsRow()
    {
        return array(
            Metrics::INDEX_NB_UNIQ_VISITORS            => 0,
            Metrics::INDEX_NB_VISITS                   => 0,
            Archiver::METRICS_INDEX_PAGE_SUM_BANDWIDTH => 0,
            Archiver::METRICS_INDEX_PAGE_MIN_BANDWIDTH => 0,
            Archiver::METRICS_INDEX_PAGE_MAX_BANDWIDTH => 0
        );
    }

    protected function doSumBandwidthMetrics($newRowToAdd, &$oldRowToUpdate)
    {
        $oldRowToUpdate[Metrics::INDEX_NB_VISITS] += $newRowToAdd[Metrics::INDEX_NB_VISITS];
        $oldRowToUpdate[Metrics::INDEX_NB_UNIQ_VISITORS] += $newRowToAdd[Metrics::INDEX_NB_UNIQ_VISITORS];
        $oldRowToUpdate[Archiver::METRICS_INDEX_PAGE_SUM_BANDWIDTH] += $newRowToAdd[Archiver::METRICS_INDEX_PAGE_SUM_BANDWIDTH];
        $oldRowToUpdate[Archiver::METRICS_INDEX_PAGE_MIN_BANDWIDTH] += $newRowToAdd[Archiver::METRICS_INDEX_PAGE_MIN_BANDWIDTH];
        $oldRowToUpdate[Archiver::METRICS_INDEX_PAGE_MAX_BANDWIDTH] += $newRowToAdd[Archiver::METRICS_INDEX_PAGE_MAX_BANDWIDTH];
    }


}

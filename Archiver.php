<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\Plugins\Bandwidth;
use Piwik\DataTable;
use Piwik\Metrics;
use Piwik\Plugins\Actions\ArchivingHelper;
use Piwik\Plugins\Bandwidth\Columns\Bandwidth as BandwidthColumn;
use Piwik\RankingQuery;
use Piwik\Tracker\Action;
use Piwik\Tracker\PageUrl;


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
    const BANDWIDTH_ARCHIVE_RECORD = "Bandwidth_archive_record";

    const METRICS_INDEX_PAGE_SUM_BANDWIDTH = 90;
    const METRICS_INDEX_PAGE_MIN_BANDWIDTH = 91;
    const METRICS_INDEX_PAGE_MAX_BANDWIDTH = 92;

    /**
     * @var DataArray
     */
    private $dataArray;

    /**
     * @var DataTable[]
     */
    private $actionsTablesByType;

    public function aggregateMultipleReports()
    {
        $this->getProcessor()->aggregateDataTableRecords(self::BANDWIDTH_ARCHIVE_RECORD);
    }

    public function aggregateDayReport()
    {
        ArchivingHelper::clearActionsCache();
        $this->initActionsTables();

        $rankingQueryLimit = ArchivingHelper::getRankingQueryLimit();
        ArchivingHelper::reloadConfig();

        $this->archiveDayActions($rankingQueryLimit);

        $this->insertPageUrlsReports();

        return true;
    }

    /**
     * Initializes the DataTables created by the archiveDay function.
     */
    private function initActionsTables()
    {
        $this->actionsTablesByType = array();
        foreach (array(Action::TYPE_PAGE_URL) as $type) {
            $dataTable = new DataTable();
            $dataTable->setMaximumAllowedRows(ArchivingHelper::$maximumRowsInDataTableLevelZero);
            $this->actionsTablesByType[$type] = $dataTable;
        }
    }

    protected function archiveDayActions($rankingQueryLimit)
    {
        $column = new BandwidthColumn();
        $column = $column->getColumnName();

        $select = "log_action.name,
				log_action.type,
				log_action.idaction,
				log_action.url_prefix,
				count(distinct log_link_visit_action.idvisit) as `" . Metrics::INDEX_NB_VISITS . "`,
				count(distinct log_link_visit_action.idvisitor) as `" . Metrics::INDEX_NB_UNIQ_VISITORS . "`,
				count(*) as `" . Metrics::INDEX_PAGE_NB_HITS . "`,
				sum(
					case when " . $column . " is null
						then 0
						else " . $column . "
					end
				) as `" . self::METRICS_INDEX_PAGE_SUM_BANDWIDTH . "`,
				min(" . $column . ")
				    as `" . self::METRICS_INDEX_PAGE_MIN_BANDWIDTH . "`,
				max(" . $column . ")
                    as `" . self::METRICS_INDEX_PAGE_MAX_BANDWIDTH . "`
				";

        $from = array(
            "log_link_visit_action",
            array(
                "table"  => "log_action",
                "joinOn" => "log_link_visit_action.idaction_url = log_action.idaction" // %s
            )
        );

        $where = "log_link_visit_action.server_time >= ?
				AND log_link_visit_action.server_time <= ?
				AND log_link_visit_action.idsite = ?
				AND log_link_visit_action.$column > 0"
            . \Piwik\Plugins\Actions\Archiver::getWhereClauseActionIsNotEvent();

        $groupBy = "log_action.idaction";
        $orderBy = "`" . Metrics::INDEX_NB_VISITS . "` DESC, name ASC";

        $rankingQuery = false;
        if ($rankingQueryLimit > 0) {
            $rankingQuery = new RankingQuery($rankingQueryLimit);
            $rankingQuery->setOthersLabel(DataTable::LABEL_SUMMARY_ROW);
            $rankingQuery->addLabelColumn(array('idaction', 'name'));
            $rankingQuery->addColumn(array('url_prefix', Metrics::INDEX_NB_UNIQ_VISITORS));
            $rankingQuery->addColumn(array(Metrics::INDEX_PAGE_NB_HITS, Metrics::INDEX_NB_VISITS), 'sum');
            $rankingQuery->addColumn(self::METRICS_INDEX_PAGE_SUM_BANDWIDTH, 'sum');
            $rankingQuery->addColumn(self::METRICS_INDEX_PAGE_MIN_BANDWIDTH, 'min');
            $rankingQuery->addColumn(self::METRICS_INDEX_PAGE_MAX_BANDWIDTH, 'max');
            $rankingQuery->partitionResultIntoMultipleGroups('type', array_keys($this->actionsTablesByType));
        }

        $resultSet = $this->archiveDayQueryProcess($select, $from, $where, $groupBy, $orderBy, $rankingQuery);
        while ($row = $resultSet->fetch()) {
            $tableRow = ArchivingHelper::getActionRow($row['name'], $row['type'], $row['url_prefix'], $this->actionsTablesByType);
            $this->aggregateBandwidthRow($tableRow, $row);
        }
    }

    private function aggregateBandwidthRow(DataTable\Row $tableRow, $row)
    {
        /*
        $label = $tableRow->getColumn('label');

        $table = $this->getDataTable(Action::TYPE_PAGE_URL);
*/
        foreach (array(
                     Metrics::INDEX_NB_UNIQ_VISITORS            => 0,
                     Metrics::INDEX_NB_VISITS                   => 0,
                     self::METRICS_INDEX_PAGE_SUM_BANDWIDTH => 0,
                     self::METRICS_INDEX_PAGE_MIN_BANDWIDTH => 0,
                     self::METRICS_INDEX_PAGE_MAX_BANDWIDTH => 0
                 ) as $key => $val) {
            if (!$tableRow->hasColumn($key)) {
                $tableRow->setColumn($key, $val);
            }
        }

        $tableRow->deleteColumn(Metrics::INDEX_PAGE_SUM_TIME_SPENT);

        $alreadyValue = $tableRow->getColumn(self::METRICS_INDEX_PAGE_MIN_BANDWIDTH);
        $value  = $row[self::METRICS_INDEX_PAGE_MIN_BANDWIDTH];
        if (empty($alreadyValue)) {
            $newValue = $value;
        } else if (empty($value)) {
            $newValue = $alreadyValue;
        } else {
            $newValue = min($alreadyValue, $value);
        }
        $tableRow->setColumn(self::METRICS_INDEX_PAGE_MIN_BANDWIDTH, $newValue);

        $alreadyValue = $tableRow->getColumn(self::METRICS_INDEX_PAGE_MAX_BANDWIDTH);
        $value  = $row[self::METRICS_INDEX_PAGE_MAX_BANDWIDTH];
        $newValue = max((int)$alreadyValue, (int)$value);
        $tableRow->setColumn(self::METRICS_INDEX_PAGE_MAX_BANDWIDTH, $newValue);

        $sumMetrics = array(self::METRICS_INDEX_PAGE_SUM_BANDWIDTH, Metrics::INDEX_PAGE_NB_HITS, Metrics::INDEX_NB_UNIQ_VISITORS, Metrics::INDEX_NB_VISITS);
        foreach ($sumMetrics as $metric) {
            $val = (int) $tableRow->getColumn($metric) + (int) $row[$metric];
            $tableRow->setColumn($metric, $val);
        }

       // $oldRowToUpdate[Metrics::INDEX_NB_VISITS] += $newRowToAdd[Metrics::INDEX_NB_VISITS];
       // $oldRowToUpdate[Metrics::INDEX_NB_UNIQ_VISITORS] += $newRowToAdd[Metrics::INDEX_NB_UNIQ_VISITORS];
     //   $oldRowToUpdate[Archiver::METRICS_INDEX_PAGE_SUM_BANDWIDTH] += $newRowToAdd[Archiver::METRICS_INDEX_PAGE_SUM_BANDWIDTH];
      //  $oldRowToUpdate[Archiver::METRICS_INDEX_PAGE_MIN_BANDWIDTH] += $newRowToAdd[Archiver::METRICS_INDEX_PAGE_MIN_BANDWIDTH];
      //  $oldRowToUpdate[Archiver::METRICS_INDEX_PAGE_MAX_BANDWIDTH] += $newRowToAdd[Archiver::METRICS_INDEX_PAGE_MAX_BANDWIDTH];
/*
    //    var_dump($row);
        $dataArray = $this->getDataArray();

        $dataArray->sumMetricsBandwidth($row['name'], $row);*/
    }

    /**
     * @return DataArray
     */
    private function getDataArray()
    {
        if (empty($this->dataArray)) {
            $this->dataArray = new DataArray();
        }

        return $this->dataArray;
    }

    private function archiveDayQueryProcess($select, $from, $where, $groupBy, $orderBy, RankingQuery $rankingQuery)
    {
        // get query with segmentation
        $query = $this->getLogAggregator()->generateQuery($select, $from, $where, $groupBy, $orderBy);

        // apply ranking query
        if ($rankingQuery) {
            $query['sql'] = $rankingQuery->generateRankingQuery($query['sql']);
        }

        // get result
        $resultSet = $this->getLogAggregator()->getDb()->query($query['sql'], $query['bind']);

        if ($resultSet === false) {
            return;
        }

        return $resultSet;
    }

    protected function insertPageUrlsReports()
    {
        $dataTable = $this->getDataTable(Action::TYPE_PAGE_URL);
        ArchivingHelper::deleteInvalidSummedColumnsFromDataTable($dataTable);
        $report = $dataTable->getSerialized(ArchivingHelper::$maximumRowsInDataTableLevelZero, ArchivingHelper::$maximumRowsInSubDataTable, ArchivingHelper::$columnToSortByBeforeTruncation);
        $this->getProcessor()->insertBlobRecord(self::BANDWIDTH_ARCHIVE_RECORD, $report);
    }

    /**
     * @param $typeId
     * @return DataTable
     */
    private function getDataTable($typeId)
    {
        return $this->actionsTablesByType[$typeId];
    }

    private function insertDayReports()
    {
        $dataTable = $this->getDataArray()->asDataTable();
        $blob = $dataTable->getSerialized(
            ArchivingHelper::$maximumRowsInDataTableLevelZero,
            ArchivingHelper::$maximumRowsInSubDataTable,
            Metrics::INDEX_NB_VISITS
        );
        $this->getProcessor()->insertBlobRecord(self::BANDWIDTH_ARCHIVE_RECORD, $blob);
    }

}

<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\Plugins\Bandwidth\RecordBuilders;

use Piwik\ArchiveProcessor\RecordBuilder;
use Piwik\DataAccess\LogAggregator;
use Piwik\Plugins\Bandwidth\Columns\Bandwidth as BandwidthColumn;

abstract class Base extends RecordBuilder
{
    protected function queryActionsByDimension(LogAggregator $logAggregator, $extraWhere = null, $joinLogActionOnColumn = false)
    {
        $column = new BandwidthColumn();
        $column = $column->getColumnName();
        $table  = 'log_link_visit_action';
        $field  = 'sum_bandwidth';
        $where  = "$table.$column is not null";

        $selects = [
            "sum($table.$column) as `$field`",
        ];

        if (!empty($extraWhere)) {
            $where = "$where AND $extraWhere";
        }

        return $logAggregator->queryActionsByDimension([$column], $where, $selects, false, null, $joinLogActionOnColumn);
    }

    /**
     * @param \Zend_Db_Statement $query
     * @param string             $field
     */
    protected function sumNumericRecord($query, string $field): int
    {
        $total = 0;

        while ($row = $query->fetch()) {
            if (!empty($row[$field])) {
                $total += $row[$field];
            }
        }

        return (int)$total;
    }
}

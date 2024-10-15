<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\Plugins\Bandwidth\RecordBuilders;

use Piwik\ArchiveProcessor;
use Piwik\Plugins\Bandwidth\Archiver;

class TotalOverall extends Base
{
    public function getRecordMetadata(ArchiveProcessor $archiveProcessor): array
    {
        return [
            ArchiveProcessor\Record::make(ArchiveProcessor\Record::TYPE_NUMERIC, Archiver::BANDWIDTH_TOTAL_RECORD),
        ];
    }

    protected function aggregate(ArchiveProcessor $archiveProcessor): array
    {
        $query = $this->queryActionsByDimension($archiveProcessor->getLogAggregator());
        $record = $this->sumNumericRecord($query, 'sum_bandwidth');
        return [Archiver::BANDWIDTH_TOTAL_RECORD => $record];
    }
}

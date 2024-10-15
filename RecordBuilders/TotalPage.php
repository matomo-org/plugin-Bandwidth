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
use Piwik\Tracker\Action;

class TotalPage extends Base
{
    public function getRecordMetadata(ArchiveProcessor $archiveProcessor): array
    {
        return [
            ArchiveProcessor\Record::make(ArchiveProcessor\Record::TYPE_NUMERIC, Archiver::BANDWIDTH_PAGEVIEW_RECORD),
        ];
    }

    protected function aggregate(ArchiveProcessor $archiveProcessor): array
    {
        $joinLogActionOnColumn = ['idaction_url'];
        $query = $this->queryActionsByDimension(
            $archiveProcessor->getLogAggregator(),
            'log_action1.type = ' . Action::TYPE_PAGE_URL,
            $joinLogActionOnColumn
        );
        $record = $this->sumNumericRecord($query, 'sum_bandwidth');
        return [Archiver::BANDWIDTH_PAGEVIEW_RECORD => $record];
    }
}

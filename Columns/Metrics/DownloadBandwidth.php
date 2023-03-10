<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\Bandwidth\Columns\Metrics;

use Piwik\DataTable\Row;
use Piwik\Metrics\Formatter;
use Piwik\Piwik;
use Piwik\Plugin\Metric;
use Piwik\Plugin\ProcessedMetric;

/**
 * The total amount bandwidth used for downloads.
 *
 * Note: This Metric should actually inherit from AggregatedMetric class, but we need to use ProcessedMetric as core
 * won't format the values automatically otherwise
 */
class DownloadBandwidth extends ProcessedMetric
{
    public function getName()
    {
        return 'nb_total_download_bandwidth';
    }

    public function getDependentMetrics()
    {
        return [
            $this->getName()
        ];
    }

    public function compute(Row $row)
    {
        return $row->getColumn($this->getName());
    }

    public function getTranslatedName()
    {
        return Piwik::translate('Bandwidth_ColumnTotalDownloadBandwidth');
    }

    public function format($value, Formatter $formatter)
    {
        if ($value) {
            $value = $formatter->getPrettySizeFromBytes($value, null, 2);
        }

        return $value;
    }

    public function getSemanticType()
    {
        return Metric::SEMANTIC_TYPE_NUMBER;
    }
}

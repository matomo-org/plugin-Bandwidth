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
use Piwik\Plugin\ProcessedMetric;

abstract class Base extends ProcessedMetric
{
    protected $metric;

    public function compute(Row $row)
    {
        if ($this->metric) {
            return $this->getMetricAsIntSafe($row, $this->metric);
        }
    }

    public function getTemporaryMetrics()
    {
        if ($this->metric) {
            return [$this->metric];
        }

        return [];
    }

    public function getMetricAsIntSafe(Row $row, $metric)
    {
        $value = $this->getMetric($row, $metric);

        if (false !== $value) {
            if (strlen((string)$value) >= 9 && is_numeric($value)) {
                return $value;
            } else {
                $value = (int)$value;
            }
        }

        return $value;
    }

    public function format($value, Formatter $formatter)
    {
        if ($value) {
            $value = $formatter->getPrettySizeFromBytes($value, null, 2);
        }

        return $value;
    }

    public function getDependentMetrics()
    {
        return [];
    }

    public function getMetricId()
    {
        return $this->metric;
    }
}
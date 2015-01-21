<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Plugins\Bandwidth\Columns\Metrics;

use Piwik\DataTable\Row;
use Piwik\Metrics\Formatter;
use Piwik\Piwik;
use Piwik\Plugins\Bandwidth\Metrics;

/**
 * The sum amount bandwidth of a pages.
 */
class SumBandwidth extends Base
{
    protected $metric = Metrics::METRICS_PAGE_SUM_BANDWIDTH;

    public function getName()
    {
        return 'sum_bandwidth';
    }

    public function getTranslatedName()
    {
        return Piwik::translate('Bandwidth_ColumnSumBandwidth');
    }

}
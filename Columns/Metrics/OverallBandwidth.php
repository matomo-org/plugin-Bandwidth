<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\Bandwidth\Columns\Metrics;

use Piwik\Piwik;
use Piwik\Plugins\Bandwidth\Metrics;

/**
 * The max amount bandwidth of a pages.
 */
class OverallBandwidth extends Base
{
    protected $metric = Metrics::METRICS_PAGE_MAX_BANDWIDTH;

    public function getName()
    {
        return 'nb_total_overall_bandwidth';
    }

    public function getTranslatedName()
    {
        return Piwik::translate('Bandwidth_ColumnTotalOverallBandwidth');
    }
}
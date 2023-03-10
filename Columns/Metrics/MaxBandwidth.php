<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\Bandwidth\Columns\Metrics;

use Piwik\Piwik;
use Piwik\Plugin\Metric;
use Piwik\Plugins\Bandwidth\Metrics;

/**
 * The max amount bandwidth of a pages.
 */
class MaxBandwidth extends Base
{
    protected $metric = Metrics::METRICS_PAGE_MAX_BANDWIDTH;

    public function getName()
    {
        return 'max_bandwidth';
    }

    public function getTranslatedName()
    {
        return Piwik::translate('Bandwidth_ColumnMaxBandwidth');
    }

    public function getSemanticType()
    {
        return Metric::SEMANTIC_TYPE_NUMBER;
    }

}
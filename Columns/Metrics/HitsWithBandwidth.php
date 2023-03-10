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
 * The amount of a pages that were tracked having a bandwidth.
 */
class HitsWithBandwidth extends Base
{
    protected $metric = Metrics::METRICS_NB_HITS_WITH_BANDWIDTH;

    public function getName()
    {
        return 'nb_hits_with_bandwidth';
    }

    public function getTranslatedName()
    {
        return Piwik::translate('Bandwidth_ColumnHitsWithBandwidth');
    }

    public function getSemanticType()
    {
        return Metric::SEMANTIC_TYPE_NUMBER;
    }
}
<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\Bandwidth\Columns\Metrics;

use Piwik\Piwik;
use Piwik\Columns\Dimension;
use Piwik\Plugins\Bandwidth\Metrics;

/**
 * The min amount bandwidth of a pages.
 */
class MinBandwidth extends Base
{
    protected $metric = Metrics::METRICS_PAGE_MIN_BANDWIDTH;

    public function getName()
    {
        return 'min_bandwidth';
    }

    public function getTranslatedName()
    {
        return Piwik::translate('Bandwidth_ColumnMinBandwidth');
    }

    public function getSemanticType(): ?string
    {
        return Dimension::TYPE_NUMBER;
    }

}
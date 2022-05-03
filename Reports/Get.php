<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\Plugins\Bandwidth\Reports;

use Piwik\Piwik;
use Piwik\Plugins\Bandwidth\Metrics;

/**
 * This class defines a new report.
 *
 * See {@link http://developer.piwik.org/api-reference/Piwik/Plugin/Report} for more information.
 */
class Get extends Base
{
    protected function init()
    {
        parent::init();

        $this->name = Piwik::translate('Bandwidth_Bandwidth') . ' - ' . Piwik::translate('General_MainMetrics');
        $this->order = 30;
        $this->metrics = array_values(Metrics::getNumericRecordNameToColumnsMapping());
        // Currently needed to get the metrics formatted
        $this->processedMetrics = Metrics::getOverallMetrics();
    }
}

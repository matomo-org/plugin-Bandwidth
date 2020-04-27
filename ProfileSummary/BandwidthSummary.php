<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\Plugins\Bandwidth\ProfileSummary;

use Piwik\Piwik;
use Piwik\Plugins\Live\ProfileSummary\ProfileSummaryAbstract;
use Piwik\View;

/**
 * Class BandwidthSummary
 */
class BandwidthSummary extends ProfileSummaryAbstract
{
    /**
     * @inheritdoc
     */
    public function getName()
    {
        return Piwik::translate('Bandwidth_Bandwidth');
    }

    /**
     * @inheritdoc
     */
    public function render()
    {
        if (!isset($this->profile['avgBandwidth'])) {
            return '';
        }

        $view              = new View('@Bandwidth/_profileSummary.twig');
        $view->visitorData = $this->profile;
        return $view->render();
    }

    /**
     * @inheritdoc
     */
    public function getOrder()
    {
        return 70;
    }
}
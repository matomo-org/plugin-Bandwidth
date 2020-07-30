<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\Plugins\Bandwidth;

use Piwik\Metrics\Formatter;
use Piwik\Plugins\Live\VisitorDetailsAbstract;
use Piwik\View;

class VisitorDetails extends VisitorDetailsAbstract
{
    public function extendActionDetails(&$action, $nextAction, $visitorDetails)
    {
        if (array_key_exists('bandwidth', $action)) {
            $formatter                  = new Formatter();
            $action['bandwidth_pretty'] = $formatter->getPrettySizeFromBytes($action['bandwidth']);
        }
    }

    public function renderActionTooltip($action, $visitInfo)
    {
        if (empty($action['bandwidth'])) {
            return [];
        }

        $view         = new View('@Bandwidth/_actionTooltip');
        $view->action = $action;
        return [[60, $view->render()]];
    }

    protected $sumBandwidth = 0;
    protected $actionsWithBandwidth = 0;
    protected $maxBandwidth = null;
    protected $minBandwidth = null;

    public function initProfile($visits, &$profile)
    {
        $this->sumBandwidth         = 0;
        $this->actionsWithBandwidth = 0;
    }

    public function handleProfileAction($action, &$profile)
    {
        if (!empty($action['bandwidth'])) {
            $this->sumBandwidth += $action['bandwidth'];
            if (is_null($this->minBandwidth)) {
                $this->minBandwidth = $action['bandwidth'];
            }
            $this->minBandwidth = min($this->minBandwidth, $action['bandwidth']);
            if (is_null($this->minBandwidth)) {
                $this->minBandwidth = $action['bandwidth'];
            }
            $this->maxBandwidth = max($this->maxBandwidth, $action['bandwidth']);
            $this->actionsWithBandwidth++;
        }
    }

    public function finalizeProfile($visits, &$profile)
    {
        if ($this->actionsWithBandwidth) {
            $formatter               = new Formatter();
            $profile['sumBandwidth'] = $formatter->getPrettySizeFromBytes($this->sumBandwidth);
            $profile['minBandwidth'] = $formatter->getPrettySizeFromBytes($this->minBandwidth);
            $profile['maxBandwidth'] = $formatter->getPrettySizeFromBytes($this->maxBandwidth);
            $profile['avgBandwidth'] = $formatter->getPrettySizeFromBytes(round($this->sumBandwidth / $this->actionsWithBandwidth));
        }
    }
}
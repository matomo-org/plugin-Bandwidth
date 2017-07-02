<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link    http://piwik.org
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
            $formatter = new Formatter();
            $action['bandwidth_pretty'] = $formatter->getPrettySizeFromBytes($action['bandwidth']);
        }
    }

    public function renderActionTooltip($action, $visitInfo)
    {
        if (!empty($action['bandwidth'])) {
            $view = new View('@Bandwidth/_actionTooltip');
            $view->action = $action;
            return $view->render();
        }

        return '';
    }
}
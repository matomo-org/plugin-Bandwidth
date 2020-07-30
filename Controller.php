<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\Plugins\Bandwidth;

use Piwik\Common;
use Piwik\Piwik;

/**
 *
 */
class Controller extends \Piwik\Plugin\Controller
{

    public function getEvolutionGraph()
    {
        $columns = Common::getRequestVar('columns');
        $columns = Piwik::getArrayFromApiParameter($columns);

        $view = $this->getLastUnitGraphAcrossPlugins($this->pluginName, __FUNCTION__, $columns, [], '');

        return $this->renderView($view);
    }


}

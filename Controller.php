<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\Bandwidth;

use Piwik\Common;
use Piwik\DataTable\Row;
use Piwik\Metrics\Formatter;
use Piwik\Piwik;
use Piwik\View;
use Piwik\API\Request;

/**
 * A controller let's you for example create a page that can be added to a menu. For more information read our guide
 * http://developer.piwik.org/guides/mvc-in-piwik or have a look at the our API references for controller and view:
 * http://developer.piwik.org/api-reference/Piwik/Plugin/Controller and
 * http://developer.piwik.org/api-reference/Piwik/View
 */
class Controller extends \Piwik\Plugin\Controller
{

    public function sparklines()
    {
        $sparklineParams = array('columns' => array(Metrics::COLUMN_TOTAL_OVERALL_BANDWIDTH));
        $sparklineUrl    = $this->getUrlSparkline('getEvolutionGraph', $sparklineParams);

        $mainMetricsRow   = $this->getBandwidthMainMetricsRow();
        $nbTotalBandwidth = $this->getFormattedTotalBandwidth($mainMetricsRow);

        return $this->renderTemplate('sparklines', array(
            'urlSparklineBandwidth' => $sparklineUrl,
            'nbTotalBandwidth' => $nbTotalBandwidth
        ));
    }

    public function getEvolutionGraph()
    {
        $columns = Common::getRequestVar('columns');
        $columns = Piwik::getArrayFromApiParameter($columns);

        $view = $this->getLastUnitGraphAcrossPlugins($this->pluginName, __FUNCTION__, $columns, array(), '');

        return $this->renderView($view);
    }

    private function getFormattedTotalBandwidth(Row $row)
    {
        $nbTotalBandwidth = (int) $row->getColumn(Metrics::COLUMN_TOTAL_OVERALL_BANDWIDTH);
        $formatter = new Formatter();
        $nbTotalBandwidth = $formatter->getPrettySizeFromBytes($nbTotalBandwidth, null, 2);
        return $nbTotalBandwidth;
    }

    private function getBandwidthMainMetricsRow()
    {
        $idSite  = Common::getRequestVar('idSite');
        $period  = Common::getRequestVar('period');
        $date    = Common::getRequestVar('date');
        $segment = Request::getRawSegmentFromRequest();

        $dataTableActions = Request::processRequest('Bandwidth.get', array(
            'idSite'  => $idSite,
            'period'  => $period,
            'date'    => $date,
            'segment' => $segment
        ));

        if ($dataTableActions->getRowsCount() === 0) {
            $row = new Row();
        } else {
            $row = $dataTableActions->getFirstRow();
        }

        return $row;
    }
}

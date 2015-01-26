<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\Bandwidth\Columns;

use Piwik\Common;
use Piwik\Period\Range;
use Piwik\Piwik;
use Piwik\Plugin\Dimension\ActionDimension;
use Piwik\Plugin\Segment;
use Piwik\Plugins\Bandwidth\API as BandwidthApi;
use Piwik\Tracker\Request;
use Piwik\Tracker\Visitor;
use Piwik\Tracker\Action;

/**
 * This example dimension recognizes a new tracking url parameter that is supposed to save the keywords that were used
 * on a certain page. Please note that dimension instances are usually cached during one tracking request so they
 * should be stateless (meaning an instance of this dimension will be reused if requested multiple times).
 *
 * See {@link http://developer.piwik.org/api-reference/Piwik/Plugin\Dimension\ActionDimension} for more information.
 */
class Bandwidth extends ActionDimension
{
    /**
     * This will be the name of the column in the log_link_visit_action table if a $columnType is specified.
     * @var string
     */
    protected $columnName = 'bandwidth';

    /**
     * If a columnType is defined, we will create this a column in the MySQL table having this type. Please make sure
     * MySQL will understand this type. Once you change the column type the Piwik platform will notify the user to
     * perform an update which can sometimes take a long time so be careful when choosing the correct column type.
     * @var string
     */
    protected $columnType = 'BIGINT(15) UNSIGNED DEFAULT NULL';

    /**
     * The name of the dimension which will be visible for instance in the UI of a related report and in the mobile app.
     * @return string
     */
    public function getName()
    {
        return Piwik::translate('Bandwidth_Bandwidth');
    }

    /**
     * By defining one or multiple segments a user will be able to filter their visitors by this column. For instance
     * show all actions only considering users having more than 10 achievement points. If you do not want to define a
     * segment for this dimension just remove the column.
     */
    protected function configureSegments()
    {
        $segment = new Segment();
        $segment->setSegment('bandwidth');
        $segment->setType(Segment::TYPE_METRIC);
        $segment->setCategory('General_Actions');
        $segment->setName('Bandwidth_Bandwidth');
        $segment->setAcceptedValues('Any number in bytes, eg. 1000');
        $this->addSegment($segment);
    }

    /**
     * This event is triggered before a new action is logged to the log_link_visit_action table. It overwrites any
     * looked up action so it makes usually no sense to implement both methods but it sometimes does. You can assign
     * any value to the column or return boolan false in case you do not want to save any value.
     *
     * @param Request $request
     * @param Visitor $visitor
     * @param Action $action
     *
     * @return mixed|false
     */
    public function onNewAction(Request $request, Visitor $visitor, Action $action)
    {
        $value = Common::getRequestVar('bw_bytes', false, 'string', $request->getParams());

        if (is_numeric($value)) {
            return (int) $value;
        }

        return false;
    }

    public function isUsedInSite($idSite, $period, $date, $columnToCompare)
    {
        if ($period === 'day' || $period === 'week') {
            $period = 'month';
        }

        if (Range::isMultiplePeriod($date, $period)) {
            $period = 'range';
        }

        $result = BandwidthApi::getInstance()->get($idSite, $period, $date);

        if (!$result->getRowsCount()) {
            return false;
        }

        $value = $result->getFirstRow()->getColumn($columnToCompare);

        return !empty($value);
    }
}
<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\Plugins\Bandwidth;

/**
 *
 */
class Archiver extends \Piwik\Plugin\Archiver
{
    const BANDWIDTH_TOTAL_RECORD = "Bandwidth_nb_total_overall";
    const BANDWIDTH_PAGEVIEW_RECORD = "Bandwidth_nb_total_pageurl";
    const BANDWIDTH_DOWNLOAD_RECORD = "Bandwidth_nb_total_download";
}

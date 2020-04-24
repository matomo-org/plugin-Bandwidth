/*!
 * Matomo - free/libre analytics platform
 *
 * Screenshot integration tests.
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

describe("Bandwidth", function () {
    this.timeout(0);

    this.fixture = "Piwik\\Plugins\\Bandwidth\\tests\\Fixtures\\OneVisitWithBandwidth";

    var generalParams = 'idSite=1&period=day&date=2010-01-03',
        secondDateParams = 'idSite=1&period=day&date=2010-01-05',
        thirdDateParams = 'idSite=1&period=day&date=2010-02-05',
        urlBase = 'module=CoreHome&action=index&' + generalParams;

    before(function () {
        testEnvironment.pluginsToLoad = ['Bandwidth'];
        testEnvironment.save();

    });

    it('should load the actions > pages page correctly', async function () {
        await page.goto("?" + urlBase + "#?" + generalParams + "&category=General_Actions&subcategory=General_Pages&flat=0");
        var elem = await page.jQuery('.pageWrap');
        expect(await elem.screenshot()).to.matchImage('actions_page_urls');
    });

    it('should load the actions > pages page flat correctly', async function () {
        await page.goto('about:blank');
        await page.goto("?" + urlBase + "#?" + generalParams + "&category=General_Actions&subcategory=General_Pages&flat=1");
        var elem = await page.jQuery('.pageWrap');
        expect(await elem.screenshot()).to.matchImage('actions_page_urls_flat');
    });

    it('should load the actions > pages titles correctly', async function () {
        await page.goto("?" + urlBase + "#?" + generalParams + "&category=General_Actions&subcategory=Actions_SubmenuPageTitles");
        var elem = await page.jQuery('.pageWrap');
        expect(await elem.screenshot()).to.matchImage('actions_page_titles');
    });

    it('should load the actions > downloads correctly', async function () {
        await page.goto("?" + urlBase + "#?" + generalParams + "&category=General_Actions&subcategory=General_Downloads");
        var elem = await page.jQuery('.pageWrap');
        expect(await elem.screenshot()).to.matchImage('actions_downloads');
    });

    it('should load the actions > downloads flat correctly', async function () {
        await page.goto('about:blank');
        await page.goto("?" + urlBase + "#?" + generalParams + "&category=General_Actions&subcategory=General_Downloads&flat=1");
        var elem = await page.jQuery('.pageWrap');
        expect(await elem.screenshot()).to.matchImage('actions_downloads_flat');
    });

    it('should load the visitors > overview correctly', async function () {
        await page.goto("?" + urlBase + "#?" + generalParams + "&category=General_Visitors&subcategory=General_Overview&columns=nb_total_overall_bandwidth,nb_total_pageview_bandwidth,nb_total_download_bandwidth");
        var elem = await page.jQuery('.pageWrap');
        expect(await elem.screenshot()).to.matchImage('visitors_overview');
    });

    it('should show bandwidth columns if no byte was tracked on that day but during the month', async function () {
        await page.goto("?" + urlBase + "#?" + secondDateParams + "&category=General_Actions&subcategory=General_Pages&flat=1");
        var elem = await page.jQuery('.pageWrap');
        expect(await elem.screenshot()).to.matchImage('actions_no_bandwidth_on_day_but_in_month');
    });

    it('should not show bandwidth columns if no byte was tracked in actions > pages page', async function () {
        await page.goto("?" + urlBase + "#?" + thirdDateParams + "&category=General_Actions&subcategory=General_Pages&flat=1");
        var elem = await page.jQuery('.pageWrap');
        expect(await elem.screenshot()).to.matchImage('actions_page_urls_no_bandwidth');
    });

    it('should not show bandwidth columns if no byte was tracked in actions > pages titles', async function () {
        await page.goto("?" + urlBase + "#?" + thirdDateParams + "&category=General_Actions&subcategory=Actions_SubmenuPageTitles");
        var elem = await page.jQuery('.pageWrap');
        expect(await elem.screenshot()).to.matchImage('actions_page_titles_no_bandwidth');
    });

    it('should not show bandwidth columns if no byte was tracked in actions > downloads', async function () {
        await page.goto("?" + urlBase + "#?" + thirdDateParams + "&category=General_Actions&subcategory=General_Downloads");
        var elem = await page.jQuery('.pageWrap');
        expect(await elem.screenshot()).to.matchImage('actions_downloads_no_bandwidth');
    });
});
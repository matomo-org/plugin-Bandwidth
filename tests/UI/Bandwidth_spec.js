/*!
 * Piwik - free/libre analytics platform
 *
 * Screenshot integration tests.
 *
 * @link http://piwik.org
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

    it('should load the actions > pages page correctly', function (done) {
        expect.screenshot('actions_page_urls').to.be.captureSelector('.pageWrap', function (page) {
            page.load("?" + urlBase + "#?" + generalParams + "&category=General_Actions&subcategory=General_Pages");
        }, done);
    });

    it('should load the actions > pages page flat correctly', function (done) {
        expect.screenshot('actions_page_urls_flat').to.be.captureSelector('.pageWrap', function (page) {
            page.load("?" + urlBase + "#?" + generalParams + "&category=General_Actions&subcategory=General_Pages&flat=1");
        }, done);
    });

    it('should load the actions > pages titles correctly', function (done) {
        expect.screenshot('actions_page_titles').to.be.captureSelector('.pageWrap', function (page) {
            page.load("?" + urlBase + "#?" + generalParams + "&category=General_Actions&subcategory=Actions_SubmenuPageTitles");
        }, done);
    });

    it('should load the actions > downloads correctly', function (done) {
        expect.screenshot('actions_downloads').to.be.captureSelector('.pageWrap', function (page) {
            page.load("?" + urlBase + "#?" + generalParams + "&category=General_Actions&subcategory=General_Downloads");
        }, done);
    });

    it('should load the actions > downloads flat correctly', function (done) {
        expect.screenshot('actions_downloads_flat').to.be.captureSelector('.pageWrap', function (page) {
            page.load("?" + urlBase + "#?" + generalParams + "&category=General_Actions&subcategory=General_Downloads&flat=1");
        }, done);
    });

    it('should load the visitors > overview correctly', function (done) {
        expect.screenshot('visitors_overview').to.be.captureSelector('.pageWrap', function (page) {
            page.load("?" + urlBase + "#?" + generalParams + "&category=General_Visitors&subcategory=General_Overview&columns=nb_total_overall_bandwidth,nb_total_pageview_bandwidth,nb_total_download_bandwidth");
        }, done);
    });

    it('should show bandwidth columns if no byte was tracked on that day but during the month', function (done) {
        expect.screenshot('actions_no_bandwidth_on_day_but_in_month').to.be.captureSelector('.pageWrap', function (page) {
            page.load("?" + urlBase + "#?" + secondDateParams + "&category=General_Actions&subcategory=General_Pages&flat=1");
        }, done);
    });

    it('should not show bandwidth columns if no byte was tracked in actions > pages page', function (done) {
        expect.screenshot('actions_page_urls_no_bandwidth').to.be.captureSelector('.pageWrap', function (page) {
            page.load("?" + urlBase + "#?" + thirdDateParams + "&category=General_Actions&subcategory=General_Pages&flat=1");
        }, done);
    });

    it('should not show bandwidth columns if no byte was tracked in actions > pages titles', function (done) {
        expect.screenshot('actions_page_titles_no_bandwidth').to.be.captureSelector('.pageWrap', function (page) {
            page.load("?" + urlBase + "#?" + thirdDateParams + "&category=General_Actions&subcategory=Actions_SubmenuPageTitles");
        }, done);
    });

    it('should not show bandwidth columns if no byte was tracked in actions > downloads', function (done) {
        expect.screenshot('actions_downloads_no_bandwidth').to.be.captureSelector('.pageWrap', function (page) {
            page.load("?" + urlBase + "#?" + thirdDateParams + "&category=General_Actions&subcategory=General_Downloads");
        }, done);
    });
});
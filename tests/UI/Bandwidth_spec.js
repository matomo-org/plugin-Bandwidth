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

    var generalParams = 'idSite=1&period=year&date=2010-01-03',
        urlBase = 'module=CoreHome&action=index&' + generalParams;

    before(function () {

        testEnvironment.pluginsToLoad = ['Bandwidth'];
        testEnvironment.save();

    });

    it('should load the actions > pages page correctly', function (done) {
        expect.screenshot('actions_page_urls').to.be.captureSelector('.pageWrap,.expandDataTableFooterDrawer', function (page) {
            page.load("?" + urlBase + "#" + generalParams + "&module=Actions&action=menuGetPageUrls");
        }, done);
    });

    it('should load the actions > pages page flat correctly', function (done) {
        expect.screenshot('actions_page_urls_flat').to.be.captureSelector('.pageWrap,.expandDataTableFooterDrawer', function (page) {
            page.load("?" + urlBase + "#" + generalParams + "&module=Actions&action=menuGetPageUrls&flat=1");
        }, done);
    });

    it('should load the actions > pages titles correctly', function (done) {
        expect.screenshot('actions_page_titles').to.be.captureSelector('.pageWrap,.expandDataTableFooterDrawer', function (page) {
            page.load("?" + urlBase + "#" + generalParams + "&module=Actions&action=menuGetPageTitles");
        }, done);
    });

    it('should load the actions > downloads correctly', function (done) {
        expect.screenshot('actions_downloads').to.be.captureSelector('.pageWrap,.expandDataTableFooterDrawer', function (page) {
            page.load("?" + urlBase + "#" + generalParams + "&module=Actions&action=menuGetDownloads");
        }, done);
    });

    it('should load the actions > downloads flat correctly', function (done) {
        expect.screenshot('actions_downloads_flat').to.be.captureSelector('.pageWrap,.expandDataTableFooterDrawer', function (page) {
            page.load("?" + urlBase + "#" + generalParams + "&module=Actions&action=menuGetDownloads&flat=1");
        }, done);
    });

    it('should load the visitors > overview correctly', function (done) {
        expect.screenshot('visitors_overview').to.be.captureSelector('.pageWrap,.expandDataTableFooterDrawer', function (page) {
            page.load("?" + urlBase + "#" + generalParams + "&module=VisitsSummary&action=index&columns=nb_total_overall_bandwidth");
        }, done);
    });
});
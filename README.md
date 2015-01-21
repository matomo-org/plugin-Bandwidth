# Piwik Bandwidth Plugin

[![Build Status](https://travis-ci.org/piwik/plugin-Bandwidth.svg?branch=master)](https://travis-ci.org/piwik/plugin-Bandwidth)

## Description

This plugin allows you to measure the bandwidth that was used by each page view or download. 
It enriches existing reports and APIs to show the used bandwidth. Find more information in the FAQ.

## FAQ

__How can I track the bandwidth?__

Log analytics:

The bandwidth will be automatically tracked when using the [log importer](http://piwik.org/log-analytics/) as long as 
your log format is supported.

Tracking API:

If you are using the [HTTP Tracking API](http://developer.piwik.org/api-reference/tracking-api) 
you can specify the bandwidth in bytes by appending the URL parameter `bw_bytes=1234` to the tracking URL. In this case 
a bandwidth of 1234 bytes will be tracked.

__Which actions support tracking of bandwidth?__

Pageviews (Page URLs and Page Titles) as well as Downloads.

__In which reports is the used bandwidth displayed?__

* Page URLs 
* Page Titles
* Downloads

All reports will show a column `Average Bandwidth` and `Sum Bandwidth`

The "Visitors => Overview" report shows a total bandwidth overview and it is possible to view the evolution over period.

__Which APIs does this plugin define or enrich?__

There is a report `Bandwidth.get` returning the total bandwidth (across all actions).

It also enriches varies reports such as `Actions.get`, `Actions.getPageUrls`, `Actions.getPageTitles` and `Actions.getDownloads`.
For example it adds columns such as `avg_bandwidth`, `sum_bandwidth`, `min_bandwidth`, `max_bandwidth` to each page view.

__Why are the bandwidth columns are not displayed in the UI?__

Make sure the Bandwidth plugin is activated by going to `Administration => Plugins`. Also the bandwidth columns are not 
displayed if no bandwidth was tracked in the current selected month.

## Changelog

0.1.0 Initial Release

## Support

Please direct any feedback to [hello@piwik.org](mailto:hello@piwik.org)
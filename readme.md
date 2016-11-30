# BHS Storehouse

A WordPress plugin for managing and serving assets exported from PastPerfect.

Built for the [Brooklyn Historical Society](http://brooklynhistory.org) by [Hard G](https://hardg.com).

This plugin is currently under active development. Do not use on a production site.

Features:

* Import assets from PastPerfect XML formats
* Dublin Core data for each asset is stored in a way that is friendly to other WordPress plugins that expect DC metadata
* Assets can be read from an external server via API endpoint

Requirements:

* PHP 5.3

## Use

### Importing and updating records

Upload PastPerfect export documents at Dashboard > PastPerfect Records > Import. Records are individuated by the `identifier` field; those records whose `identifer` cannot be found in the WP database will have a new local record created, while those with an existing `identifier` will have their local records updated.

### Accessing records

Records are available at an API endpoint with the format `/wp-json/bhs/v1/record/[identifier]`. Be sure to flush your permalinks after activating the plugin, to ensure that the endpoint works (Dashboard > Settings > Permalinks > Save).

There is a companion plugin that enables WordPress authors to access and display Storehouse data via shortcodes or template functions. See [bhs-client](https://github.com/bhslibrary/bhs-client).

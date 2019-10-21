# BHS Storehouse

A WordPress plugin for managing and serving assets exported from PastPerfect Museum Software or other software that generates valid XML.

Built for the [Brooklyn Historical Society](http://brooklynhistory.org) by [Hard G](https://hardg.com).

Features:

* Import assets from PastPerfect/valid XML formats
* Dublin Core data for each asset is stored in a way that is friendly to other WordPress plugins that expect metadata:
	title_collection,
	title_title,
	title_accession,
	identifier,
	type,
	publisher,
	description,
	date,
	coverage,
	coverage_GIS,
	creator,
	contributor,
	format,
	format_scale,
	format_size,
	rights,
	subject_people,
	subject_subject,
	subject_places,
	relation_ohms,
	relation_findingaid,
	rights_request,
	relation_image,
	relation_attachment,
	source,
	language,
	creator_alpha
* Assets can be read from an external server via API endpoint

Requirements:

* PHP 5.3+
* WordPress 4.4+

## Use

### Importing and updating records

Upload PastPerfect export documents at Dashboard > PastPerfect Records > Import. Records are individuated by the `identifier` field; those records whose `identifer` cannot be found in the WP database will have a new local record created, while those with an existing `identifier` will have their local records updated.

### Accessing records

Records are available at an API endpoint with the format `/wp-json/bhs/v1/record/[identifier]`. Be sure to flush your permalinks after activating the plugin, to ensure that the endpoint works (Dashboard > Settings > Permalinks > Save).

There is a companion plugin that enables WordPress authors to access and display Storehouse data via shortcodes or template functions. See [bhs-client](https://github.com/bhslibrary/bhs-client).

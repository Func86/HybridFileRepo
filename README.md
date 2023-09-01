# Usage

This extension is designed to be used with other foreign file repo extensions.

Example to build a live archive of moegirlpedia with this extension:

```php
wfLoadExtension( 'HybridFileRepo' );
// This must be configured before the foreign repo
$wgForeignFileRepos[] = [
	'class' => MediaWiki\Extension\HybridFileRepo\Repo::class,
	'name' => 'sharedFsRepo',
	'directory' => 'media', // The local directory that contains files
	'hashLevels' => 0,
	'url' => 'http://localhost:8080/w/media', // Change this to your real path
	'foreignRepo' => 'zhmoe', // This should be identical to the name of foreign file repo below
	'descBaseUrl' => 'https://commons.moegirl.org.cn/File:',
	'fetchDescription' => true,
];

// See also: extension description page on mediawiki.org
wfLoadExtension( 'QuickInstantCommons' );
$wgUseQuickInstantCommons = false;
$wgForeignFileRepos[] = [
	'class' => MediaWiki\Extension\QuickInstantCommons\Repo::class,
	'name' => 'zhmoe', // Must be a distinct name
	'directory' => $wgUploadDirectory, // FileBackend needs some value here.
	'apibase' => 'https://commons.moegirl.org.cn/api.php',
	'hashLevels' => 2, // Important this matches foreign repo if 404 transform enabled.
	'thumbUrl' => 'https://img.moegirl.org.cn/common/thumb', // Set to false to auto-detect
	'fetchDescription' => true, // Optional
	'descriptionCacheExpiry' => 43200, // 12 hours, optional (values are seconds). This cache is not adaptive.
	'transformVia404' => true, // Whether foreign repo supports 404 transform. Much faster if supported
	'abbrvThreshold' => 160, // must match what foreign repo uses if 404 transform enabled. Default is 255. Wikimedia uses 160.
	'apiMetadataExpiry' => 60 * 60 * 24, // Max time metadata is cached for. Recently changed items are cached for less
	'disabledMediaHandlers' => [TiffHandler::class] // media handler extensions to not use. For 404 handling its important that the local media handler extensions match the foreign ones.
];
```
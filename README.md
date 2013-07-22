# Upload files via $_FILES in the Lithium framework

This plugin will upload files via HTTP POST uploads only since it relies on $_FILES to get the source path.



## Dependencies
[li3_behaviors](http://github.com/jails/li3_behaviors) by @jails



## Installation

Clone the code to your library directory (or add it as a submodule):

	cd libraries
	git clone git@github.com:housni/li3_uploadable.git

If you plan to use the [Imagine](https://github.com/avalanche123/Imagine) (by @avalanche123) strategy, you also need to do:

	git submodule init && git submodule update

Include the library in in your `/app/config/bootstrap/libraries.php`

	Libraries::add('li3_uploadable');



## Configuration

	```php
	<?php
	/**
	 * I usually put this in:
	 *     app/config/bootstrap/uploadable.php
	 * and then I include this file in libraries.php
	 */
	use li3_uploadable\extensions\storage\Uploadable;

	Uploadable::config([
		/**
		 * Themes
		 *
		 * Uploads a zip file and then, using processors, it extracts the contents
		 * to `{:application}/views/themes/{:filename}` and deletes
		 * the zip file via a filter.
		 */
		'themeArchive' => [
			'adapter' => 'CompressionArchive',
			'strategies' => ['ZipArchive'],
			'processors' => [
				'extract' => ['{:application}/views/themes']
			],
			'save' => '{:application}/views/themes/{:filename}.zip',
			'remove' => '{:application}/views/themes/{:filename}',
			'filters' => [
				function($self, $params, $chain) {
					$extracted = $chain->next($self, $params, $chain);
					if (file_exists($extracted['extract']['source'])) {
						unlink($extracted['extract']['source']);
					}
					return $extracted;
				}
			]
		],

		/**
		 * Uploads a preview image of the theme with two different styles.
		 * Assuming the name of the image uploaded is `preview.png`, this will yield:
		 * 1. `thumb_preview.png` at 250 x 250px
		 * 2. `full_preview.png` at 1024 x 1024px
		 *
		 * The `Imagine` strategy depends on Imagine: https://github.com/avalanche123/Imagine
		 */
		'themePreview' => [
			'adapter' => 'Image',
			'strategies' => [
				'Imagine' => [
					'implementation' => 'Imagick',
					'styles' => [
						'thumb' => '250x250',
						'full' => '1280x1024'
					],
					'quality' => 100
				]
			],
			'save' => '{:application}/webroot/uploads/themes/{:id}/{:style}_{:basename}',
			'remove' => '{:application}/webroot/uploads/themes/{:id}',
			'url' => 'uploads/themes/{:id}/{:style}_{:basename}',
			'default' => 'img/missing-theme.png'
		],
	]);
	?>
	```



## Usage
The model below assumes you are accepting a `enctype="multipart/form-data"` form
whose fields of names `directory` and `preview` accept files.

	```php
	<?php
	/**
	 * app/models/Theme.php
	 */
	namespace app\models;

	use li3_behaviors\data\model\Behaviors;

	class Theme extends \lithium\data\Model {

		use Behaviors;

		protected $_actsAs = [
			'Uploadable' => [
				'fields' => [
					'themeArchive' => 'directory',
					'themePreview' => 'preview'
				],
				/**
				 * You can override placeholders
				 */
				'placeholders' => [
					'model' => 'MyModelName'
				]
			]
		];
	}
	?>
	```

In your views:

	```php
	// This will output the text value stored in themes.directory
	<?= $theme->directory; ?>

	// This will output the 250 x 250px thumb_preview.png url
	<img src="<?= $theme->preview->url('thumb'); ?>" alt="Preview of theme">
	```



## Validation
Look at `app/config/bootstrap/validators.php`



## More Adapter => Strategy
* MP3 => ID3
* Vide => FFmpeg
* PDF => wkhtmltopdf



## TODO
 * Make code comply with `li3_quality`
 * Add more documentation
 * Add tests
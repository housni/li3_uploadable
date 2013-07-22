<?php
/**
 * li3_uploadable: Upload files via $_POST
 *
 * @copyright     Copyright 2013, Housni Yakoob (http://koobi.co)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

use lithium\util\Validator;
use lithium\core\ConfigException;

/**
 * Checks to see if a file has been uploaded.
 *
 * In your model:
 * {{{
 * public $validates = [
 *     'avatar' => [
 *         [
 *             'isUploadedFile',
 *             'message' => 'You must upload a file.',
 *         ]
 *     ]
 * ];
 * }}}
 */
Validator::add('isUploadedFile', function($value, $rule, $options) {
	if (isset($options['skipEmpty']) && $options['skipEmpty'] === true) {
		return true;
	}

	$defaults = [
		'validateInCli' => false
	];
	$options += $defaults;
	if (!$options['validateInCli'] && PHP_SAPI === 'cli') {
		return true;
	}

	if ($_FILES[$options['field']]['error'] !== UPLOAD_ERR_NO_FILE) {
		return true;
	}
	return false;
});

/**
 * Checks to see if the uploaded file is within a file size range.
 *
 * In your model:
 * {{{
 * public $validates = [
 *     'avatar' => [
 *         [
 *	            'uploadedFileSize',
 *	            'message' => 'The image must be less than 2mb.',
 *				'in' => [0, 2, 'mb']
 *         ]
 *     ]
 * ];
 * }}}
 */
Validator::add('uploadedFileSize', function($value, $rule, $options) {
	if (isset($options['skipEmpty']) && $options['skipEmpty'] === true) {
		return true;
	}

	$defaults = [
		'validateInCli' => false
	];
	$options += $defaults;
	if (!$options['validateInCli'] && PHP_SAPI === 'cli') {
		return true;
	}

	$suffixes = [
		'' => 0, 'bytes' => 0, 'b' => 0,
		'kb' => 1, 'kilobytes' => 1,
		'mb' => 2, 'megabytes' => 2,
		'gb' => 3, 'gigabytes' => 3,
		'tb' => 4, 'terabyte' => 4,
		'pb' => 5, 'petabyte' => 5
	];
	$in = $options['in'];
	$unit = strtolower(array_pop($in));

	if (count($in) != 2) {
		throw new ConfigException('You must specify an upper and lower bound for `in`.');
	}

	if (!Validator::isInList($unit, null, ['list' => $suffixes])) {
		throw new ConfigException("Invalid unit `{$unit}` for size.");
	}

	$uploaded = $_FILES[$options['field']];

	list($lowerBound, $upperBound) = $in;
	$lower = round($lowerBound * pow(1024, $suffixes[$unit]));
	$upper = round($upperBound * pow(1024, $suffixes[$unit]));

	return Validator::isInRange($uploaded['size'], null, compact(
		'lower',
		'upper'
	));
});

/**
 * Checks to see if the uploaded file is of an allowed file type.
 *
 * In your model:
 * {{{
 * public $validates = [
 *     'avatar' => [
 *         [
 *	            'allowedFileType',
 *	            'message' => 'Please upload a JPG, PNG or GIF image.',
 *				'allowed' => [
 *					'image/png',
 *					'image/x-png',
 *					'image/jpeg',
 *					'image/pjpeg'
 *				]
 *         ]
 *     ]
 * ];
 * }}}
 */
Validator::add('allowedFileType', function($value, $rule, $options) {
	if (isset($options['skipEmpty']) && $options['skipEmpty'] === true) {
		return true;
	}


	$defaults = [
		'validateInCli' => false
	];
	$options += $defaults;
	if (!$options['validateInCli'] && PHP_SAPI === 'cli') {
		return true;
	}

	$uploaded = $_FILES[$options['field']];
	return Validator::isInList($uploaded['type'], null, [
		'list' => $options['allowed']
	]);
});
?>
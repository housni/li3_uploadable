<?php
/**
 * li3_uploadable: Upload files via $_POST
 *
 * @copyright     Copyright 2013, Housni Yakoob (http://koobi.co)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace li3_uploadable\extensions\adapter\storage\uploadable;

use lithium\util\String;
use Closure;

class Image extends \li3_uploadable\extensions\adapter\storage\Uploadable {

	/**
	 * copy() follows the rules unlike move_uploaded_file():
	 * http://www.php.net/manual/en/function.move-uploaded-file.php#85149
	 *
	 * As for the security issue, validation should be used to check if a file
	 * is, in fact, an uploaded file. Custom validation for this is provided
	 * in app/config/bootstrap/validators.php
	 *
	 * @param  [type] $source      [description]
	 * @param  [type] $destination [description]
	 * @param  [type] $options     [description]
	 * @return [type]              [description]
	 */
	public function save($source, $destination, array $options = []) {
		return $this->_filter(__METHOD__, compact('source', 'destination', 'options'), function($self, $params) {

			$source = $params['source'];
			$destination = $params['destination'];
			$options = $params['options'];
			return function($self, $params) use (&$source, &$destination, $options) {
				return copy($source, $destination);
			};
		});
	}

	public function remove($files) {
		return function($self, $params) use ($files) {
			$result = [];
			foreach ((array) $files as $file) {
				if (!file_exists($file)) {
					/**
					 * I'm doing this because due to the `styles` option, there
					 * may be multiple images and this method would have already
					 * deleted one of the styles on the first pass.
					 *
					 * e.g: The following may exist:
					 *     uploads/avatars/1/thumb_avatar.png
					 *     uploads/avatars/1/full_avatar.png
					 * This method may be set to delete the `uploads/avatars/1`
					 * directory via the `remove` option in which case both
					 * styles would be deleted but the `remove` method would be
					 * visited twice.
					 *
					 * May need to change this for a cleaner solution.
					 */
					// @todo Throw exception, perhaps?
					return true;
				}
				$result[] = static::_remove($file);
			}
			return !in_array(false, $result, true);
		};
	}

	public function url($url, array $options = []) {
		$absolute = static::_url();
		return function($self, $params) use ($url, $absolute, $options) {
			$style = ['style' => $options['key']];
			return $absolute . String::insert($url, $style);
		};
	}
}
?>
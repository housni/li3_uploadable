<?php
/**
 * li3_uploadable: Upload files via $_POST
 *
 * @copyright     Copyright 2013, Housni Yakoob (http://koobi.co)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace li3_uploadable\extensions\adapter\storage\uploadable;

use Closure;

class File extends \li3_uploadable\extensions\adapter\storage\Uploadable {

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

	public function remove(array $files) {
		return function($self, $params) use ($files) {
			$result = [];
			foreach ($files as $file) {
				$result[] = static::_remove($file);
			}
			return !in_array(false, $result, true);
		};
	}

	public function url($url, array $options = []) {
		$absolute = static::_url();
		return function($self, $params) use ($url, $absolute, $options) {
			return $absolute . $url;
		};
	}
}
?>
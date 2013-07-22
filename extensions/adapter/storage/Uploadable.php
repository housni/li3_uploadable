<?php
/**
 * li3_uploadable: Upload files via $_POST
 *
 * @copyright     Copyright 2013, Housni Yakoob (http://koobi.co)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace li3_uploadable\extensions\adapter\storage;

use lithium\action\Request;
use lithium\net\http\Router;

class Uploadable extends \lithium\core\Object {
	/**
	 * Generates absolute URL to the webroot folder via reverse routing.
	 *
	 * @return string Absolute URL to webroot
	 */
	protected static function _url() {
		$request = new Request;
		return Router::match('/', $request, ['absolute' => true]);
	}

	protected static function _remove($path) {
		if (!file_exists($path)) {
			// @todo Throw exception, perhaps?
		}

		if (is_dir($path)) {
			return static::_recursiveRemove($path);
		}
		return unlink($path);
	}

	protected static function _recursiveRemove($dir) {
		$files = array_diff(scandir($dir), ['.', '..']);
		foreach ($files as $file) {
			if (is_dir("$dir/$file")) {
				static::_recursiveRemove("$dir/$file");
			} else {
				unlink("$dir/$file");
			}
		}
		return rmdir($dir);
	}
}
?>
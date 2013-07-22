<?php
/**
 * li3_uploadable: Upload files via $_POST
 *
 * @copyright     Copyright 2013, Housni Yakoob (http://koobi.co)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace li3_uploadable\extensions\adapter\storage\uploadable;

use Closure;
use InvalidArgumentException;
use li3_uploadable\extensions\storage\Uploadable;

// @todo remove this
use app\extensions\helper\Debug;

class CompressionArchive extends \li3_uploadable\extensions\adapter\storage\Uploadable {

	protected $_processors = ['extract'];

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
				$copied = copy($source, $destination);
				$results = [];
				// @todo check if $this->_config['processors'] exists to avoid undefined index notice
				foreach ($this->_config['processors'] as $method => $arguments) {
					if (!in_array($method, $this->_processors)) {
						throw new InvalidArgumentException("The processor `$name` is not supported.");
					}
					foreach ($arguments as $index => $arg) {
						$arguments[$index] = Uploadable::interpolate($arg, $options);
					}
					$results[$method] = Uploadable::applyStrategies($method, $options['name'], $destination, $arguments);
				}
				if ($copied && !in_array(false, $results, true)) {
					return $results;
				}
				return false;
			};
		});
	}

	public function remove($files) {
		return function($self, $params) use ($files) {
			$result = [];
			foreach ((array) $files as $file) {
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
<?php
/**
 * li3_uploadable: Upload files via $_POST
 *
 * @copyright     Copyright 2013, Housni Yakoob (http://koobi.co)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace li3_uploadable\extensions\storage;

use lithium\util\String;
use lithium\core\Libraries;
use li3_uploadable\extensions\data\behavior\Uploadable as UploadableBehavior;

class Uploadable extends \lithium\core\Adaptable {

	/**
	 * Stores configurations uploadable adapters.
	 *
	 * @var array
	 */
	protected static $_configurations = [];

	/**
	 * Libraries::locate() compatible path to adapters for this class.
	 *
	 * @var string
	 */
	protected static $_adapters = 'adapter.storage.uploadable';

	/**
	 * Libraries::locate() compatible path to strategies for this class.
	 *
	 * @var string Dot-delimited path.
	 */
	protected static $_strategies = 'strategy.storage.uploadable';

	protected $_field = null;
	protected $_entity = null;
	protected $_name = null;

	public function __construct($field, $name, $entity) {
		$this->_field = $field;
		$this->_name = $name;
		$this->_entity = $entity;
	}

	public static function config($config = null) {
		return parent::config($config);
	}

	public static function interpolate($path, array $options = []) {
		$defaults = [
			'placeholders' => []
		];
		$options += $defaults;
		return String::insert($path, $options['placeholders']);
	}

	public static function save($source, $destination, array $options = []) {
		$defaults = [
			'strategies' => true
		];
		$options += $defaults;
		$name = $options['name'];
		$settings = static::config($name);

		/**
		 * Database dependant placeholders are replaced here.
		 * For example {:id}, {:created}, {:updated}, etc.
		 */
		$destination = String::insert($destination, $options['placeholders']);

		$destinationInfo = pathinfo($destination);

		if ($options['strategies']) {
			static::applyStrategies('makeDirs', $name, $destination);
		}
		static::makeDirs($destinationInfo['dirname']);

		if ($options['strategies']) {
			$options += compact('source');
			static::applyStrategies(__FUNCTION__, $name, $destination, $options);
		}

		$method = static::adapter($name)->save($source, $destination, $options);
		$params = compact('source', 'destination', 'options');
		return static::_filter(__FUNCTION__, $params, $method, $settings['filters']);
	}

	/**
	 * @param  [type]  $dir       [description]
	 * @param  integer $mode      [description]
	 * @param  boolean $recursive [description]
	 * @return [type]             [description]
	 * @filter This method can be filtered.
	 */
	public static function makeDirs($dir, $mode = 0777, $recursive = true) {
		return static::_filter(__FUNCTION__, compact('dir', 'mode', 'recursive'), function($self, $params) {

			$mode = $params['mode'];
			$recursive = $params['recursive'];

			$results = [];
			foreach ((array) $params['dir'] as $dir) {
				if (!is_dir($dir)) {
					$results[] = mkdir($dir, $mode, $recursive);
				}
			}

			return !in_array(false, $results, true);
		});
	}

	/**
	 * Should use ApplyStrategies() to call enabled() from the strategy class
	 * that should check if the requirements to use the strategy are met.
	 *
	 * @todo
	 * @see: lithium\core\Adaptable::enabled()
	 */
	public static function enabled($name) {
		return parent::enabled($name);
	}

	/**
	 * @return string The name of the uploaded file stored in the database.
	 */
	public function __toString() {
		$field = $this->_field;
		$exported = $this->_entity->export();
		if (!isset($exported['data'][$field])) {
			return '';
		}
		$return = $exported['data'][$field];

		if (!is_string($return)) {
			return '';
		}
		return $return;
	}

	/**
	 * @param string $name Name of the config in filesystem.php
	 * @return string The url to folder
	 * @see app/config/bootstrap/koobi/filesystem.php
	 */
	public function url($key = null, array $options = []) {
		$defaults = [
			'strategies' => true,
			'placeholders' => []
		];
		$options += $defaults;
		$name = $this->_name;
		$settings = static::config($name);
		$url = $settings['url'];
		$data = $this->_entity->data();

		$field = $this->_field;
		$fileName = $data[$field];
		if (empty($data[$field])) {
			if (!isset($settings['default'])) {
				return null;
			}
			$url = $settings['default'];
			$fileParts = explode(DIRECTORY_SEPARATOR, $settings['default']);
			$fileName = end($fileParts);
		}

		$options['placeholders'] = static::placeholders($fileName, $this->_entity, ['field' => $field]);
		$url = String::insert($url, $options['placeholders']);

		$options += compact('key');
		if ($options['strategies']) {
			$url = static::applyStrategies(__FUNCTION__, $name, $url, $options);
		}

		$method = static::adapter($name)->url($url, $options);
		$params = compact('url', 'options');
		return static::_filter(__FUNCTION__, $params, $method, $settings['filters']);
	}

	/**
	 * Reserved placeholders:
	 * :field = name of the FileSystem config ($this->_config)
	 * :basename = file name with extension (eg: 'avatar.png')
	 * :filename = file name minus extension (eg: 'avatar')
	 * :extension = file extension (eg: 'png')
	 *
	 * @param string $file The file for which placeholder values will be
	 *                     extracted from using pathinfo().
	 * @param array $placeholders An array of key/val placeholders that will
	 *                            overwrite any placeholders generated inside
	 *                            this method.
	 * @return array An array of placeholders.
	 */
	public static function placeholders($file, $entity = null, array $placeholders = []) {
		$info = pathinfo($file);

		$modelNamespace = $entity->model();
		$modelSplit = explode('\\', $modelNamespace);
		$model = strtolower(end($modelSplit));

		$data = [];
		if (!is_null($entity)) {
			$data = $entity->data();
		}

		$basename = $info['filename'];
		$extension = null;
		if (isset($info['extension'])) {
			$basename = "{$info['filename']}.{$info['extension']}";
			$extension = $info['extension'];
		}

		return $placeholders + [
			'application' => Libraries::get(true, 'path'),
			'basename' => $basename,
			'filename' => $info['filename'],
			'extension' => $extension,
			'model' => $model
		] + $data;
	}

	// removes a single file
	public static function remove($file, array $options = []) {
		$defaults = [
			'strategies' => true
		];
		$options += $defaults;
		$name = $options['name'];
		$settings = static::config($name);
		$adapter = static::adapter($name);

		if ($options['strategies']) {
			$options += compact('source');
			$file = static::applyStrategies(__FUNCTION__, $name, $file);
		}

		$method = $adapter->remove($file);
		$params = compact('file');
		return static::_filter(__FUNCTION__, $params, $method, $settings['filters']);
	}
}

?>
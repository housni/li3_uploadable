<?php
/**
 * li3_uploadable: Upload files via $_POST
 *
 * @copyright     Copyright 2013, Housni Yakoob (http://koobi.co)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace li3_uploadable\extensions\data\behavior;

use \li3_uploadable\extensions\storage\Uploadable as UploadableStorage;
use lithium\util\String;
use lithium\util\Validator;

class Uploadable extends \li3_behaviors\data\model\Behavior {

	protected static function _modified($data, $fields, array $options = []) {
		$defaults = [
			'format' => 'any'
		];
		$options += $defaults;

		foreach ($fields as $field => $name) {
			if (isset($data[$field]['name'])) {
				$file = $data[$field]['name'];
				if (!Validator::rule('isUploadedFile', $file, $options['format'], ['field' => $field])) {
					unset($data[$field]);
				}
			}
		}
		return $data;
	}

	/**
	 * @todo We may also need to filter `remove()`.
	 */
	protected function _init() {
		parent::_init();
		if (PHP_SAPI === 'cli') {
			return true;
		}

		if ($model = $this->_model) {
			$behavior = $this;
			$model::applyFilter('save', function($self, $params, $chain) use ($behavior) {
				$options = [
					'placeholders' => []
				];
				$configs = $behavior->_config;
				$fields = $behavior::_formattedFields($configs['fields']);
				$source = $destination = $configName = [];

				$data = $behavior::_modified($params['data'], $fields);

				$dataKeys = array_keys($data);
				if (empty($dataKeys)) {
					return $chain->next($self, $params, $chain);
				}

				$queried = $params['entity']->export()['data'];
				if (0 === count(array_intersect_key($queried, $fields))) {
					return $chain->next($self, $params, $chain);
				}

				$params['data'] = [];
				$params['entity']->set($data);

				$entity = $params['entity'];
				$entityData['model'] = $entity->model();
				$entityData['data'] = $entity->data();

				if (!isset($configs['placeholders'])) {
					$configs['placeholders'] = [];
				}

				$defaults = [
					'save' => null,
					'remove' => null,
				];

				$skip = [];
				$fieldCount = 0;
				foreach ($fields as $field => $name) {
					if (array_key_exists($field, $data)) {
						$configName[$field] = $name;
						$source[$field] = $_FILES[$field]['tmp_name'];
						$settings = UploadableStorage::config($name);
						$settings += $defaults;

						$path = $settings['save'];
						$newFile = $_FILES[$field]['name'];

						if ($entity->exists()) {
							if (!$entity->modified($field)) {
								$skip[$field] = true;
							} else {
								// We delete the old file
								$oldFile = $entity->export()['data'][$field];
								$removeOptions['placeholders'] = UploadableStorage::placeholders(
									$oldFile,
									$configs['placeholders'] + ['field' => $field],
									$entityData
								);

								$removePath = UploadableStorage::interpolate($settings['remove'], $removeOptions);
								UploadableStorage::remove($removePath, $removeOptions + ['name' => $name]);

								if (null === $data[$field]) {
									$skip[$field] = true;
									continue;
								}

								$options['placeholders'] = UploadableStorage::placeholders(
									$newFile,
									$configs['placeholders'] + ['field' => $field],
									$entityData
								);
								$destination[$field] = UploadableStorage::interpolate($path, $options);

								/**
								 * The field has been modified to now contain 'null' so we
								 * remove the field from the config so that, later on, we don't
								 * try to upload a non-existent file later.
								 */
								if (null === $entity->$field) {
									$params['entity']->$field = null;
									unset($configs['fields'][$field]);
								} else {
									/**
									 * the field has been modified but it contains a value
									 * so we must upload the new one.
									 */
									$fieldValue = static::fieldValue($path, $options['placeholders']);
									$params['entity']->$field = $fieldValue;
								}
							}
						} else {
							$options['placeholders'] = UploadableStorage::placeholders(
								$newFile,
								$configs['placeholders'] + ['field' => $field],
								$entityData
							);
							$fieldValue = static::fieldValue($path, $options['placeholders']);
							$destination[$field] = UploadableStorage::interpolate($path, $options);
							$params['entity']->$field = $fieldValue;
						}
					} else {
						$fieldCount++;
					}
				}

				if (!$params['entity']->validates()) {
					return false;
				}

				$saved = $chain->next($self, $params, $chain);
				if ($fieldCount == count($fields)) {
					return $saved;
				}

				$options['placeholders'] += $params['entity']->data();

				$uploaded = [];
				foreach ($fields as $field => $name) {
					if (!isset($skip[$field]) || $skip[$field] !== true) {
						$options['name'] = $configName[$field];
						$uploaded[] = UploadableStorage::save($source[$field], $destination[$field], $options);
					}
				}
				return $saved && !in_array(false, $uploaded, true);
			});

			$model::applyFilter('find', function($self, $params, $chain) use ($behavior) {
				$data = $chain->next($self, $params, $chain);

				switch ($params['type']) {
					case 'first':
						$entity = $behavior->invokeMethod('_assignAccessors', [$data]);
						return $entity;
					break;

					case 'all':
						foreach ($data as $datum) {
							$entity = $behavior->invokeMethod('_assignAccessors', [$datum]);
						}
						return $data;
					break;

					default:
						return $data;
					break;
				}
			});

			$model::applyFilter('delete', function($self, $params, $chain) use ($behavior) {
				$entity = $params['entity'];
				$entityData['model'] = $entity->model();
				$entityData['data'] = $entity->data();

				$configs = $behavior->_config;
				$fields = $behavior::_formattedFields($configs['fields']);
				if (!isset($configs['placeholders'])) {
					$configs['placeholders'] = [];
				}

				$results = [];
				foreach ($fields as $field => $name) {
					$path = UploadableStorage::config($name)['remove'];
					$existingFile = $entity->export()['data'][$field];
					$options['placeholders'] = UploadableStorage::placeholders(
						$existingFile,
						$configs['placeholders'] + ['field' => $field],
						$entityData
					);
					$destination = UploadableStorage::interpolate($path, $options);

					$results[] = UploadableStorage::remove($destination, $options + ['name' => $name]);
				}
				$deleted = $chain->next($self, $params, $chain);

				return $deleted && !in_array(false, $results, true);
			});
		}
	}

	public static function fieldValue($path, array $placeholders) {
		preg_match_all('@({:\w+})@', $path, $matches);
		$value = end($matches[0]);
		return String::insert($value, $placeholders);
	}

	protected function _assignAccessors($entity) {
		$fields = static::_formattedFields($this->_config['fields']);
		$queried = $entity->export()['data'];
		if (0 === count(array_intersect_key($queried, $fields))) {
			return $entity;
		}

		foreach ($fields as $field => $name) {
			if (isset($_FILES[$field]['name'])) {
				// Attempted file upload
				$file = $_FILES[$field]['name'];
				if (Validator::rule('isUploadedFile', $file, 'any', ['field' => $field])) {
					// Valid file upload
					$entity->$field = new UploadableStorage($field, $name, $entity);
				}
			} else {
				// Read
				$entity->$field = new UploadableStorage($field, $name, $entity);
			}
		}
		return $entity;
	}

	protected static function _formattedFields($config) {
		$formatted = [];
		foreach ($config as $name => $fields) {
			foreach ((array) $fields as $field) {
				$formatted[$field] = $name;
			}
		}
		return $formatted;
	}
}
?>

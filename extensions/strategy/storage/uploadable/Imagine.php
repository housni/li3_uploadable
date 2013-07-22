<?php
/**
 * li3_uploadable: Upload files via $_POST
 *
 * @copyright     Copyright 2013, Housni Yakoob (http://koobi.co)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace li3_uploadable\extensions\strategy\storage\uploadable;

use lithium\core\Libraries;
use lithium\util\String;
use Imagine as ImagineLib;
use li3_uploadable\extensions\storage\Uploadable;
use li3_uploadable\extensions\adapter\storage\uploadable\File;

class Imagine extends \lithium\core\Object {

	/**
	 * Reserved placeholders:
	 * :styles
	 */
	public function __construct(array $config = []) {
		$defaults = [
			'styles' => []
		];
		parent::__construct($config + $defaults);
	}

	/**
	 * Filters into Uploadable::makeDirs().
	 *
	 * @param  [type] $destination [description]
	 * @param  [type] $options     [description]
	 * @return [type]              [description]
	 */
	public function makeDirs($destination) {
		$imagine = $this;
		Uploadable::applyFilter(__FUNCTION__, function(
			$self, $params, $chain) use ($destination, $imagine) {

			if (!empty($imagine->_config['styles'])) {
				$styles = $imagine->_config['styles'];

				$params['dir'] = [];
				foreach ($styles as $style => $dimension) {
					$destinationInfo = pathinfo(String::insert($destination, ['style' => $style]));
					$params['dir'][] = $destinationInfo['dirname'];
				}
			}

			return $chain->next($self, $params, $chain);
		});
	}

	public function interpolate($file) {
		$styles = $this->_config['styles'];

		$return = [];
		foreach ($styles as $style => $dimension) {
			$return[$dimension] = String::insert($file, ['style' => $style]);
		}
		return $return;
	}

	/**
	 * @todo If `styles` is not defined, just copy the original to `$destination`
	 * @todo If width and height are not defined, don't resize
	 * @todo http://www.imagemagick.org/Usage/resize/
	 * @param  [type] $path    [description]
	 * @param  [type] $options [description]
	 * @return [type]          [description]
	 */
	public function save($destination, array $options = []) {
		$options['quality'] = ['quality' => 75];
		if (isset($this->_config['quality'])) {
			$options['quality'] = ['quality' => $this->_config['quality']];
		}

		foreach ($this->interpolate($destination) as $dimension => $resolved) {
			list($options['width'], $options['height']) = explode('x', $dimension);
			$source = $options['source'];
			$this->_resize($source, $resolved, $options);
		}

		/**
		 * We return `true` instead of the chain since we don't want
		 * File::save() to do anything because Imagine::save() does
		 * all the writing.
		 */
		$adapter = Uploadable::adapter($options['name']);
		$adapter->applyFilter(__FUNCTION__, function(
			$self, $params, $chain) {
			return function($self, $params) {
				return true;
			};
		});
	}

	public function remove($file) {
		$files = [];
		foreach ($this->interpolate($file) as $dimension => $resolved) {
			$files[] = $resolved;
		}
		return $files;
	}

	protected function _resize($source, $destination, array $options = []) {
		$defaults = [
			'width' => 32,
			'height' => 32,
		];
		$options += $defaults;

		$implementation = $this->_config['implementation'];
		$imagineClass = "Imagine\\{$implementation}\Imagine";
		$imagine = new $imagineClass();
		$size = new ImagineLib\Image\Box($options['width'], $options['height']);
		$mode = ImagineLib\Image\ImageInterface::THUMBNAIL_OUTBOUND;
		return $imagine->open($source)->thumbnail($size, $mode)->save($destination, $options['quality']);
	}

	public function url($url, array $options = []) {
		return $url;
	}
}
?>
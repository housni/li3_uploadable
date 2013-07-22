<?php

namespace li3_uploadable\extensions\strategy\storage\uploadable;

use ZipArchive as ZipArchiveLib;
use lithium\core\Libraries;
use lithium\util\String;
use li3_uploadable\extensions\storage\Uploadable;
use li3_uploadable\extensions\adapter\storage\uploadable\File;


// @todo remove this
use app\extensions\helper\Debug;

class ZipArchive extends \lithium\core\Object {

	public function extract($source, $destination) {
		$destination = reset($destination);
		$zip = new ZipArchiveLib;
		if ($zip->open($source) !== true) {
			return false;
		}
	    $zip->extractTo($destination);
		$zip->close();

		return compact('source', 'destination');
	}

	public function save($destination, array $options = []) {
		return $destination;
	}

	public function url($url, array $options = []) {
		return $url;
	}
}
?>
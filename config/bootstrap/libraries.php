<?php
/**
 * li3_uploadable: Upload files via $_POST
 *
 * @copyright     Copyright 2013, Housni Yakoob (http://koobi.co)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

use lithium\core\Libraries;

/**
 * Creating an auto loader for Imagine
 * @see https://github.com/avalanche123/Imagine/
 */
Libraries::add('Imagine', [
	'path' => Libraries::get('li3_uploadable', 'includePath'),
	'transform' => function($class) {
		return str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
	}
]);
?>

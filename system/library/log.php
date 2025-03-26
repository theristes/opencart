<?php
/**
 * @package		OpenCart
 *
 * @author		Daniel Kerr
 * @copyright	Copyright (c) 2005 - 2022, OpenCart, Ltd. (https://www.opencart.com/)
 * @license		https://opensource.org/licenses/GPL-3.0
 *
 * @see		https://www.opencart.com
 */
namespace Opencart\System\Library;
/**
 * Class Log
 */
class Log {
	/**
	 * @var string
	 */
	private string $file;

	/**
	 * Constructor
	 *
	 * @param string $filename
	 */
	public function __construct(string $filename) {
		$this->file = DIR_LOGS . $filename;

		if (stream_is_local($this->file) && !is_file($this->file)) { // Ensure the file path is local
			$handle = fopen($this->file, 'w');

			if ($handle) { // Ensure the file handle is valid
				fclose($handle);
			}
		}
	}

	/**
	 * Write
	 *
	 * @param mixed $message
	 *
	 * @return void
	 */
	public function write($message): void {
		if (stream_is_local($this->file)) { // Ensure the file path is local
			file_put_contents($this->file, date('Y-m-d H:i:s') . ' - ' . print_r($message, true) . "\n", FILE_APPEND);
		} else {
			// Handle unsupported stream wrapper error
			error_log('Unsupported stream wrapper for log file: ' . $this->file);
		}
	}
}

<?php

namespace Sevenedge\Lib\Utilities;

/**
 * Class Varnish
 * Varnish utilities. Pretty basic stuff
 */
class Varnish {

	private $_secretFile = null, $_host = null;

	/**
	 *
	 * @param $secretFile Path to secret file
	 * @param $host Host, including port number. eg. localhost:6082
	 */
	public function __construct($secretFile, $host = 'localhost:6082') {
		if(!is_readable($secretFile)) {
			throw new \InvalidArgumentException("File $secretFile does not exist");
		}
		$this->_secretFile = $secretFile;
		$this->_host = $host;
	}

	/**
	 * Ban all varnish cache for the given pattern
	 * @param $pattern Pattern to match for banning
	 * @return mixed. True if successful, an array with the output in case the operation failed. The last line of the output will usually give you sufficient info.
	 */
	public function ban($pattern) {
		$pattern = str_replace('"', '\"', $pattern);
		exec('varnishadm -S ' . $this->_secretFile . ' -T ' . $this->_host . ' "ban req.url ~ ' . $pattern . '"', $output, $status);

		return $status === 0 ? true : $output;
	}



}
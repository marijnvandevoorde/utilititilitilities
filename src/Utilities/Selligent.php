<?php
namespace Sevenedge\Utilities\Utilities;

use Cake\Core\Configure;
use Cake\Network\Exception\BadRequestException;
use Cake\Network\Exception\NotImplementedException;
use Cake\Network\Exception\UnauthorizedException;
use phpseclib\Net\SFTP;
use Psr\Log\InvalidArgumentException;

/**
 * Class Selligent
 * @package Sevenedge\Utilities\Utilities
 *
 *
 */
class Selligent {

	const
		PROTO_SFTP = 0,
		PROTO_FTPTLS = 1;


	private $_server;
	private $_protocol;

	public function __construct($server, $username, $password, $port = 22, $protocol = self::PROTO_SFTP, $passive = false)
	{
		$this->_protocol = $protocol;
		switch ($protocol) {
			case self::PROTO_FTPTLS:
				$this->_server = ftp_ssl_connect($server, $port, 15);
				$res = ftp_login($this->_server, $username, $password);
				if (!$res) {
					throw new UnauthorizedException('cannot login on remote server');
				}
				if ($passive) {
					ftp_pasv($this->_server, TRUE);
				}

				break;
			case self::PROTO_SFTP:
				$this->_server = new SFTP($server, $port);
				if (!$this->_server->login($username, $password)) {
					throw new UnauthorizedException('cannot login on remote server');
				}
				break;
			default:
				throw new NotImplementedException('no implementation for protocol ' . $protocol);
		}

	}




	public function upload($sourcePath, $targetPath) {
		switch ($this->_protocol) {
			case self::PROTO_FTPTLS:

				if (!ftp_put($this->_server, $targetPath, $sourcePath, FTP_ASCII)) {
					throw new BadRequestException('failed to transfer file');

				} else {
					return array('success' => TRUE);
				}

				break;
			case self::PROTO_SFTP;
				$putresult = $this->_server->put($targetPath, $sourcePath, SFTP::SOURCE_LOCAL_FILE);
				if (!$putresult) {
					return array(
						'success' => FALSE,
						'error' => $this->_server->getErrors()
					);
				}
				else {
					return array('success' => TRUE);
				}
				break;
			default:
				throw new NotImplementedException('no implementation for protocol ' . $this->_protocol);

		}
	}

	public function deleteFile($targetPath) {
		switch ($this->_protocol) {
			case self::PROTO_FTPTLS:
				ftp_delete($this->_server, $targetPath);
				break;
			case self::PROTO_SFTP;
				$res = $this->_server->delete($targetPath);
				break;
			default:
				throw new NotImplementedException('no implementation for protocol ' . $this->_protocol);

		}
	}


}
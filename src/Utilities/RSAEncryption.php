<?php

namespace Marijnworks\Utilities\Utilities;

define('CRYPT_RSA_PKCS15_COMPAT', true);

use Cake\Network\Exception\NotImplementedException;


class RSAEncryption {

	const LIB_PHP = 0,
		LIB_SECLIB = 1;

	private $_config = [
		"digest_alg" => "sha1",
		"private_key_bits" => 1024,
		"private_key_type" => OPENSSL_KEYTYPE_RSA,
		"library" => self::LIB_PHP
	], $_rsa = null
	, $_keypair = [];


	public function __construct($config = null) {
		if (is_array($config)) {
			$this->_config = array_merge($this->_config, $config);
		}
		if (!defined('OPENSSL_VERSION_NUMBER') || OPENSSL_VERSION_NUMBER < 0x009080bf){
			$this->_config['library'] = self::LIB_SECLIB;
		}

		switch ($this->_config['library']) {
			case self::LIB_PHP:
				break;
			case self::LIB_SECLIB:
				$this->_rsa = new \phpseclib\Crypt\RSA();
				$this->_rsa->setEncryptionMode(\phpseclib\Crypt\RSA::ENCRYPTION_PKCS1);
				$this->_rsa->setSignatureMode(\phpseclib\Crypt\RSA::SIGNATURE_PKCS1);
				$this->_rsa->setHash($this->_config['digest_alg']);
				break;
			default:
				throw new NotImplementedException("library not implemented");
		}



	}

	public function loadKeys($private = null, $public = null) {
		if (!is_null($private)) {
			$this->_keypair['private'] = $private;
		}
		if (!is_null($public)) {
			$this->_keypair['public'] = $public;
		}
	}

	public function createKeypair() {
		switch ($this->_config['library']) {
			case self::LIB_PHP:
				$res = openssl_pkey_new($this->_config);
				openssl_pkey_export($res, $this->_keypair['private']);
				$pubKey = openssl_pkey_get_details($res);
				openssl_free_key($res);
				$this->_keypair['public'] = $pubKey["key"];
				break;
			case self::LIB_SECLIB:
				$key = $this->_rsa->createKey($this->_config['private_key_bits']);
				$this->_keypair['private'] = $key['privatekey'];
				$this->_keypair['public'] = $key['publickey'];
				break;
			default:
				// never happens. ever :-). Check should be in the construct.
				return null;
		}

		return $this->getKeys();
	}

	public function getKeys($type = null) {

		return is_null($type) ? $this->_keypair : $this->_keypair[$type];
	}

	public function getModulusAndExponent() {
		switch ($this->_config['library']) {
			case self::LIB_PHP:
				$keyResource = openssl_pkey_get_public($this->_keypair['public']);
				$res = openssl_pkey_get_details($keyResource);
				return $res['rsa'];
			case self::LIB_SECLIB:
				$this->_rsa->loadKey($this->_keypair['public']); // public key
				$raw = $this->_rsa->getPublicKey(\phpseclib\Crypt\RSA::PUBLIC_FORMAT_RAW);
				return $raw;
			default:
				// never happens. ever :-). Check should be in the construct.
				return null;
		}
	}

	public function encrypt($data) {
		switch ($this->_config['library']) {
			case self::LIB_PHP:
				openssl_public_encrypt($data, $encrypted, $this->_keypair['public']);
				return $encrypted;
			case self::LIB_SECLIB:
				$this->_rsa->loadKey($this->_keypair['public']); // public key
				return $this->_rsa->encrypt($data);
			default:
				// never happens. ever :-). Check should be in the construct.
				return null;
		}
	}

	public function decrypt($data) {
		switch ($this->_config['library']) {
			case self::LIB_PHP:
				openssl_private_decrypt($data, $decrypted, $this->_keypair['private']);
				return $decrypted;
			case self::LIB_SECLIB:
				$this->_rsa->loadKey($this->_keypair['private']); // private key
				return $this->_rsa->decrypt($data);
			default:
				// never happens. ever :-). Check should be in the construct.
				return null;
		}
	}


	public function sign($data) {
		switch ($this->_config['library']) {
			case self::LIB_PHP:
				openssl_sign($data, $signature, $this->_keypair['private']);
				return $signature;
			case self::LIB_SECLIB:
				$this->_rsa->loadKey($this->_keypair['private']); // private key
				return $this->_rsa->sign($data);
			default:
				// never happens. ever :-). Check should be in the construct.
				return null;
		}
	}

	public function verify($data, $signature) {
		switch ($this->_config['library']) {
			case self::LIB_PHP:
				return openssl_verify($data, $signature, $this->_keypair['public'], OPENSSL_ALGO_SHA512);
			case self::LIB_SECLIB:
				$this->_rsa->loadKey($this->_keypair['public']); // public key
				return $this->_rsa->verify($data, $signature);
			default:
				// never happens. ever :-). Check should be in the construct.
				return null;
		}
	}

}
<?php

namespace Sevenedge\Utilities;

/**
 * Class Security
 * All kinds of security methods. Please read the documentation for each method because they need a lot of configuration and parameters
 * @Author Marijn Vandevoorde
 * TODO: work something out for overflowing numbers on 32 bit machines. or reeeaaally big numbers on 64 bit :-)
 * @package Sevenedge\Utilities
 */
class Security {
	const
		TOKENTYPE_COUNTER = 0,
		TOKENTYPE_TIME = 1;

	/**
	 *
	 * Obfucates an integer. A simple yet rather hard to crack asymmetric encryption for numbers.
	 *
	 * @param $number The number to obfuscate
	 * @param $maxid The max number. I'd advise to take the max for a 32 bit number minus 1, so 2147483647
	 * @param $prime A very fucking big prime number. Go for something with 9 digits! You'll need an inverse prime to deobfuscate: ($prime * inverse) & $maxid === 1
	 * @link http://www.bigprimes.net/archive/prime/1400001/ for your big prime.
	 * @link http://planetcalc.com/3311/ for your inverse (integer = prime, modulo = maxid +1!!!!!
	 * @param $rand A random number. Can also be quite frikking big.
	 * @return string hexadecimal string that represents your integer.
	 */
	public static function obfuscateNumber($number, $prime, $rand, $maxid) {
		return dechex((($number * $prime) & $maxid) ^ $rand);
	}

	/**
	 *
	 * deObfucates an integer
	 *
	 * @param $number The string to deobfuscate to the number again.
	 * @param $maxid The max number. I'd advise to take the max for a 32 bit number minus 1, so 2147483647
	 * @param $inverse The inverse of the very big prime.
	 * @see self::obfuscateNumber
	 * @param $rand A random number. Can also be quite frikking big, but has to be the same one used when obfuscating that number.
	 * @return the original number.
	 */
	public static function deobfuscateNumber($number, $inverse, $rand, $maxid) {
		return ((hexdec($number) ^ $rand) * $inverse) & $maxid;
	}


	/**
	 *
	 * Simple yet effective token verification.
	 * Will verify if the counter is behaving as expected (i.e.: +1) or if the the token hasn't expired (by comparing timestamps)
	 * Prevents fiddling by using a secure signature hash.
	 *
	 * @param $token Associative array containing:
	 * 			- token: the received token.
	 * 			- secret: a secret value used when hashing.
	 * 			- type: one of the available types. right now counter or time (use constants starting with TOKENTYPE in this class
	 * 			- numberobfucation: optional. @see deobfuscateNumber and @see obfuscateNumber for specifics. Should be an associative array containing values for
	 * 					• prime
	 * 					• randxor
	 * 					• maxid
	 * 					• inverse
	 * 			- timestamp: optional. the current timestamp to compare to. will fallback to curren time. not needed when using  TOKENTYPE_COUNTER
	 * 			- counter: optional. the current value of the counter. Not needed when using TOKENTYPE_TIME
	 * 			- tolerance: optional. the tolerance of the verification. in counts or in seconds, depending on the type value.
	 *
	 * @param $debug. set to true to debug. it will always return the same token with no errors.
	 * @param $update. Default true, which means this request will return an updated token This will prevent the stimestamp version to expire (new timestamp on every request) and it takes care of increasing the counter for you
	 * Set to false to disable this behaviour. Only makes sense if you want to create a token that expires after 30 minutes.
	 */
	public static function verifyToken($token, $debug = false, $update = true) {
		// whatever happens, we return a token that lasts for 5 minutes
		if ($debug) {
			return array('token' => 'debug');
		}
		switch ($token['type']) {
			case self::TOKENTYPE_COUNTER:
				if (!isset($token['counter']) || !intval($token['counter'])) {
					$token['counter'] = 1;
				}
				break;
			case self::TOKENTYPE_TIME:
				if (!isset($token['timestamp'])) {
					$token['timestamp'] = time();
				}
				break;
			default:
				throw new \NotImplementedException("No implementation for type {$token["type"]}");

		}
		$response = array('token' => implode('-', self::_createToken($token)));
		if (!isset($token['token'])) {
			$response['error'] = 'notoken';
		}
		else {
			$receivedToken = explode('-', $token['token']);
			if (count($receivedToken) !== 2) {
				$response['error'] = __('invalidtoken');
			} elseif (sha1($token['secret'] . $receivedToken[1]) !== $receivedToken[0]) {
				$response['error'] = __('invalidtoken');
			} else {
				if (isset($token['numberobfuscation'])) {
					$receivedToken[1] = self::deobfuscateNumber($receivedToken[1], $token['numberobfuscation']['inverse'], $token['numberobfuscation']['randxor'], $token['numberobfuscation']['maxid']);

				}
				switch($token['type']) {
					case self::TOKENTYPE_COUNTER:
						$reference = $token['counter'];
						break;
					case self::TOKENTYPE_TIME:
						$reference = $token['timestamp'];
						break;
					default:
						throw new \NotImplementedException("No implementation for type {$token["type"]}");

				}
				if ($reference < $receivedToken[1] || $reference - $receivedToken[1] > (isset($token['tolerance']) ? $token['tolerance'] : 1)) {
					$response['error'] = __('tokenexpired');
				}
			}
		}
		return $response;
	}

	/**
	 * @param $token basically the same token array used in @see self::verifyToken
	 * @return array an array with two elements. The hash and the value that represents either a timestamp or counter number.
	 */
	private static function _createToken($token) {
		switch ($token['type']) {
			case self::TOKENTYPE_COUNTER;
				if (isset($token['numberobfuscation'])) {
					$seed = self::obfuscateNumber($token['counter'], $token['numberobfuscation']['prime'], $token['numberobfuscation']['randxor'], $token['numberobfuscation']['maxid']);
				} else {
					$seed = $token['counter'];
				}
				break;
			case self::TOKENTYPE_TIME:
				if (!isset($token['timestamp'])) {
					$token['timestamp'] = time();
				}
				if (isset($token['numberobfuscation'])) {
					$seed = self::obfuscateNumber($token['timestamp'], $token['numberobfuscation']['prime'], $token['numberobfuscation']['randxor'], $token['numberobfuscation']['maxid']);
				} else {
					$seed = $token['timestamp'];
				}
				break;
			default:
				throw new \NotImplementedException("No implementation for type {$token["type"]}");
		}
		return array(sha1($token['secret'] . (isset($token['extraData']) ? $token['extraData'] : '') . $seed), $seed);
	}
}

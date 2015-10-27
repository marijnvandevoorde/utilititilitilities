<?php

namespace Sevenedge\CakeUtils;

/**
 * Class CacheWrapper
 *
 * @Author Marijn Vandevoorde <marijn@sevenedge.be>
 * @package Sevenedge\CakeUtils
 */
class CacheWrapper extends \Cache {

	private static $_TTL_OFFSET = 600;



	public static function setTTLOffset($secs) {
		self::$_TTL_OFFSET = $secs;
	}


	public static function getTTLOffset($secs) {
		return self::$_TTL_OFFSET;
	}

	public static function write($key, $value, $config = 'default') {
		$settings = self::settings($config);

		if (empty($settings)) {
			return false;
		}
		if (!self::isInitialized($config)) {
			return false;
		}
		$key = self::$_engines[$config]->key($key);

		if (!$key || is_resource($value)) {
			return false;
		}

		// wrap value with ttl offset?
		$value = isset($settings['prefetch']) ? array('ttl' => time() + $settings['duration'] - $settings['prefetch'], 'val' => $value) : $value;

		$success = self::$_engines[$config]->write($settings['prefix'] . $key, $value, $settings['duration']);
		self::set(null, $config);
		if ($success === false && $value !== '') {
			trigger_error(
					__d('cake_dev',
							"%s cache was unable to write '%s' to %s cache",
							$config,
							$key,
							self::$_engines[$config]->settings['engine']
					),
					E_USER_WARNING
			);
		}
		return $success;
	}

	public static function read($key, $config = 'default') {
		$settings = self::settings($config);

		if (empty($settings)) {
			return false;
		}
		if (!self::isInitialized($config)) {
			return false;
		}
		$key = self::$_engines[$config]->key($key);
		if (!$key) {
			return false;
		}
		$value = self::$_engines[$config]->read($settings['prefix'] . $key);

		if (isset($settings['prefetch'])) {
			if ($value['ttl'] > 0) {
				// If current time is equal or greater than a fake expiration time
				if (time() >= $value['ttl']) {
					if (self::$_engines[$config]->write(
							$settings['prefix'] . $key,
							array('ttl' => time() + $settings['prefetch'], 'val' => $value),
							$settings['duration'])
					) {
						return false;
					}

				}
			}
			return $value['val'];
		}
		return $value;
	}


}
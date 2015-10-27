<?php

/**
 * @Author Rein Deneut
 * @Email Rein@sevenedge.be
 *
 * For all your bit.ly needs.
 */

namespace Sevenedge\Lib\Utilities;

class Bitly {

	CONST API_ENDPOINT = 'http://api.bit.ly/v3/shorten';
	CONST
		FORMAT_TXT = 'txt',
		FORMAT_JSON = 'json',
		FORMAT_XML = 'xml';

	/* make a URL small */
	public static function generate($url, $login, $appkey, $format = self::FORMAT_TXT){
		//create the URL

		$params = array(
			'longUrl' => $url,
			'login' => $login,
			'apiKey' => $appkey,
			'format' => $format
		);

		//public function addRequest($url, $params = array(), $headers = array(), $loadDefaults = true, array $optionOverrides = array(), $callback = false, $callbackparams = array())

		$cr = new CurlRequest();
		$key = $cr->addRequest(self::API_ENDPOINT, $params, array(), true, array(CURLOPT_HEADER => false));

		if ($cr->execute() === 0) {
			$response = $cr->getResponse($key);
			if(strtolower($params['format']) === 'txt') {
				$url = trim($response['response']);
				if (substr($url,0,7) !== 'http://') {
					return false;
				}
				return $url;
			} elseif(strtolower($params['format']) === 'json') {
				$json = @json_decode($response['response'], true);
				if ($json['status_code'] === 200) {
					return $json['data']['url'];
				} else {
					return false;
				}
			} else { //xml
				$xml = simplexml_load_string($response['response']);
				if ($xml->status_code === "200") {
					return $xml->data->url;

				}
				return false;
			}
		}

		return false;
	}

}
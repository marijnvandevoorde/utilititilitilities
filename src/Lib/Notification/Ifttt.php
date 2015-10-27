<?php

namespace Sevenedge\Lib\Notification;

use Sevenedge\Lib\Utilities\CurlRequest;


class Ifttt {

	CONST ENDPOINT_PUSH = 'https://maker.ifttt.com/trigger/{event}/with/key/{key}';

	private $_senderAuth;

	public function __construct() {

	}


	public static function sendMessage($recipients, $title, $message, $type = 'notification', $link = null)
	{
		if (!is_array($recipients)) {
			$recipients = array($recipients);
		}
		$postData = array('value1' => $title, 'value2' => $message, 'value3' => $link);
		$endPoint = str_replace('{event}', $type, self::ENDPOINT_PUSH);

		$keys = array();

		$cr = new CurlRequest();
		foreach ($recipients as $recipient) {
			$finalEndPoint = str_replace('{key}', $recipient, $endPoint);
			$keys[$recipient] = $cr->addRequest($finalEndPoint, json_encode($postData), array('Content-Type: application/json'), true, array(CURLOPT_POST => true, CURLOPT_BINARYTRANSFER => 1, CURLOPT_HEADER => true));
		}
		$cr->execute();

		foreach ($keys as $recipient => $key) {
			$msg = $cr->getResponse($key);
			if ($msg['http_code'] !== 200) {
				$response = json_decode($msg['response'], 1);
				$keys[$recipient] = array('status' => 'error', 'error' => $response['errors'][0]['message']);
			} else {
				$keys[$recipient] = array('status' => 'success');
			}
		}
		$cr->__destruct();
		return $keys;
	}

}
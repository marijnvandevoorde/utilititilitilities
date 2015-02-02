<?php

/**
 * Class Pushbullet
 * @author Marijn Vandevoorde <marijn@marijnworks.be>
 * @link http://www.marijnworks.be
 *
 * Super simple push to pushbullet api.
 */
namespace Sevenedge\Notification;

use Sevenedge\Utilities\CurlRequest;

class Pushbullet
{

	CONST ENDPOINT_PUSH = 'https://{token}@api.pushbullet.com/v2/pushes';

	private $_senderAuth;

	public function __construct($senderAuth) {
		$this->_senderAuth = $senderAuth;
	}

	public function sendMessage($recipients, $title, $message, $link = false)
	{
		if (!is_array($recipients)) {
			$recipients = array($recipients);
		}
		$postData = array('title' => $title, 'body' => $message, 'type' => 'note');

		if ($link) {
			$postData['url'] = $link;
			$postData['type'] = 'link';
		}

		$keys = array();

		$cr = new CurlRequest();
		foreach ($recipients as $recipient) {
			$postData['client_iden'] = $recipient;
			$keys[$recipient] = $cr->addRequest(self::ENDPOINT_PUSH, json_encode($postData), array('Content-Type: application/json'), true, array(CURLOPT_POST => true, CURLOPT_BINARYTRANSFER => 1, CURLOPT_HEADER => true, CURLOPT_USERPWD => $this->_senderAuth));
		}
		$cr->execute();


		foreach ($keys as $recipient => $key) {
			$msg = $cr->getResponse($key);
			echo $recipient . " ----> ";
			var_dump(json_decode($msg['response'], 1));

			echo "\n------\n-----\n";
		}
		$cr->__destruct();
	}
}
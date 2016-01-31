<?php

/**
 * Class Pushbullet
 * @author Marijn Vandevoorde <marijn@marijnworks.be>
 * @link http://www.marijnworks.be
 *
 * Super simple push to pushbullet api.
 */
namespace Marijnworks\Utilities\Notification;

use Cake\Network\Exception\BadRequestException;
use Marijnworks\Utilities\Utilities\CurlRequest;

class Pushbullet
{

	CONST ENDPOINT_PUSH = 'https://api.pushbullet.com/v2/pushes';
	CONST ENDPOINT_CONTACTLIST = 'https://api.pushbullet.com/v2/contacts';

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
			$postData['email'] = $recipient;
			$keys[$recipient] = $cr->addRequest(self::ENDPOINT_PUSH,
				json_encode($postData),
				array('Content-Type: application/json',  'Authorization: Bearer ' . $this->_senderAuth),
				true,
				array(CURLOPT_POST => true, CURLOPT_BINARYTRANSFER => 1, CURLOPT_HEADER => true));
		}
		$cr->execute();


		foreach ($keys as $recipient => $key) {
			$msg = $cr->getResponse($key);
			if ($msg['http_code'] === 200) {
				$keys[$recipient] = array('status' => 'success');
			} else {
				$keys[$recipient] = array('status' => 'error', 'error' => $msg['error']);
			}
		}
		$cr->__destruct();

		return $keys;
	}

	/**
	 * @return mixed array of contacts
	 * @throws BadRequestException in case the request was bad. contains http code and error message.
	 */
	public function getContacts() {
		$cr = new CurlRequest();
		$key = $cr->addRequest(self::ENDPOINT_CONTACTLIST, array(),
			array('Content-Type: application/json',  'Authorization: Bearer ' . $this->_senderAuth),
			true, array(CURLOPT_BINARYTRANSFER => 1, CURLOPT_HEADER => true));
		$response = $cr->getResponse($key);
		if ($cr->execute() === 0) {
			$response['response'] = json_decode($response['response'], 1);
			if ($response['http_code'] === 200) {
				return $response['response']['contacts'];
			} else {
				throw new BadRequestException($response['response']['error']['message'], $response['http_code']);
			}

		}
		else {
			throw new BadRequestException($response['error'], $response['http_code']);
		}

	}
}
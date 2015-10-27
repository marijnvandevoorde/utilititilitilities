<?php

/**
 * Class Notifier
 * @author Marijn Vandevoorde <marijn@marijnworks.be>
 * @link http://www.marijnworks.be
 *
 * A notifier class. should be quite pluggable
 * Addressbook should be a big associative array:
 *	array(
 * 		"contacts" => array(
 * 			[identifier] =>
 * 				array(
 *					"email" => [email]
 *	 				"pushbullet" => [pushbullet email]
 * 					"phone" => [+32xxxxxxxxx]
 * 				)
 *			),
 * 		"groups" => array(
 * 			[groupname] => array([identifier1],[identifier2],...)
 * 		)
 * 	);
 *
 */
namespace Sevenedge\Lib\Notification;

use Sevenedge\Lib\Utilities\CurlRequest;

class Notifier
{
	private $_addressBook;

	public function __construct($addressBook) {
		$this->_addressBook = $addressBook;
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
			$keys[$recipient] = $cr->addRequest(self::ENDPOINT_PUSH, $postData, array(), true, array(CURLOPT_POST => true, CURLOPT_USERPWD => $this->_senderAuth));
		}
		$cr->execute();


		foreach ($keys as $recipient => $key) {
			var_dump($recipient, $cr->getResponse($key));
		}
		$cr->__destruct();
	}
}
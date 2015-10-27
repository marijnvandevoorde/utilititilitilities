<?php

/*
		App::uses('MailchimpTools', 'Lib');
		$mc = new MailchimpTools(Configure::read('mailchimp.apikey'), Configure::read('mailchimp.listid'));
		$res = $mc->getInfo("marijnnn@gmail.com");
		if (!$res || $res['status'] !== 'subscribed'){
			if (empty($firstName) || empty($lastName))
			$mc->subscribe($email, $firstName, $lastName);
		}
		else
		{
			$mc->unsubscribe($email);
		}

*/

namespace Sevenedge\Lib\Mail;

class MailchimpTools
{
	private $apiKey;
	private $listId;
	private $apiEndpoint = 'https://<dc>.api.mailchimp.com/2.0/';
	private $verifySSL   = false;



	/**
	 * Create a new instance
	 * @param string $apiKey Your MailChimp API key
	 */
	function __construct($apiKey, $listId)
	{
		$this->apiKey = $apiKey;
		$this->listId = $listId;
		list(, $datacentre) = explode('-', $this->apiKey);
		$this->apiEndpoint = str_replace('<dc>', $datacentre, $this->apiEndpoint);
	}


	public function subscribe($email, $firstName, $lastName)
	{
		$res = $this->call('lists/subscribe', array(
			'id'                => $this->listId,
			'email'             => array('email' => $email),
			'merge_vars'        => array('FNAME' => $firstName, 'LNAME' => $lastName),
			'update_existing'   => true,
			'replace_interests' => false,
			'send_welcome'      => false
		));

		if (isset($res['leid']))
		{
			return $res['leid'];
		}
		else
		{
			return false;
		}
	}


	public function unsubscribe($email)
	{
		$res = $this->call('lists/unsubscribe', array(
			'id'                => $this->listId,
			'email'             => array('email' => $email)
		));

		if (isset($res['complete']) && $res['complete'] === true)
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	public function getInfo($email)
	{
		$res = $this->call('lists/member-info', array(
			'id'                => $this->listId,
			'emails'             => array(array('email' => $email))
		));

		if ($res['success_count'] == 1)
		{
			return $res['data'][0];
		}

		return false;
	}




	/**
	 * Call an API method. Every request needs the API key, so that is added automatically -- you don't need to pass it in.
	 * @param  string $method The API method to call, e.g. 'lists/list'
	 * @param  array  $args   An array of arguments to pass to the method. Will be json-encoded for you.
	 * @return array          Associative array of json decoded API response.
	 */
	public function call($method, $args=array())
	{
		return $this->_raw_request($method, $args);
	}




	/**
	 * Performs the underlying HTTP request. Not very exciting
	 * @param  string $method The API method to be called
	 * @param  array  $args   Assoc array of parameters to be passed
	 * @return array          Assoc array of decoded result
	 */
	private function _raw_request($method, $args=array())
	{
		$args['apikey'] = $this->apiKey;

		$url = $this->apiEndpoint.'/'.$method.'.json';

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
		curl_setopt($ch, CURLOPT_USERAGENT, 'PHP-MCAPI/2.0');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->verifySSL);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($args));
		$result = curl_exec($ch);
		curl_close($ch);

		return $result ? json_decode($result, true) : false;
	}

}
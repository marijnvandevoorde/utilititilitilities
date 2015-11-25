<?php

namespace Sevenedge\Utilities\Mail;

class Sendgrid
{
	private $_apiKey, $_user;

	public function __construct( $apiKey) {
		$this->_apiKey = $apiKey;
	}



	// $to_emails 	=> array('mail1@gmail.com', 'mail2@gmail.com', ...)
	// $html 		=> '<table><tr><td>html example</td><tr></table>'
	// $subject 	=> 'a regular string as subject'
	// $global_merge_vars AND $merge_vars : http://help.mandrill.com/entries/21678522-How-do-I-use-merge-tags-to-add-dynamic-content-
	// $async => if are more then 10 recipients, doesn't matter what you fill in here... see https://mandrillapp.com/api/docs/messages.JSON.html#method-send
	public function sendMail($recipients, $html, $text, $subject, $from_mail, $from_name)
	{

		$recipientString = '';
		$toNames = array(); $toMails = array();
		foreach ($recipients as $mail => $name) {
			$recipientString .= '&to[]=' .$mail;
			$recipientString .= '&toname[]=' .$name;
			$toNames[] = $name;
			$toMails[] = $mail;
		}


		// build json string
		$data_array = array(
			'subject' => $subject,
			'text' => $text,
			'html' => $html,
			'to' => $toMails,
			'toname' => $toNames,
			'from' => $from_mail,
		);
		if (!empty($from_name)) {

			$data_array['fromname'] = $from_name;
		}

		$data_array = http_build_query($data_array);




		$ch = curl_init();
		// Set the cURL options
		curl_setopt($ch, CURLOPT_URL,            "https://api.sendgrid.com/api/mail.send.json");
		curl_setopt($ch, CURLOPT_POST,           TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS,     $data_array);
		curl_setopt($ch, CURLOPT_HTTPHEADER,     array('AUTHORIZATION: Bearer ' . $this->_apiKey));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // for https:// to work, do not verify peer or host
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);




		// perform POST
		$result = curl_exec($ch);
		// $this->log(print_r($result, true));
		curl_close($ch);
		$json_result = json_decode($result);
		if ($json_result->message !== 'success') {
			Debugger::log('mail not sent, sendgrid returns: ' . print_r($json_result, true));
			return false;

		}
		return true;
	}
}
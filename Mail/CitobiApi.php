<?php
/**
 *
 * A nice wrapper around some code by
 * @Author Jonathan Beck
 *
 *
 */

namespace Sevenedge\Mail;
use Sevenedge\Utilities\CurlRequest;

class CitobiApi
{
	CONST ENDPOINT_ADDUPDATE = "https://www.actito.be/ActitoWebServices/ws/profile/v1/updateOrCreateProfile?licence={license}&login={login}&pwd={password}&table={table}";
	CONST ENDPOINT_GET = "https://www.actito.be/ActitoWebServices/ws/profile/v1/profileInformationBy{reference}?licence={license}&login={login}&pwd={password}&table={table}&{key}={value}";

	private $_license, $_login, $_password;

	public function __construct($license, $login, $password) {
		$this->_login = $login;
		$this->_license = $license;
		$this->_password = $password;
	}

	private function _getEndpoint($tpl) {
		$endpoint = str_replace('{login}', $this->_login, $tpl);
		$endpoint = str_replace('{password}', $this->_password, $endpoint);
		return str_replace('{license}', $this->_license, $endpoint);
	}


	/**
	 * @param $userData array containing the following indexes
	 * emailAddress: the user's email address
	 * lastName: the user's last name
	 * firstName: the user's first name
	 * sex: M or F for male or female
	 * motherLanguage: upper case iso code for language (NL, FR, EN)
	 * bonuscardNumber: number of the user's bonus card
	 * addressStreet,
	 * addressNumber,
	 * addressBox,
	 * addressPostalCode,
	 * addressLocality: the address details (locality = city)
	 */
	public function addOrUpdate($userData, $table)
	{
		$cr = new CurlRequest();
		$endpoint = str_replace('{table}', $table, $this->_getEndpoint(self::ENDPOINT_ADDUPDATE));

		$attributes = array();
		foreach ($userData	as $key => $value) {
			$attributes[] = array('@name' => $key, 'value' => $value);
		}
		$userData = array('profileInformation' => array('attribute' => $attributes));

		$key = $cr->addRequest($endpoint,
			array(), array(), true,
			array(
				CURLOPT_FOLLOWLOCATION => false,
				CURLOPT_HEADER => true,
				CURLOPT_POST => true,
				CURLOPT_HTTPHEADER => array("Content-Type: application/json"),
				CURLOPT_POSTFIELDS => json_encode($userData)
			)
		);

		$err = $cr->execute();
		if ($err === 0) {
			$res = $cr->getResponse($key);
			$response = json_decode($res['response'], 1);
			if (is_null($response)) {
				// for some reason this returns xml? just great. but fuck it, we'll just parse dirtyyyy
				if (preg_match_all('#<error>([^<]+)</error>#', $res['response'], $matches)) {
					return array('success' => false, 'errors' => $matches[1]);
				}
				return array('success' => false, 'errors' => array());
			}
			return array('success' => true, 'id' => $response['value']);
		} else {
			return null;
		}
	}

	public function getData($email = null, $id = null, $table) {
		if (is_null($id) && is_null($email)) {
			return null;
		}

		$endpoint = str_replace('{table}', $table, $this->_getEndpoint(self::ENDPOINT_GET));
		if (!is_null($id)) {
			$endpoint = str_replace('{key}', 'id', $endpoint);
			$endpoint = str_replace('{value}', $id, $endpoint);
			$endpoint = str_replace('{reference}', 'Id', $endpoint);
		} else {
			$endpoint = str_replace('{key}', 'key=uniqueKey&value', $endpoint);
			$endpoint = str_replace('{value}', $email . 'EOYH2014', $endpoint);
			$endpoint = str_replace('{reference}', 'Key', $endpoint);
		}
		$cr = new CurlRequest();

		$key = $cr->addRequest($endpoint,
			array(), array(), true,
			array(
				CURLOPT_FOLLOWLOCATION => false,
				CURLOPT_HEADER => true,
				CURLOPT_HTTPHEADER => array("Content-Type: application/json"),
			)
		);

		$err = $cr->execute();
		if ($err === 0) {
			$res = $cr->getResponse($key);
			$response = json_decode($res['response'], 1);
			if (is_null($response)) {
				// for some reason this returns xml? just great. but fuck it, we'll just parse dirtyyyy
				if (preg_match_all('#<error>([^<]+)</error>#', $res['response'], $matches)) {
					return array('success' => false, 'errors' => $matches[1]);
				}
				return array('success' => false, 'errors' => array());
			}
			$assoc = array();

			foreach ($response['profileInformation']['attribute'] as $attr) {
				$assoc[$attr['@name']] = isset($attr['value']) ? $attr['value'] : null;
			}
			return array('success' => true, 'data' => $assoc);
		} else {
			return null;
		}
	}

}

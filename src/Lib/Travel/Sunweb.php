<?php
	require_once(dirname( __FILE__ ) . '/curlrequest.php');

	class Sunweb {
		private $_subdomain = 'zon';

		CONST URL = 'http://{subdomain}.sunweb.be/data/xml/Catalog/Default.aspx';
		CONST PRICINGURL = 'http://{subdomain}.sunweb.be/webservices/getprices/{hotelId}/{startDate}/{endDate}/VL/-1?format=json';
		CONST HOTELURL = 'http://{subdomain}.sunweb.be/webservices/getaccommodation/{hotelId}?format=json';
		CONST LOCATIONURL = 'http://{subdomain}.sunweb.be/webservices/getlocation/{locationId}?format=json';
		CONST WEATHERURL = 'http://{subdomain}.sunweb.be/webservices/getweatherbylocationid/{locationId}?format=json';
		private $_cr;

		public function __construct($logCallback = null, $language ='nld') {
			$this->_cr = new CurlRequest();
			$this->_logCallback = $logCallback !== null ? $logCallback : function($level, $message) {};
			if ($language === 'fra') {
				$this->_subdomain = 'soleil';
			}
		}

		public function getList($raw = true) {
			$api_url = str_replace('{subdomain}', $this->_subdomain, self::URL);
			$key = $this->_cr->addRequest($api_url);
			$err = $this->_cr->execute();
			if ($err !== 0) {
				$this->_cr->clean();
				$this->_log("failed to get the lastest xml from sunweb", E_USER_ERROR);
				return;
			}
			$res = $this->_cr->getResponse($key);
			$this->_cr->clean();
			if ($raw) {
				return $res['response'];
			} else {
				throw new NotImplementedException("Parsed responses have not been implemented yet");
			}
		}

		/**
		 * get the data for a specific hotel and a specific date.
		 * the date should be in euro format. the date should be a php date object.
		 **/
		public function getHotelPricings($hotelId, $pricings, $raw = false) {
			$keys = array();
			$responses = array();
			$api_url = str_replace('{subdomain}', $this->_subdomain, self::PRICINGURL);

			foreach ($pricings as $index => $pricing) {
				$call = str_replace('{hotelId}', $hotelId, $api_url);
				$call = str_replace('{startDate}', $pricing[0], $call);
				$call = str_replace('{endDate}', $pricing[1], $call);
				$keys[$index] = $this->_cr->addRequest($call);
			}

			$err = $this->_cr->execute();
			foreach ($keys as $index => $key) {
				$response = $this->_cr->getResponse($key);

				if ($response['errno'] === 0) {
					if ($raw) {
						$responses[$index] =  $response['response'];
					} else {
						$responses[$index] =   json_decode($response['response'], 1);
					}
				} else {
					$this->_log("failed to get the json info from sunweb for hotel $hotelId and startdate $index", E_USER_ERROR);
				}

			}
			$this->_cr->clean();
			return $responses;
		}

		public function getWeather($locationId, $raw = false) {
			$call = str_replace('{subdomain}', $this->_subdomain, self::WEATHERURL);
			$call = str_replace('{locationId}', $locationId, $call);
			$key = $this->_cr->addRequest($call);
			$err = $this->_cr->execute();
			if ($err !== 0) {
				$this->_cr->clean();
				$this->_log("failed to get the json info from sunweb", E_USER_ERROR);
				return null;
			}
			$res = $this->_cr->getResponse($key);
			$this->_cr->clean();
			if ($raw) {
				return $res['response'];
			} else {
				return json_decode($res['response'], 1);
			}

		}


		public function getLocationInfo($locationId, $raw = false) {
			$call = str_replace('{subdomain}', $this->_subdomain, self::LOCATIONURL);
			$call = str_replace('{locationId}', $locationId, $call);
			$key = $this->_cr->addRequest($call);
			$err = $this->_cr->execute();
			if ($err !== 0) {
				$this->_cr->clean();
				$this->_log("failed to get the json info from sunweb", E_USER_ERROR);
				return null;
			}
			$res = $this->_cr->getResponse($key);
			$this->_cr->clean();
			if ($raw) {
				return $res['response'];
			} else {
				return json_decode($res['response'], 1);
			}

		}


		public function getHotelInfo($hotelId, $raw = false) {
			$call = str_replace('{subdomain}', $this->_subdomain, self::HOTELURL);
			$call = str_replace('{hotelId}', $hotelId, $call);

			$key = $this->_cr->addRequest($call);
			$err = $this->_cr->execute();
			if ($err !== 0) {
				$this->_cr->clean();
				$this->_log("failed to get the json info from sunweb", E_USER_ERROR);
				return null;
			}
			$res = $this->_cr->getResponse($key);
			$this->_cr->clean();
			if ($raw) {
				return $res['response'];
			} else {
				return json_decode($res['response'], 1);
			}

		}

		private function _log($message, $level) {
			call_user_func($this->_logCallback, $level, $message);

		}

	}
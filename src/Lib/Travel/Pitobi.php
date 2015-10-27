<?php

require_once(dirname( __FILE__ ) . '/curlrequest.php');


/**
 * Class Pitobi
 * @author Marijn Vandevoorde <marijn@sevenedge.be>
 * @link http://www.sevenedge.be
 *
 * Magical pitobi api wrapper. you can login and pretty much do everything you want. only thing is that you have to authorize your account once. (this can be done by generating the landingurl below
 *
 * Instructions, well most of this should be self-explaning.
 *
 */
class Pitobi
{
	private $_credentials;
	private $_cr, $_logCallback;


	/**
	 * @var array list of endpoints for api calls. works like a template. stuff in between curly brackets has to be replaced here. do it on call. no need to do them all when only using one or two.
	 */
	private $endPoints = array(
		'login' => 'http://summer.pitobi.com/besun/login.php',
		'overview' => 'http://summer.pitobi.com/besun/global_report.php',
		'fetchCsv' => 'http://summer.pitobi.com/besun/global_report.php?toxl=1&myzone={zone}',
		'alternatives' => 'http://summer.pitobi.com/besun/comparator.php',
		'lastupdatedates' => 'http://summer.pitobi.com/besun/index.php'
	);

	/**
	 * @param $credentials: array with username, password and maybe a cookie if you already have one
	 * @param $logCallback: should be a method that takes 2 paramters. 1 is the level (E_WARN, E_ERROR,... you know, the php error levels. 2 is the message.
	 * 				FYI: the ones used are E_USER_NOTICE for debug, E_USER_WARNING for... warning and E_USER_ERROR for booboos
	 * @throws Exception throws a good old exception when the access token could not be obtained. no other details, it either works or doesn't.
	 */
	public function __construct($credentials, $logCallback = null)
	{
		$this->_credentials = $credentials;
		$this->_cr = new CurlRequest();
		$this->_logCallback = $logCallback !== null ? $logCallback : function($level, $message) {};
		// we want to check the cookie now. it might have been a while since we used it so it might be expired.
		$this->_getAuthenticationDetails(true);
	}




	/**
	 * private helper method to get an access token.
	 * @throws Exception when the access token could not be obtained. not much details here. it either works or it doesn't. don't feel like working it all out to the details.
	 */
	private function _getAuthenticationDetails($checkCookie = false)
	{
		if (isset($this->_credentials['cookie'])) {
			if (!$checkCookie || $this->_loggedIn()) {
				return $this->_credentials;
			}
		}
		$this->_log('logging in, token expired', E_USER_NOTICE);
		$key = $this->_cr->addRequest($this->endPoints['login'],
			array(
				'login' => $this->_credentials['username'],
				'password' => $this->_credentials['password']
			), array(), true,
			array(
				CURLOPT_FOLLOWLOCATION => false,
				CURLOPT_REFERER => $this->endPoints['login'],
				CURLOPT_HEADER => true,
				CURLOPT_POST => true,
			)
		);
		$err = $this->_cr->execute();

		if ($err === 0) {
			// (using jeff bridges' voice in tron legacy: https://www.youtube.com/watch?v=tFXYuw96d0c) "we got in!"
			$res = $this->_cr->getResponse($key);
			$this->_cr->clean();

			// I guess I could do some checks here to be sure we do the redirect, or get the global_report.php file to scrape locations etc.
			//we should have received more cookies to set now. let's keep them over the next requests
			$matches = array();
			$matchcount = preg_match_all('/^Set-Cookie: (\S*)/mi', $res['header'], $matches);


			if ($matchcount) {
				$this->_credentials['cookie'] = implode("; ", $matches[1]);
				return $this->_credentials;
			}
		}

		// if we go there, it means something went wrong along the road. debug yourself, i don't feel like catching all possible exceptions atm.
		$this->_cr->clean();

		$this->_log("Failed to get an access token in any possible way", E_USER_ERROR);
		throw new Exception("something went terribly, terribly wrong");
	}

	/** a really stupid test to see if we are logged in */
	private function _loggedIn() {
			$key = $this->_cr->addRequest($this->endPoints['overview'], array(),
				array(
				), true,
				array(
					CURLOPT_FOLLOWLOCATION => false,
					CURLOPT_REFERER => $this->endPoints['login'],
					CURLOPT_COOKIE => $this->_credentials['cookie'],
					CURLOPT_HEADER => true
				)
			);

			$err = $this->_cr->execute();
			if ($err === 0) {
				$res = $this->_cr->getResponse($key);
			}
			$this->_cr->clean();
			return ($err === 0 && $res['response'] !== '<meta http-equiv="refresh" content="0;URL=login.php">');
	}

	/**
	 * just getting those authenticationdetails so we can save the authenticationtoken etc.
	 * @return an array containing the original authentication details, supplemented with the cookie etc.
	 */
	public function getAuthenticationDetails() {
		$details = $this->_getAuthenticationDetails();
		return $details;
	}

	/**
	 * simple method to fetch a list for a specific zone
	 * @param $raw: not implemented yet. might put the parsing of the data here int he future and offer the choise. now it's all raw
	 * @return list of all accomodations
	 * @throws
	 */
	public function getList($zone, $raw = true)
	{
		$endpoint = str_replace('{zone}', urlencode($zone) , $this->endPoints['fetchCsv']);

		$this->_getAuthenticationDetails();
		$key = $this->_cr->addRequest($endpoint, array(),
			array(
			), true,
			array(
				CURLOPT_FOLLOWLOCATION => false,
				CURLOPT_REFERER => $this->endPoints['overview'],
				CURLOPT_COOKIE => $this->_credentials['cookie'],
				CURLOPT_HEADER => true,

			)
		);
		$err = $this->_cr->execute();
		if ($err === 0) {
			$res = $this->_cr->getResponse($key);
			$this->_cr->clean();
			if ($raw) {
				return $res['response'];
			} else {
				throw new NotImplementedException("Parsed responses have not been implemented yet");
			}
		}
		$this->_log("Failed to fetch the data for zone '$zone'. response was " . print_r($this->_cr->getResponse(), 1), E_USER_ERROR);
		$this->_cr->clean();
		return null;
	}

	public function getLastUpdateDates($raw = false) {
		$this->_getAuthenticationDetails();
		$key = $this->_cr->addRequest($this->endPoints['lastupdatedates'], array(),
			array(
			), true,
			array(
				CURLOPT_FOLLOWLOCATION => false,
				CURLOPT_REFERER => $this->endPoints['overview'],
				CURLOPT_COOKIE => $this->_credentials['cookie'],
				CURLOPT_HEADER => true,

			)
		);
		$err = $this->_cr->execute();
		if ($err === 0) {
			$res = $this->_cr->getResponse($key);
			$this->_cr->clean();
			if ($raw) {
				return $res['response'];
			} else {
				$doc = new DOMDocument();
				@$doc->loadHTML($res['response']);
				$xpath = new DOMXpath($doc);
				$elements = $xpath->query('//*[@id="bandeau_contenu"]/table[2]/tr');

				$dates = array();
				foreach($elements as $row) {
					$row = $row->childNodes;
					switch($row->item(0)->nodeValue) {
						case ' zon.sunweb.be ':
							$dates['Sunweb'] = substr($row->item(2)->nodeValue, 1, 16);
							break;
						case 'Corendon.be':
							$dates['Corendon'] = substr($row->item(2)->nodeValue, 1, 16);
							break;
						case 'Jetair':
							$dates['Jetair'] = substr($row->item(2)->nodeValue, 1, 16);
							break;
						case 'Neckermann':
							$dates['Neckermann'] = substr($row->item(2)->nodeValue, 1, 16);
							break;
						case 'Sunjet':
							$dates['Sunjet'] = substr($row->item(2)->nodeValue, 1, 16);
							break;
						case 'ThomasCook':
							$dates['Thomascook'] = substr($row->item(2)->nodeValue, 1, 16);
							break;
					}

				}

				// Let's turn them into proper dates, mkay?

				foreach ($dates as $idx => $date) {
					$properData = explode(' ', $date);
					if (count($properData) !== 2) {
						unset($dates[$idx]);
					} else {
						$properdate = explode('/', $properData[0]);
						if (count($properdate) !== 3) {
							unset($dates[$idx]);
						} else {
							$dates[$idx] = $properdate[2] . '-' . $properdate[1] . '-' . $properdate[0] . ' ' . $properData[1] . ':00';
						}
					}

				}
				return $dates;
			}
		}
		$this->_log("Failed to fetch the last update dates " . print_r($this->_cr->getResponse(), 1), E_USER_ERROR);
		$this->_cr->clean();
		return array();
	}


	public function getAlternatives($raw = true) {
		$this->_getAuthenticationDetails();
		$key = $this->_cr->addRequest($this->endPoints['alternatives'], array(),
			array(
			), true,
			array(
				CURLOPT_FOLLOWLOCATION => false,
				CURLOPT_REFERER => $this->endPoints['overview'],
				CURLOPT_COOKIE => $this->_credentials['cookie'],
				CURLOPT_HEADER => true,

			)
		);
		$err = $this->_cr->execute();
		if ($err === 0) {
			$res = $this->_cr->getResponse($key);
			$this->_cr->clean();
			if ($raw) {
				return $res['response'];
			} else {
				throw new NotImplementedException("Parsed responses have not been implemented yet");
			}
		}
		$this->_log("Failed to fetch the alternatives mapping data " . print_r($this->_cr->getResponse(), 1), E_USER_ERROR);
		$this->_cr->clean();
		return null;
	}

	/**
	 * Get the available zones
	 */
	public function getZones()
	{
		$this->_getAuthenticationDetails();
		$key = $this->_cr->addRequest($this->endPoints['overview'], array(),
			array(
			), true,
			array(
				CURLOPT_FOLLOWLOCATION => false,
				CURLOPT_REFERER => $this->endPoints['login'],
				CURLOPT_COOKIE => $this->_credentials['cookie'],
				CURLOPT_HEADER => false,
			)
		);
		$err = $this->_cr->execute();
		if ($err === 0) {
			$res = $this->_cr->getResponse($key);
			$doc = new DOMDocument();
			@$doc->loadHTML($res['response']);

			$xpath = new DOMXpath($doc);
			$elements = $xpath->query('//*[@name="myzone"]/option');
			$nodes = array();
			foreach($elements as $node) {
				$value = $node->nodeValue;
				$value = explode("/", $value);
				if (count($value) == 2) {
					$key = $node->getAttribute('value');
					$nodes[] = array('country' =>  utf8_decode(array_shift($value)), 'city' => utf8_decode(array_shift($value)), 'pitobiId' => utf8_decode($key));
				}
			}
			if (count($nodes) > 0) {
				$this->_cr->clean();
				return $nodes;
			}
		}
		$this->_cr->clean();
		$this->_log("Failed to fetch the zonelist " . print_r($this->_cr->getResponse($key), 1), E_USER_ERROR);
		return null;
	}

	private function _log($message, $level) {
		call_user_func($this->_logCallback, $level, $message);

	}

}
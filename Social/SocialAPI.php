<?php

	namespace Sevenedge\Social;

	class SocialAPI {
		private $_errHandler = false;
		const PLATFORM = 'DEFAULT';
		const PAGINATION_PER_PAGE = 200;

		public function __construct($errHandler = null) {
			$this->_errHandler = is_callable($errHandler) ? $errHandler : function($msg, $level) {};
		}

		protected static function getCity($lat, $lng) {
			// Find country with Google Reverse Geocoding
			$googleJson = json_decode(file_get_contents('http://maps.googleapis.com/maps/api/geocode/json?latlng='.$lat.','.$lng.'&sensor=true', 0, null, null), 1);
			$googleData = array();
			if (isset($googleJson['results']['0']['address_components'])) {
				foreach($googleJson['results']['0']['address_components'] as $element){
					$googleData[implode(' ', $element['types'])] = $element['short_name'];
				}
				$country = $googleData['country political'];
			} else {
				$country = 'Unknown';
			}
			return $country;
		}


		protected function _log($message, $level = E_USER_NOTICE) {
			call_user_func($this->_errHandler, $level, $message);
		}

		public static function _calcDistance ($coord1, $coord2) {
			$φ1 = deg2rad($coord1['latitude']);
			$φ2 = deg2rad($coord2['latitude']);
			$Δφ = deg2rad($coord2['latitude']-$coord1['latitude']);
			$Δλ = deg2rad($coord2['longitude']-$coord1['longitude']);

			$a = sin($Δφ/2) * sin($Δφ/2) +
				cos($φ1) * cos($φ2) *
				sin($Δλ/2) * sin($Δλ/2);
			$c = 2 * atan2(sqrt($a), sqrt(1-$a));
			return 6378137 * $c;
		}
	}
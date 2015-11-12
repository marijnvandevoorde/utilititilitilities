<?php
namespace Sevenedge\Utilities\Geography;

class GoogleGeocodeApi {
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
}
<?php

namespace Sevenedge\Travel;
use Sevenedge\Utilities\CurlRequest;
use Sevenedge\Geography;

class Opentripplanner {

	private $_serviceURI;

	private static $_endpoints = array(
		'planroute' => '/routers/{routerId}/plan',
		'routerlist' => '/routers',
		'routerinfo' => '/routers/{routerId}',
		'routermeta' => '/routers/{routerId}/metadata',
		'agencies' => '/routers/{routerId}/index/agencies',
		'agencyinfo' => '/routers/{routerId}/index/agencies/{agencyId}',
		'routes' => '/routers/{routerId}/index/routes',
		'routeinfo' => '/routers/{routerId}/index/routes/{routeId}',
		'routestops' => '/routers/{routerId}/index/routes/{routeId}/stops',
		'routetrips' => '/routers/{routerId}/index/routes/{routeId}/trips',
		'stops' => '/routers/{routerId}/index/stops',
		'stopinfo' => '/routers/{routerId}/index/stops/{stopId}',
		'stopstoptimes' => '/routers/{routerId}/index/stops/{stopId}/stoptimes',
		'stoproutes' => '/routers/{routerId}/index/stops/{stopId}/routes',
		'trips' => '/routers/{routerId}/index/trips',
		'tripinfo' => '/routers/{routerId}/index/trips/{tripId}',
		'tripgeometry' => '/routers/{routerId}/index/trips/{tripId}/geometry',
		'tripstops' => '/routers/{routerId}/index/trips/{tripId}/stops',
		'tripstoptimes' => '/routers/{routerId}/index/trips/{tripId}/stoptimes',
	);

	private static $_defaultParams = array(
		'planroute' => array(
			'time' => '8:00am',
			'date' => '03-16-2015',
			'mode' => 'TRANSIT,WALK',
			'maxWalkDistance' => 2000,
			'arriveBy' => false,
			'wheelchair' => false,
			'showIntermediateStops' => false,
			'numItineraries' => 1,
			'walkReluctance' => 25,
			'waitReluctance' => 0.1, // i don't mind waiting reeaaally long
			'waitAtBeginningFactor' => 0.1 // i don't mind waiting really long for the first transit
		)
	);

	public function __construct($serviceURI) {
		$this->_serviceURI = rtrim($serviceURI, '/ ');
	}

	public function plan($params, $routerId = 'default') {
		$uri = self::$_endpoints['planroute'];
		$uri = str_replace('{routerId}', self::escapeParam($routerId), $uri);

		$params = array_merge(self::$_defaultParams['planroute'], $params);

		return $this->_call($uri, $params);
	}

	public function getRouters() {
		$uri = self::$_endpoints['routerlist'];

		$response = $this->_call($uri);
		if ($response) {
			$routers = array();
			foreach ($response['routerInfo'] as $router) {
				$routers[] = $router['routerId'];
			}
			return $routers;
		}
		return false;
	}

	public function unregisterAllRouters() {
		$uri = self::$_endpoints['routerlist'];

		return $this->_call($uri, array(), CurlRequest::METHOD_DELETE);
	}

	public function reloadAllRouters() {
		$uri = self::$_endpoints['routerlist'];

		return $this->_call($uri, array(), CurlRequest::METHOD_PUT);
	}


	public function getRouter($routerId) {
		$uri = self::$_endpoints['routerinfo'];
		$uri = str_replace('{routerId}', self::escapeParam($routerId), $uri);

		return $this->_call($uri);
	}

	public function getRouterMeta($routerId) {
		$uri = self::$_endpoints['routermeta'];
		$uri = str_replace('{routerId}', self::escapeParam($routerId), $uri);

		return $this->_call($uri);
	}

	public function getAgencies($routerId) {
		$uri = self::$_endpoints['agencies'];
		$uri = str_replace('{routerId}', self::escapeParam($routerId), $uri);

		return $this->_call($uri);
	}

	public function getAgency($routerId, $agencyId) {
		$uri = self::$_endpoints['agencyinfo'];
		$uri = str_replace('{routerId}', self::escapeParam($routerId), $uri);
		$uri = str_replace('{agencyId}', self::escapeParam($agencyId), $uri);

		return $this->_call($uri);
	}

	public function getRoutes($routerId) {
		$uri = self::$_endpoints['routes'];
		$uri = str_replace('{routerId}', self::escapeParam($routerId), $uri);

		return $this->_call($uri);
	}

	public function getRoute($routerId, $routeId) {
		$uri = self::$_endpoints['routeinfo'];
		$uri = str_replace('{routerId}', self::escapeParam($routerId), $uri);
		$uri = str_replace('{routeId}', self::escapeParam($routeId), $uri);

		return $this->_call($uri);
	}

	public function getRouteStops($routerId, $routeId) {
		$uri = self::$_endpoints['routestops'];
		$uri = str_replace('{routerId}', self::escapeParam($routerId), $uri);
		$uri = str_replace('{routeId}', self::escapeParam($routeId), $uri);

		return $this->_call($uri);
	}

	public function getRouteTrips($routerId, $routeId) {
		$uri = self::$_endpoints['routetrips'];
		$uri = str_replace('{routerId}', self::escapeParam($routerId), $uri);
		$uri = str_replace('{routeId}', self::escapeParam($routeId), $uri);

		return $this->_call($uri);
	}

	public function getStops($routerId) {
		$uri = self::$_endpoints['stops'];
		$uri = str_replace('{routerId}', self::escapeParam($routerId), $uri);

		return $this->_call($uri);
	}

	public function getStop($routerId, $stopId) {
		$uri = self::$_endpoints['stopinfo'];
		$uri = str_replace('{routerId}', self::escapeParam($routerId), $uri);
		$uri = str_replace('{stopId}', self::escapeParam($stopId), $uri);

		return $this->_call($uri);
	}

	public function getStopStoptimes($routerId, $stopId) {
		$uri = self::$_endpoints['stopstoptimes'];
		$uri = str_replace('{routerId}', self::escapeParam($routerId), $uri);
		$uri = str_replace('{stopId}', $stopId, $uri);

		return $this->_call($uri);
	}

	public function getStopRoutes($routerId, $stopId) {
		$uri = self::$_endpoints['stoproutes'];
		$uri = str_replace('{routerId}', self::escapeParam($routerId), $uri);
		$uri = str_replace('{stopId}', self::escapeParam($stopId), $uri);

		return $this->_call($uri);
	}

	public function getTrips($routerId) {
		$uri = self::$_endpoints['trips'];
		$uri = str_replace('{routerId}', self::escapeParam($routerId), $uri);

		return $this->_call($uri);
	}

	public function getTrip($routerId, $tripId) {
		$uri = self::$_endpoints['tripinfo'];
		$uri = str_replace('{routerId}', self::escapeParam($routerId), $uri);
		$uri = str_replace('{tripId}', self::escapeParam($tripId), $uri);

		return $this->_call($uri);
	}

	public function getTripGeometry($routerId, $tripId) {
		$uri = self::$_endpoints['tripgeometry'];
		$uri = str_replace('{routerId}', self::escapeParam($routerId), $uri);
		$uri = str_replace('{tripId}', self::escapeParam($tripId), $uri);

		return $this->_call($uri);
	}

	public function getTripStops($routerId, $tripId) {
		$uri = self::$_endpoints['tripstops'];
		$uri = str_replace('{routerId}', self::escapeParam($routerId), $uri);
		$uri = str_replace('{tripId}', self::escapeParam($tripId), $uri);

		return $this->_call($uri);
	}

	public function getTripStoptimes($routerId, $tripId) {
		$uri = self::$_endpoints['tripstoptimes'];
		$uri = str_replace('{routerId}', self::escapeParam($routerId), $uri);
		$uri = str_replace('{tripId}', self::escapeParam($tripId), $uri);

		return $this->_call($uri);
	}


	public function unregisterRouter($routerId) {
		$uri = self::$_endpoints['routerinfo'];
		$uri = str_replace('{routerId}', self::escapeParam($routerId), $uri);

		return $this->_call($uri, array(), CurlRequest::METHOD_DELETE);

	}

	public function registerRouter($routerId) {
		$uri = self::$_endpoints['routerinfo'];
		$uri = str_replace('{routerId}', self::escapeParam($routerId), $uri);

		return $this->_call($uri, array(), CurlRequest::METHOD_PUT);
	}

	private static function escapeParam($param) {
		$param = rawurlencode($param);
		return str_replace("%22", "\"", $param);
	}


	private function _call($uri, $params = array(), $method = CurlRequest::METHOD_GET) {
		$uri = $this->_serviceURI . $uri;

		$cr = new CurlRequest();
		$key = $cr->addRequest($uri, $params);
		if ($method !== CurlRequest::METHOD_GET) {
			$cr->setRequestMethod($method);
		}
		$result = $cr->execute();
		if ($result === 0) {
			// success!!!
			$response = $cr->getResponse($key);
			if ($response['http_code'] === 200) {
				$response = json_decode($response['response'], 1);
				return $response;
			}
		}
		return false;

	}

}
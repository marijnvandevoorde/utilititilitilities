<?php

	namespace Sevenedge\Social;

	use Sevenedge\Utilities;

	class SimpleGramAPI  extends SocialAPI
	{
		const PLATFORM = 'INSTAGRAM';
		const API_ROOT = 'https://api.instagram.com/v1/';
		const MAX_PAGES = 50;


		private $_clientId, $_clientSecret, $_credentials = false, $_connection = false;

		private static $_endpoints = array(
			'search' =>  'tags/{TAG}/media/recent',
			'coordinatesbylocation' => 'locations/{LOCATIONID}'
		);

		public function __construct($clientId, $clientSecret, $credentials = false, $errorHandler = null) {
			parent::__construct($errorHandler);
			$this->_clientId = $clientId;
			$this->_clientSecret = $clientSecret;
			if (is_array($credentials) && !empty($credentials)) {
				$this->_log("Credentials are not implemented atm in the simple api and probably never will be. APP key will be used and hence app rate limits apply.", E_USER_WARNING);
				$this->_credentials = $credentials;
			}
		}

		private function _getConnection($reset = false) {
			if ($reset || !$this->_connection) {
				$this->_connection = new Utilities\CurlRequest();
			}
			return $this->_connection;
		}

		/**
		 * @param $hashTag single hashtag to search for
		 * @param bool $filters array of filters.
		 * @param bool $afterId only fetch media after the given id. !!!! This id is updated after the call with the max encountered id;
		 * @param int $limit max amount here
		 * @return array
		 */
		public function fetchByTag($hashTag, $filters = false, &$afterId = false, $limit = self::PAGINATION_PER_PAGE)
		{
			$hashTag = ltrim($hashTag, '#');
			$oldest = $afterId;
			if ($afterId) {
				$afterId = explode('_', $afterId);
				$oldest = intval($afterId[0]);
			}
			$endpoint = self::API_ROOT . str_replace("{TAG}", $hashTag, self::$_endpoints['search']);
			$params = array('client_id' => $this->_clientId);
			$page_limit = $limit > self::PAGINATION_PER_PAGE ? self::PAGINATION_PER_PAGE : $limit;
			$params['count'] = $page_limit;

			$connection = $this->_getConnection();
			$key = $connection->addRequest($endpoint, $params);
			$this->_log($params);

			$result = $connection->execute();
			if ($result !== 0) {
				throw new \BadRequestException ('Something went wrong during the instagram request');
			}
			$result = $connection->getResponse($key);
			$connection->clean();
			$result = json_decode($result['response'], 1);

			if (!isset($result['meta']) || !isset($result['meta']['code'])) {
				throw new \BadRequestException("Weird response. No clue");
			} elseif ($result['meta']['code'] !== 200) {
				throw new \HttpException("Bad response code. Shouldb be 200 but was {$result['meta']['code']}");
			}

			// if there's no new data, just throw back that empty array.
			if (empty($result['data'])) {
				return $result['data'];
			}
			// update afterid
			//maybe even higher!
			if ($result['data'][0]['comments']['count'] > 0) {
				//the last comment might and probably will have a higher id here
				$afterId  = $result['data'][0]['comments']['data'][$result['data'][0]['comments']['count']-1]['id'];
			} else {
				$afterId = $result['data'][0]['id'];
			}
			$media = array();
			$page = 1;
			while ($page++ < self::MAX_PAGES) {
				foreach ($result['data'] as $ig) {
					// user['username'] user['profile_picture'], user['full_name'], user['id'], user['bio']
					// images['low_resolution' / "thumbnail" / "standard_resolution", each of them has url, width & height when type === 'image'
					// videos[standard_resolution, low_bandwidth and low_resolution], all of them url (mp4, width & height when type === 'video'
					if (is_array($filters)) {
						if (isset($filters['location']) && !empty($filters['location'])) {
							// location filtering
							if (empty($ig['location'])) {
								continue;
							} else {
								//fetch coordinates if not available!
								if (!isset($ig['location']['langitude'])) {
									if (isset($ig['location']['id'])) {
										try {
											$ig['location'] = array_merge($ig['location'], $this->fetchCoordinatesForLocation($ig['location']['id']));
										} catch (\Exception $e) {
											continue;
										}
									} else {
										continue;
									}
								}
								if(self::_calcDistance($filters['location'],$ig['location']) > $filters['location']['radius']) {
									continue;
								}
							}
						}
						if (isset($filters['type'])) {
							if ($ig['type'] === $filters['type'] || (is_array($filters['type']) && in_array($ig['type'], $filters['type']))) {
								// OK. Doing it like this because it's a tiny bit lazier.
							} else {
								continue;
							}
						}
					}

					$medium = array(
						'username' => $ig['user']['username'],
						'fullname' => $ig['user']['full_name'],
						'userbio' => $ig['user']['bio'],
						'userimage' => preg_replace('/^[^\/]+\/\//', '//', $ig['user']['profile_picture']),
						'id' => $ig['id'],
						'date' => date('Y-m-d H:i:s', $ig['created_time']),
						'type' => $ig['type'],
						'external_url' => preg_replace('/^[^\/]+\/\//', '//', $ig['link']),
						'image' => preg_replace('/^[^\/]+\/\//', '//', $ig['images']['standard_resolution']['url']),
						'caption' => $ig['caption']['text'],
						'hashtag' => $hashTag
					);
					if ($ig['type'] === 'video') {
						$medium['video'] = preg_replace('/^[^\/]+\/\//', '//', $ig['videos']['standard_resolution']['url']);
					}
					if (!empty($ig['location'])) {
						// just copy. if it's an id you can do geomapping later on.
						$medium['location'] = $ig['location'];
					}
					/*$check = $this->checkMedia($medium);
					if ($check['success']) { */
					$media[] = $medium;
					//}

					if (count($media) >= $limit) {
						// we reached the asked amount. finish up!
						break 2;
					}
				}

				if (!isset($result['pagination']['next_max_tag_id']) || ($oldest && intval($result['pagination']['next_max_tag_id']) < $oldest)) {
					break;
				}
				// if we got here, we didn't get enough data yet. ask moar!
				$this->_log('Simplegram: so far, I have ' . count($media) . ' results. fetching moar, starting at ' .  $result['pagination']['next_max_tag_id'], E_USER_NOTICE);


				$params['max_tag_id'] = $result['pagination']['next_max_tag_id'];
				$key = $connection->addRequest($endpoint, $params);
				$result = $connection->execute();
				if ($result !== 0) {
					throw new \BadRequestException ('Something went wrong during the instagram request');
				}
				$result = $connection->getResponse($key);
				$connection->clean();
				$result = json_decode($result['response'], 1);
				// let's not be as harsh this time and keep the data we already collected.
				if (!isset($result['meta']) || !isset($result['meta']['code'])) {
					break;
				} elseif ($result['meta']['code'] !== 200) {
					break;
				}

				// if there's no new data, we should not bother going on.
				if (empty($result['data'])) {
					break;
				}
				$this->_log($params);
			}

			return $media;
		}

		public function fetchCoordinatesForLocation($locationId) {
			$endpoint = self::API_ROOT . str_replace("{LOCATIONID}", $locationId, self::$_endpoints['coordinatesbylocation']);
			$params = array('client_id' => $this->_clientId);
			$connection = $this->_getConnection();
			$key = $connection->addRequest($endpoint, $params);
			$result = $connection->execute();
			if ($result !== 0) {
				throw new \BadRequestException ('Something went wrong during the instagram request');
			}
			$result = $connection->getResponse($key);
			$connection->clean();
			$result = json_decode($result['response'], 1);
			if (!isset($result['meta']) || !isset($result['meta']['code'])) {
				throw new \BadRequestException("Weird response. No clue");
			} elseif ($result['meta']['code'] !== 200) {
				throw new \HttpException("Bad response code. Shouldb be 200 but was {$result['meta']['code']}");
			} elseif (empty($result['data']['latitude'])) {
				throw new \NotFoundException("Seriously, I have no coordinates for this location");
			}
			return array('latitude' => $result['data']['latitude'], 'longitude' => $result['data']['longitude']);
		}

		public function checkMedia($post) {
			// doesn't really have anything to do with the instagram api, but it's a nice feat since media tends to disappear. And doesn't affect the rate limits.
			$connection = $this->_getConnection();
			$result = array('success' => true, 'errors' => array());
			$keys = array();
			foreach (array('userimage', 'external_url', 'image', 'video') as $key) {
				if (isset($post[$key])) {
					$keys[$key] = $connection->addRequest(
						preg_replace('/^[^\/]*\/\//', 'https://', $post[$key]),
						array(), array(), true,
						array(CURLOPT_HEADER => true, CURLOPT_NOBODY => true)
					);
				}
			}

			if (!empty($keys)) {
				$connection->execute();
				foreach ($keys as $item => $key) {
					$response = $connection->getResponse($key);
					if ($response['http_code'] !== 200) {
						$result['success'] = false;
						$result['errors'][] = $item;
					}
				}
				$connection->clean();
			}
			return $result;
		}

	}
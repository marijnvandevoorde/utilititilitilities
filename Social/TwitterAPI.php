<?php

namespace Sevenedge\Social;

use Abraham\TwitterOAuth\TwitterOAuth;


class TwitterAPI extends SocialAPI {
	const PLATFORM = 'TWITTER';

	private $_connection, $_credentials;

	public function __construct($credentials, $errorHandler = false) {
		parent::__construct($errorHandler);
		$this->_credentials = $credentials;
	}

	private function _getConnection() {
		if (!$this->_connection) {
			$this->_connection = new TwitterOAuth(
				$this->_credentials['consumerkey'],
				$this->_credentials['consumersecret'],
				$this->_credentials['accesstoken'],
				$this->_credentials['accesstokensecret']
			);
			$this->_connection->setTimeout(30);
			$this->_connection->setConnectionTimeout(30);
		}
		return $this->_connection;
	}


	public function updateStatus($tweet, $reply_to_id = false) {
		$postParams = array('status' => $tweet);
		if ($reply_to_id) {
			$postParams['in_reply_to_status_id'] = $reply_to_id;
		}
		$response = $this->_getConnection()->post("statuses/update", $postParams);
	}

	public function fetchStatusses($hashtags, $filters = false, $since = false, $limit = self::PAGINATION_PER_PAGE) {
		if (is_array($hashtags)) {
			$hashtags = implode(' OR ', $hashtags);
		}

		// 10 times as much because there's only very few that actually have a picture.
		$page_limit = ($limit * 10) < self::PAGINATION_PER_PAGE ? ($limit * 10) : self::PAGINATION_PER_PAGE;
		$params = array('q' => $hashtags, 'result_type' => 'recent', 'count' => $page_limit);
		if ($since) {
			$params['since_id'] = $since;
		}
		if (isset($filters['location'])) {
			$params['geocode'] = "{$filters['location']['latitude']},{$filters['location']['longitude']},{$filters['location']['radius']}km";
		}

		// fetch fetch fetch!
		$content = $this->_getConnection()->get("search/tweets", $params);

		// for pagination: $content->search_metadata->next_results for query string (so no more params then!!!)
		// or if you want to use params: search_metadata->max_id -1!!!!!!

		$media = array();
		if (is_array($filters) && isset($filters['type'])) {
			if (!is_array($filters['type'])) {
				$filters['type'] = array($filters['type']);
			}
		}
		while (1) {
			foreach ($content->statuses as &$tweet) {
				$type = 'status';
				$photo_url = null;
				$video_url = null;
				if(isset($tweet->entities->media)) {
					foreach ($tweet->entities->media as $medium) {
						if ($medium->type === 'photo') {
							$type = 'image';
							$photo_url = preg_replace('/^[^\/]+\/\//', '//', $medium->media_url_https);
							break; // TODO: there's just photo atm. if ever, there is a type with higher priority, like video, replace this by "continue;"
						} elseif ($medium->type === 'video') {
							$type = 'video';
							$video_url = preg_replace('/^[^\/]+\/\//', '//', $medium->media_url_https);
							break;
						}
					}
				}

				if (is_array($filters)) {
					if (isset($filters['type'])) {
						if ($type === $filters['type'] || (is_array($filters['type']) && in_array($type, $filters['type']))) {
							// OK. Doing it like this because it's a tiny bit lazier.
						} else {
							continue;
						}
					}
					if (isset($filters['retweets']) && $filters['retweets'] === false && isset($tweet->retweeted_status)) {
						continue;
					}
				}


				$medium = array(
					'username' => $tweet->user->screen_name,
					'fullname' => $tweet->user->name,
					'userbio' => $tweet->user->description,
					'userimage' => preg_replace('/^[^\/]+\/\//', '//', $tweet->user->profile_image_url_https),
					'id' => $tweet->id,
					'date' => date('Y-m-d H:i:s', strtotime($tweet->created_at)),
					'type' => $type,
					'external_url' => '//twitter.com/' . $tweet->user->screen_name . '/status/' . $tweet->id,
					'image' => $photo_url,
					'caption' => $tweet->text,
					'lang' => $tweet->lang
				);
				if ($type === 'video') { // TODO THIS WILLL NEVER HAPPEN ATM, BUT IF TWITTER EVER STARTS ADDING VIDEO... HANDLE THAT SHIT
					$medium['video'] = $video_url;
				}
				// geo or coordinates
				if (!empty($tweet->geo)) {
					$medium['location'] = array('latitude' => $tweet->geo->coordinates[0], 'longitude' => $tweet->geo->coordinates[1]);
				}
				elseif (!empty($tweet->coordinates)) {
					$medium['location'] = array('latitude' => $tweet->coordinates->coordinates[1], 'longitude' => $tweet->coordinates->coordinates[0]);
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

			// if we got here, we didn't get enough data yet. ask moar!
			// well, at least if twitter didn't run out of statusses
			if (count($content->statuses) < $page_limit) {
				break;
			}

			// get the last id
			$last = end($content->statuses);
			$params['max_id'] = $last->id - 1;
			$content = $this->_getConnection()->get("search/tweets", $params);

			// if there's no new data, we should not bother going on.
			if (empty($content->statuses)) {
				break;
			}
		}
		return $media;
	}


}
<?php

namespace Sevenedge\Utilities\Social;

use Sevenedge\Utilities\Utilities;


/**
 * Class Spotifapi
 * @author Marijn Vandevoorde <marijn@marijnworks.be>
 * @link http://www.marijnworks.be
 *
 * Magical spotify api wrapper. you can login and pretty much do everything you want. only thing is that you have to authorize your account once. (this can be done by generating the landingurl below
 *
 * Instructions, well most of this should be self-explaning. One thing though: before you can use it, you have to authorize your app in the browser. to do this, generate an auth url using
 * Spotifapi::generateAuthorizationURI(clientId, scopes = array('scope1','scope2',...), 'callbackuri');
 * the callback uri must match one in the settings of the app! you have to add this in the settings on the spotify develop site or it won't work.
 *
 * The current set of endpoints is somewhat limited, but you can add more yourself:
 * Check out the documentation here: https://developer.spotify.com/web-api/endpoint-reference/
 * And check the methods below as a reference.
 *
 */
class InstagramAPI extends SocialAPI
{
	private $clientId, $clientSecret, $redirect_uri, $scope, $credentials;
	private $cr, $logCallback;

	/**
	 * @var array some basic urls and other stuff that kind of works like templates. client_id, redirect_uri,... will be replaced in this. should be done on boot so it only has to be done once
	 */
	private static $templates = array(
		'landinguri' => 'https://instagram.com/accounts/login/',
		'loginuri' => 'https://instagram.com/accounts/login/ajax/',
		'authenticationuri' => 'https://api.instagram.com/oauth/authorize/?client_id={client_id}&redirect_uri={redirect_uri}&response_type=code',
		//'authenticationuri' => 'https://accounts.spotify.com/authorize/?client_id={client_id}&redirect_uri={redirect_uri}&response_type=code&scope={scope}&show_dialog=false&state=',
		'accesstokenuri' => 'https://accounts.spotify.com/api/token',
		'cookiesuffix' => '; fb_continue=https%3A%2F%2Faccounts.spotify.com%2Fauthorize%2F%3Fclient_id%3D{client_id}%26redirect_uri%3D{redirect_uri}%26response_type%3Dcode%26scope%3D{scope}%26show_dialog%3Dfalse%26state%3D; _ga=GA1.2.1321090406.1408544984; _dc=1";'
	);

	/**
	 * @var array list of endpoints for api calls. again, works like a template. user_id, playlist_id, track_id has to be replaced here. do it on call. no need to do them all when only using one or two.
	 */
	private $endPoints = array(
		'createPlaylist' => 'https://api.spotify.com/v1/users/{user_id}/playlists',
		'managePlaylist' => 'https://api.spotify.com/v1/users/{user_id}/playlists/{playlist_id}/tracks',
		'refreshAccessToken' => 'https://accounts.spotify.com/api/token'
	);

	/**
	 * @param $clientId app id
	 * @param $clientSecret app secret
	 * @param $redirect_uri redirect url of the app. should be in the list!!!!!
	 * @param array $scope array of scopes your app requires.
	 * @param $credentials: array with username, password and maybe acces token and expiry time if already obtained
	 * @param $logCallback: should be a method that takes 2 paramters. 1 is the level (E_WARN, E_ERROR,... you know, the php error levels. 2 is the message.
	 * 				FYI: the ones used are E_USER_NOTICE for debug, E_USER_WARNING for... warning and E_USER_ERROR for booboos
	 * @throws Exception throws a good old exception when the access token could not be obtained. no other details, it either works or doesn't.
	 */
	public function __construct($clientId, $clientSecret, $redirect_uri, $scope = array(), $credentials, $errHandler = null)
	{
		parent::__construct($errHandler);
		$this->clientId = $clientId;
		$this->clientSecret = $clientSecret;
		$this->redirect_uri = $redirect_uri;
		$this->scope = $scope;
		$this->credentials = $credentials;
		$this->cr = new Marijnworks\CurlRequest();

		//filling out the templates
		$authenticationuri = str_replace("{client_id}", $this->clientId, self::$templates['authenticationuri']);
		self::$templates['authenticationuri'] = str_replace("{redirect_uri}", urlencode($this->redirect_uri), $authenticationuri);

		$cookiesuffix = str_replace("{client_id}", $this->clientId, self::$templates['cookiesuffix']);
		$cookiesuffix = str_replace("{scope}", urlencode(implode("+", $this->scope)), $cookiesuffix);
		self::$templates['cookiesuffix'] = str_replace("{redirect_uri}", urlencode(urlencode($this->redirect_uri)), $cookiesuffix);

		$this->_getAuthenticationDetails();
	}


	public static function generateAuthorizationURI($clientId, $scope = array(), $redirect_uri, $scope = array())
	{
		$authenticationuri = str_replace("{client_id}", $clientId, self::$templates['authenticationuri']);
		$authenticationuri = self::$templates['authenticationuri'] = str_replace("{redirect_uri}", urlencode($redirect_uri), $authenticationuri);
		if (!empty($scope)) {
			$authenticationuri .= "&scope=" . implode("+", $scope);
		}
		return $authenticationuri;
	}

	/**
	 * private helper method to get an access token.
	 * @throws Exception when the access token could not be obtained. not much details here. it either works or it doesn't. don't feel like working it all out to the details.
	 */
	private function _getAuthenticationDetails()
	{
		if (isset($this->credentials['access_token'])) {
			if ($this->credentials['valid_until'] > time()) {
				$this->_log("using the cached access token");
				return $this->credentials;
			} else if (isset($this->credentials['refresh_token'])) {
				// we have a refresh token, so let's try it that way!
				$key = $this->cr->addRequest($this->endPoints['refreshAccessToken'], array(
					'grant_type' => 'refresh_token',
					'refresh_token' => $this->credentials['refresh_token']
				),
					array(
						'Authorization: Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret)

					), true,
					array(
						CURLOPT_POST => true
					)
				);
				$err = $this->cr->execute();
				if ($err === 0) {
					$res = $this->cr->getResponse($key);
					$this->cr->clean();
					$res = json_decode($res['response'], 1);
					$this->credentials = array_merge($this->credentials, $res);
					$this->credentials['valid_until'] = time() + $this->credentials['expires_in'];
					$this->_log("was able to get a new access token with the refresh token");
					return $this->credentials;
				}
				$this->cr->clean();
			}
		}
		$this->_log("current token expired and unable to get a new one with the refresh token. trying to log in", E_USER_WARNING);
		// step one: we need that cookie & csrf to bypass the cross site request forgery checks. we be stealthy!
		$key = $this->cr->addRequest(self::$templates['landinguri'],
			null, array(), true,
			array(
				CURLOPT_FOLLOWLOCATION => false,
				CURLOPT_REFERER => $this->redirect_uri,
				CURLOPT_HEADER => true,
				CURLOPT_COOKIESESSION => true
			)
		);

		$err = $this->cr->execute();
		if ($err === 0) {
			// well that went fine. let's fetch the cookie!
			$res = $this->cr->getResponse($key);
			$this->cr->clean();
			$matches = array();
			$matchcount = preg_match('/^Set-Cookie: (\s*([^;]*))/mi', $res['header'], $matches);
			if ($matchcount) {
				$cookie = $matches[1] . self::$templates['cookiesuffix'];

				$matchcount = preg_match('/csrftoken=([^;]+);?.*$/mi', $matches[1], $matches);

				if ($matchcount) {
					$csrf = $matches[1];
					$headers = array(
						'X-CSRFToken: ' . $csrf,
						'X-Instagram-AJAX: 1',
						'X-Requested-With: XMLHttpRequest'
					);

					// next up, we are going to log in using that cookie & csrf token.
					$key = $this->cr->addRequest(self::$templates['loginuri'],
						array(
							'username' => $this->credentials['username'],
							'password' => $this->credentials['password'],
							'intent' => $csrf
						), $headers, true,
						array(
							CURLOPT_FOLLOWLOCATION => false,
							CURLOPT_REFERER => 'https://instagram.com/accounts/login/ajax/?targetOrigin=https%3A%2F%2Finstagram.com',
							CURLOPT_COOKIE => $cookie,
							CURLOPT_HEADER => true,
							CURLOPT_POST => true,
						)
					);
					$err = $this->cr->execute();

					if ($err === 0) {
						// (using jeff bridges' voice in tron legacy: https://www.youtube.com/watch?v=tFXYuw96d0c) "we got in!"
						$res = $this->cr->getResponse($key);
						$this->cr->clean();

						//we should have received more cookies to set now. let's keep them over the next requests
						$matches = array();
						$matchcount = preg_match_all('/^Set-Cookie: (\S*)/mi', $res['header'], $matches);

						if ($matchcount) {
							$cookie =  implode("; ", $matches[1]);
							// let's authorize the shit out of this!
							$key = $this->cr->addRequest(self::$templates['authenticationuri'],
								array(

								), array(), true,
								array(
									CURLOPT_FOLLOWLOCATION => false,
									CURLOPT_REFERER => self::$templates['landinguri'],
									CURLOPT_COOKIE => $cookie,
									CURLOPT_HEADER => true,
									CURLOPT_POST => false
								)
							);
							$err = $this->cr->execute();
							if ($err === 0) {
								$res = $this->cr->getResponse($key);
								//oh yes! this request gave us the redirect url with the "code" paramter which we need. in a browser, this would redirect to our redirect_uri with ?code=xxxx attached.
								$this->cr->clean();
								//all we need is that code.


								if (isset($res['redirect_url']))
								{
									$res = array('redirect' => $res['redirect_url']);
								} else {
									$res = json_decode($res['response'], 1);
								}

								$matchcount = preg_match("/code=([^&]+)&?.*$/", $res['redirect'], $matches);
								if ($matchcount) {

									$code = $matches[1];
									// we have a code! now with that code, our application can ask for an access token. let's go! we have to pass the code and our application info.
									// redirect uri has to be passed as well, even though no redirect will take place. it's euhm, yeah, something.
									// grant_type should be authorization_code. just because.
									$key = $this->cr->addRequest(self::$templates['accesstokenuri'],
										array(
											'grant_type' => 'authorization_code',
											'code' => $code,
											'redirect_uri' => $this->redirect_uri,
											'client_id' => $this->clientId,
											'client_secret' => $this->clientSecret
										), array(), true,
										array(
											CURLOPT_FOLLOWLOCATION => false,
											CURLOPT_REFERER => $this->redirect_uri,
											CURLOPT_COOKIE => $cookie,
											CURLOPT_HEADER => false,
											CURLOPT_POST => true
										)
									);
									$err = $this->cr->execute();
									if ($err === 0) {
										// hella yes! we got that access token! let's store it!
										$res = $this->cr->getResponse($key);
										$this->cr->clean();
										$res = json_decode($res['response'], 1);

										$this->credentials = array_merge($this->credentials, $res);
										$this->credentials['valid_until'] = time() + $this->credentials['expires_in'];
										return $this->credentials;
									}
								}
							}
						}
					}
				}
			}
		}
		// if we go there, it means something went wrong along the road. debug yourself, i don't feel like catching all possible exceptions atm.
		$this->cr->clean();

		$this->_log("Failed to get an access token in any possible way", E_USER_ERROR);
		throw new Exception("something went terribly, terribly wrong");
	}

	/**
	 * just getting those authenticationdetails so we can save the authenticationtoken etc.
	 * @return an array containing the original authentication details, supplemented with the acces token etc.
	 */
	public function getAuthenticationDetails() {
		$details = $this->_getAuthenticationDetails();
		return $details;
	}

	/**
	 * simple method to create a new playlist for the logged in user.
	 * @param $name name of the playlist to create
	 * @param $public should it be public or not. default true
	 * @return the playlist id if it worked, null if it didn't work.
	 */
	public function createList($name, $public = true)
	{
		$endpoint = str_replace('{user_id}', $this->credentials['username'], $this->endPoints['createPlaylist']);

		$this->_getAuthenticationDetails();
		$key = $this->cr->addRequest($endpoint, json_encode(array(
			'name' => $name,
			'public' => $public
		)),
			array(
				'Authorization: Bearer ' . $this->credentials['access_token'],
				'Content-Type: application/json'

			), true,
			array(
				CURLOPT_POST => true
			)
		);
		$err = $this->cr->execute();
		if ($err === 0) {
			$res = $this->cr->getResponse($key);
			$this->cr->clean();
			$res = json_decode($res['response'], 1);
			return $res['id'];
		}

		$this->_log("Failed to create a new list called '$name'. response was " . print_r($this->cr->getResponse(), 1), E_USER_ERROR);
		$this->cr->clean();
		return null;
	}

	/**
	 * Get the tracks of the given playlist
	 * @param $listId the playlist id
	 * @return the tracklist. or null if it failed.
	 */
	public function getPlaylistTracks($listId)
	{
		$endpoint = str_replace('{user_id}', $this->credentials['username'], $this->endPoints['managePlaylist']);
		$endpoint = str_replace('{playlist_id}', $listId, $endpoint);

		$this->_getAuthenticationDetails();

		$key = $this->cr->addRequest($endpoint, array(),
			array(
				'Authorization: Bearer ' . $this->credentials['access_token'],
				'Content-Type: application/json'

			), true,
			array(
			)
		);
		$err = $this->cr->execute();
		if ($err === 0) {
			$res = $this->cr->getResponse($key);
			$this->cr->clean();
			$res = json_decode($res['response'], 1);
			return $res['items'];
		}

		$this->_log("Failed to get the playlist $listId tracks. response was " . print_r($this->cr->getResponse(), 1), E_USER_ERROR);
		$this->cr->clean();
		return null;
	}

	/**
	 * add some tracks to the given playlist
	 * @param $listId the id of the list
	 * @param $trackIds an array of spotify:track:xxxxxx id's.
	 * @return bool true if it worked, false if it didn't. doesn't always have to be complicated.
	 */
	public function addTracksToPlaylist($listId, $trackIds)
	{
		if (empty($trackIds)) { return false;}
		$endpoint = str_replace('{user_id}', $this->credentials['username'], $this->endPoints['managePlaylist']);
		$endpoint = str_replace('{playlist_id}', $listId, $endpoint);

		$this->_getAuthenticationDetails();
		$this->cr->addRequest($endpoint, json_encode($trackIds),
			array(
				'Authorization: Bearer ' . $this->credentials['access_token'],
				'Content-Type: application/json'

			), true,
			array(
				CURLOPT_POST => true
			)
		);
		$err = $this->cr->execute();
		if ($err === 0) {
			$this->cr->clean();
			return true;
		}
		call_user_func($this->logCallback, E_USER_ERROR, "Failed to add tracks to the list $listId. response was " . print_r($this->cr->getResponse(), 1));
		$this->cr->clean();
		return false;
	}

	public function removeTracksFromPlaylist($listId, $trackIds)
	{
		if (empty($trackIds)) { return false;}
		$endpoint = str_replace('{user_id}', $this->credentials['username'], $this->endPoints['managePlaylist']);
		$endpoint = str_replace('{playlist_id}', $listId, $endpoint);

		// the spotify api has us create this stupid object-ish array
		$tracks = array();
		foreach ($trackIds as $id)
		{
			$tracks[] = array('uri' => $id);
		}
		$tracks = array('tracks' => $tracks);

		$this->_getAuthenticationDetails();

		$this->cr->addRequest($endpoint, json_encode($tracks),
			array(
				'Authorization: Bearer ' . $this->credentials['access_token'],
				'Content-Type: application/json'

			), true,
			array(
				CURLOPT_CUSTOMREQUEST => CurlRequest::METHOD_DELETE
			)
		);
		$err = $this->cr->execute();
		if ($err === 0) {
			$this->cr->clean();
			return true;

		}
		call_user_func($this->logCallback, E_USER_ERROR, "Failed to remove tracks to the list $listId. response was " . print_r($this->cr->getResponse(), 1));
		$this->cr->clean();
		return false;
	}

	public function replaceTracksInPlaylist($listId, $trackIds)
	{
		if (empty($trackIds)) { return false;}
		$endpoint = str_replace('{user_id}', $this->credentials['username'], $this->endPoints['managePlaylist']);
		$endpoint = str_replace('{playlist_id}', $listId, $endpoint);
		// oh yes, why not yet another way to pass a list of track id's, spotify? great idea!

		$this->_getAuthenticationDetails();
		$this->cr->addRequest($endpoint, json_encode(array('uris' => $trackIds)),
			array(
				'Authorization: Bearer ' . $this->credentials['access_token'],
				'Content-Type: application/json'

			), true,
			array(
				CURLOPT_CUSTOMREQUEST => CurlRequest::METHOD_PUT
			)
		);
		$err = $this->cr->execute();
		if ($err === 0) {
			$this->cr->clean();
			return true;

		}
		call_user_func($this->logCallback, E_USER_ERROR, "Failed to replace tracks in the list $listId. response was " . print_r($this->cr->getResponse(), 1));
		$this->cr->clean();
		return false;
	}

}
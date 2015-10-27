<?php

/**
 * Class CurlRequest
 * @author Marijn Vandevoorde <marijn@marijnworks.be>
 * @link http://www.marijnworks.be
 *
 * Just a class to make curl requests a bit easier, especially concurrent ones.
 * Supports the multi curl request features.
 * There's a lot of stuff lacking but I just created this to fit my own use.
 * Feel free to fork or send pull requests for any additional stuff you might find lacking.
 * I'll be happy to share authorship
 */
namespace Sevenedge\Lib\Utilities;

class CurlRequest
{
	const
		METHOD_GET = 'GET',
		METHOD_POST = 'POST',
		METHOD_HEAD = 'HEAD',
		METHOD_DELETE = 'DELETE',
		METHOD_PUT = 'PUT',
		METHOD_PURGE = 'PURGE',

		DEFAULT_METHOD = self::METHOD_GET,
		DEFAULT_FOLLOWLOCATION = true,
		DEFAULT_MAXREDIRS = 10,
		DEFAULT_FETCHHEADER = false,
		DEFAULT_FETCHCONTENT = true,
		DEFAULT_USERAGENT = 'Mozilla/5.0 (Windows NT 6.3; Trident/7.0; rv:11.0) like Gecko'; // IE11. less updates, less prone to changes.

	private $_handles = array();
	private $_responses = array();
	private $_handle = null;
	private $_params = null;
	private $_headers = null;
	private $_url = null;
	private $_method = self::METHOD_GET;
	private $_errno = null;
	private $_error = null;
	private $_debug = false;
	private $_options = array();
	private $_key = null;


	/**
	 * Construct this instance and prep the first request
	 */
	public function __construct($debug = false)
	{
		$this->_debug = $debug;
	}

	/**
	 * Finalizes the current open request (if any) and adds & Initializes a new one
	 * @param $url: the url to fetch
	 * @param $params: array of request parameters. the way they are sent will depend on the request method set for this request
	 * @param $headers: custom curl request headers to be set here
	 * @param $loadDefaults: boolean indicating whether to load the default curl options. true by default
	 * @param $optionOverrides: array of curl options to set. This is done after the loading of defaults, so they will be overwritten if used.
	 * @return $unique identifier, a key for this curl request you can use to lookup responses later.
	 */
	public function addRequest($url, $params = array(), $headers = array(), $loadDefaults = true, array $optionOverrides = array(), $callback = false, $callbackparams = array())
	{
		if (!is_null($this->_handle))
		{
			$this->_finalizeRequest();
		}

		$key = $this->_init($url, $params, $headers, $loadDefaults, $optionOverrides, $callback, $callbackparams);
		return $key;
	}

	/**
	 * Private init method. Initializes a request with some default options and whatever options & headers you pass it.
	 * @param $url: the url to fetch
	 * @param $params: array of request parameters. the way they are sent will depend on the request method set for this request
	 * @param $headers: custom curl request headers to be set here
	 * @param $loadDefaults: boolean indicating whether to load the default curl options. true by default
	 * @param $optionOverrides: array of curl options to set. This is done after the loading of defaults, so they will be overwritten if used.
	 *
	 */
	private function _init($url, $params = array(), $headers = array(), $loadDefaults = true, array $optionOverrides = array(), $callback = false, $callbackparams = array())
	{
		$this->_handle = curl_init();
		$this->_key = md5((string) $this->_handle);
		$this->_handles[$this->_key] = array('handle' => $this->_handle);
		if ($callback) {
			$this->_handles[$this->_key]['callback'] = array('callback' => $callback, 'params' => $callbackparams);
		}
		$this->_params = $params;
		$this->_headers = $headers;
		$this->_url = $url;
		$this->_method = self::METHOD_GET;

		if ($loadDefaults)
		{
			$this->_loadDefaults();
		}

		$this->setOptions($optionOverrides);
		return $this->_key;

	}

	/**
	 * A bunch of default settings which should do the trick in most cases :-)
	 */
	private function _loadDefaults()
	{
		$this->setOptions(array(
			CURLOPT_RETURNTRANSFER		=> true,
			CURLOPT_REFERER				=> 'http://www.google.com/',
			CURLOPT_VERBOSE				=> false,
			CURLOPT_SSL_VERIFYPEER		=> false,
			CURLOPT_SSL_VERIFYHOST		=> false,
			CURLOPT_HEADER				=> self::DEFAULT_FETCHHEADER,
			CURLOPT_NOBODY				=> !self::DEFAULT_FETCHCONTENT,
			CURLOPT_FOLLOWLOCATION		=> self::DEFAULT_FOLLOWLOCATION,
			CURLOPT_MAXREDIRS			=> self::DEFAULT_MAXREDIRS,
			CURLOPT_USERAGENT			=> self::DEFAULT_USERAGENT,
			CURLOPT_CONNECTTIMEOUT		=> 0

		));
		if ($this->_debug !== false) {
			$this->setOption(CURLOPT_PROGRESSFUNCTION, array($this, '_progress'));
			$this->setOption(CURLOPT_NOPROGRESS, false);
			$this->setOption(CURLOPT_BUFFERSIZE,64000);
		}
	}

	/**
	 * private method to finalize a request so it's ready for execution
	 */
	private function _finalizeRequest()
	{
		if (!empty($this->_headers))
		{
			curl_setopt($this->_handle, CURLOPT_HTTPHEADER, $this->_headers);
		}

		if (!empty($this->_params))
		{
			if (is_array($this->_params)) {
				$params = array();
				foreach ($this->_params as $name => $value) {
					$params[] = $name . '=' . urlencode($value);
				}
				$params = implode('&', $params);
			}
			else {
				$params = $this->_params;
			}

			if ($this->_method !== self::METHOD_GET)
			{
				curl_setopt($this->_handle, CURLOPT_POSTFIELDS, $params);
			}
			else
			{
				if (strpos($this->_url, '?') === false)
				{
					$this->_url .= '?' . $params;
				}
				else
				{
					$this->_url .= '&' . $params;
				}
			}
		}

		if (!curl_setopt($this->_handle, CURLOPT_URL, $this->_url)) {
			throw new \Exception("something wrong right here: " . $this->_url); exit;
		}

		// store options in the handle info
		$this->_handles[$this->_key]['options'] = $this->_options;
		$this->_options = array(); $this->_key = null;
	}

	private function _progress($download_size, $downloaded, $upload_size, $uploaded)
	{
		call_user_func_array($this->_debug, array($download_size, $downloaded, $upload_size, $uploaded));

	}


	/*****************************************************************************
	 *        Set one or multiple options, curl option names should be used.     *
	 *****************************************************************************/

	/**
	 * Set an option. Wrapper method to handle some special cases (request method especially)
	 */
	public function setOption($option, $value)
	{
		$success = curl_setopt($this->_handle, $option, $value);
		// in case of some special options, we need to keep track of these values;
		if ($success)
		{
			$this->_options[$option] = $value;
			if ($option === CURLOPT_POST && $value)
			{
				$this->_method = self::METHOD_POST;
			}
			elseif ($option === CURLOPT_HTTPGET && $value)
			{
				$this->_method = self::METHOD_GET;
			}
			elseif ($option === CURLOPT_CUSTOMREQUEST)
			{
				$this->_method = $value;
			}
		}
		return $success;
	}

	/**
	 * Set an entire array of options. Wrapper method to handle some special cases (request method especially)
	 * @param array $options array of CURLOPT_* keys and their values.
	 */
	public function setOptions(array $options = array())
	{
		$allok = true;
		foreach ($options as $option => $value)
		{
			$allok &= $this->setOption($option, $value);
		}
		return $allok;
	}

	/*****************************************************************************
	 *       To make life easy, some quick sets for the most common options      *
	 *****************************************************************************/

	/**
	 * setting for max amount of redirects
	 * @param int $maxRedirects
	 */
	public function setMaximumRedirectCount($maxRedirects)
	{
		return curl_setopt($this->_handle, CURLOPT_MAXREDIRS, (int) $maxRedirects >= 0 ? (int) $maxRedirects : self::DEFAULT_MAXREDIRS);
	}

	/**
	 * set whether to fetch the headers
	 * @param boolean $value true to do so, false if you don't need them
	 */
	public function setFetchHeaders($value)
	{
		return curl_setopt($this->_handle, CURLOPT_HEADER, $value);
	}

	/**
	 * set whether to fetch the body
	 * @param boolean $value true to do so, false if you don't need the body
	 */
	public function setFetchContent($value)
	{
		return curl_setopt($this->_handle, CURLOPT_NOBODY, !$value);
	}

	/**
	 * Easy way to change the request method.
	 * @param one of the self::METHOD_* constants.
	 * @return  true if successful, false if not
	 */
	public function setRequestMethod($value)
	{
		if ($value === self::METHOD_POST)
		{
			$this->setOption(CURLOPT_POST, true);
			$this->setOption(CURLOPT_HTTPGET, false);
		}
		elseif ($value === self::METHOD_GET)
		{
			$this->setOption(CURLOPT_POST, false);
			$this->setOption(CURLOPT_HTTPGET, true);
		}
		else
		{
			$this->setOption(CURLOPT_POST, false);
			$this->setOption(CURLOPT_HTTPGET, false);
			return $this->setOption(CURLOPT_CUSTOMREQUEST, $value);
		}
		return true;
	}

	/*****************************************************************************
	 *    Some request methods to execute and fetch results, errors, statusses   *
	 *****************************************************************************/

	/**
	 * Gets request info for the request with the given key
	 * @param  $key request key
	 * @return curl info for the given request.
	 */
	public function getInfo($key)
	{
		if (isset($this->_handles[$key]))
		{
			return curl_getinfo($this->_handles[$key]['handle']);
		}
		else
		{
			return null;
		}
	}

	/**
	 * Execute executes the queued curl request(s).
	 * @param  Optional callback function to be called when the/a request is finished
	 * 		Signature of this function should be callback($response [, $additionalparam1, [$addionalparam2,...]])
	 * 		the response will be an associative array with indexes:
	 * 			key: the key of the request, received when calling addRequest
	 * 			url: the url that was fetched (not including post params atm, but get params are added to the url!)
	 * 			errno: errno of this request. 0 if success, one of the CURLE_* consts if not.
	 * 			error: only set when errno > 0. the error message
	 * 			response: the actuall content of the response.
	 * @param  array  $fixedCallbackParams, a fixed array of callback parameters to be passed to every call of the callback function. The response of the curl request will be prepended to this.
	 * @return errno of the last failed request or 0 if it was just smooth sailin'
	 */
	public function execute()
	{
		// Finalize that last request
		if (is_null($this->_handle))
		{
			return null;
		}

		$this->_finalizeRequest();

		if (count($this->_handles) > 1)
		{
			$multiCurlHandle = curl_multi_init();
			$this->_errno = 0;

			foreach ($this->_handles as $handle)
			{
				curl_multi_add_handle($multiCurlHandle, $handle['handle']);
			}

			$mrc = curl_multi_exec($multiCurlHandle, $active);

			do
			{
				if (curl_multi_select($multiCurlHandle) == -1) {
					//if it returns -1, wait a bit, but go forward anyways!
					usleep(100);
				}
				do
				{
					$mrc = curl_multi_exec($multiCurlHandle, $active);
					do
					{
						$msg = curl_multi_info_read($multiCurlHandle, $queue);
						if ($msg && $msg['msg'] == CURLMSG_DONE)
						{
							$handleKey = md5((string) $msg['handle']);
							$msg['options'] = $this->_handles[$handleKey]['options'];
							$this->_responses[$handleKey] = self::_parseResponse($msg);

							if ($msg['result'] > 0)
							{
								$this->_errno = $msg['result'];
								$this->_error = $this->_responses[$handleKey]['error'];
							}
							// is there a callback to call?
							if (isset($this->_handles[$handleKey]['callback']))
							{
								// add the key in case we want to know :-)
								$this->_responses[$handleKey]['key'] = $handleKey;
								$params = $this->_handles[$handleKey]['callback']['params'];
								// squeeze in the response
								array_unshift($params, $this->_responses[$handleKey]);
								call_user_func_array($this->_handles[$handleKey]['callback']['callback'], $params);
							}
							// No "else", response was already saved, it's all good!
							curl_close($msg['handle']);
							foreach ($this->_handles as $index => $handle) {
								if ($handle['handle'] === $msg['handle']) {
									unset($this->_handles[$index]);
								}
							}

							curl_multi_remove_handle($multiCurlHandle, $msg['handle']);

						}
					}
					while ($queue > 0);
				}
				while ($mrc == CURLM_CALL_MULTI_PERFORM);
			}
			while ($active && $mrc == CURLM_OK);

			// close all them curl handlers
			curl_multi_close($multiCurlHandle);
		}
		else
		{
			$response = curl_exec($this->_handle);
			$handleKey = md5((string) $this->_handle);

			$this->_errno = curl_errno($this->_handle);

			$this->_responses[$handleKey] = self::_parseResponse(array(
				'result' => $this->_errno,
				'handle' => $this->_handle,
				'response' => $response,
				'options' => $this->_handles[$handleKey]['options']
			));

			if ($this->_errno > 0)
			{
				$this->_error =  $this->_responses[$handleKey]['error'];
			}
			// callback? let's call it!
			if (isset($this->_handles[$handleKey]['callback']))
			{
				// add the key in case we want to know :-)
				$this->_responses[$handleKey]['key'] = $handleKey;
				$params = $this->_handles[$handleKey]['callback']['params'];
				// squeeze in the response
				array_unshift($params, $this->_responses[$handleKey]);
				call_user_func_array($this->_handles[$handleKey]['callback']['callback'], $params);
			}
			// No "else", response was already saved, it's all good!
		}
		return $this->_errno;
	}

	/**
	 * get response of the curl request(s).
	 * @param  $key optional, key for the response to get
	 * @return if no key given, an array with all responses, each of them being an associative array (see below).
	 * 		If a key was given, then only that index of the array will be returned.
	 * 		The value will be an associtative array with:
	 * 			- errno: the errno of the request. 0 if success, CURLE_* value if not.
	 * 			- error: only set when errno > 0, so if an error occurred
	 * 			- url: the url that was fetched (not including post params atm, but get params are added to the url!)
	 * 			- response: only set when errno == 0. contains the response contents.
	 * 		Or null if no response with that index could be
	 */
	public function getResponse($key = null)
	{
		if (is_null($key))
		{
			return $this->_responses;
		}
		else
		{
			if (isset($this->_responses[$key]))
			{
				return $this->_responses[$key];
			}
			else
			{
				return null;
			}
		}
	}


	/**
	 * Gets the errno of the last failed curl request
	 * @return int errno of the last failed curl request. Or 0 if all request were successful. Returns NULL if no requests were executed yet.
	 */
	public function getErrNo()
	{
		return $this->_errno;
	}

	/**
	 * Returns the error message of the last failed curl request
	 * @return string error message of last failed curl request or null if no requests executed or all were successful.
	 */
	public function getError()
	{
		return $this->error;
	}

	/**
	 * Play nice, close any open handles.
	 *
	 * @return void
	 */
	public function __destruct()
	{
		foreach ($this->_handles as $index => $handle)
		{
			curl_close($handle['handle']);
			unset($this->_handles[$index]);
		}
	}


	public function clean() {

		@curl_close($this->_handle);
		$this->_handle = null;
		foreach ($this->_handles as $index => $handle)
		{
			@curl_close($handle['handle']);
			unset($this->_handles[$index]);
		}
		$this->_handles = array();
		$this->_responses = array();
		$this->_handle = null;
		$this->_method = self::METHOD_GET;
		$this->_params = null;
		$this->_headers = null;
		$this->_url = null;
		$this->_errno = null;
		$this->_error = null;
		$this->_options = array();
		$this->_key = null;
	}

	//expects an array containing an 'result' (curl errno) value, a 'handle' (curl resource)
	//optional has the 'response' index set. If not, the response will be obtained from the handle.
	private function _parseResponse($msg)
	{
		$parsed = curl_getinfo($msg['handle']);
		$parsed['errno'] = $msg['result'];

		if ($msg['result'] == 0)
		{
			// response is only set when coming from a single curl request.
			if (!isset($msg['response']))
			{
				$parsed['response'] = curl_multi_getcontent($msg['handle']);
			} else {
				$parsed['response'] = $msg['response'];
			}

			//parse header and body. but make sure to check if we actually got the header and body
			if ($msg['options'][CURLOPT_HEADER]) {
				$responseLength = strlen($parsed['response']);
				if ($responseLength == $parsed['header_size']) {
					$parsed['header'] = $parsed['response'];
					unset($parsed['response']);
				} elseif ($responseLength > $parsed['download_content_length']) {
					$parsed['header'] = substr($parsed['response'], 0, $parsed['header_size']);
					$parsed['response'] = substr($parsed['response'], $parsed['header_size']);
				}
			}
		}
		else
		{
			$parsed['error'] = curl_error($msg['handle']);
		}
		return $parsed;
	}
}
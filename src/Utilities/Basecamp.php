<?php

/**
 * @Author Marijn Vandevoorde
 * @Email marijn@marijnworks.be
 *
 * Beats the crap out of that crap api
 */

namespace Sevenedge\Utilities\Utilities;

use Cake\Network\Exception\UnauthorizedException;

class Basecamp
{

    CONST OAUTH_ENDPOINT = 'https://launchpad.37signals.com/authorization/';
    CONST LAUNCHPAD_ENDPOINT = 'https://launchpad.37signals.com/authorization.json';
    CONST BASECAMP_ENDPOINT = 'https://basecamp.com/{accountId}/api/v1/';

    private $_credentials = null, $_cr = null, $_cache = array('urls' => array(), 'accounts' => array());

    /**
     * @param $credentials API login credentials
     * @param null $logCallback log callback method
     * @param $initialize full cache array. You can obtain this with the 'export' method to store the basecamp data to prevent bashing the API
     */
    public function __construct($credentials, $logCallback = null, $initialize = false)
    { //$clientId, $clientSecret, $authUrl) {
        $this->_credentials = $credentials;
        $this->logCallback = $logCallback !== null ? $logCallback : function ($level, $message) {
        };
        if ($initialize) {
            $this->_cache = $initialize;
        }

        $this->_cr = new CurlRequest();
    }

    /**
     * Export the internal cache so you can initialize it later to prevent bashing the API
     * @return The full cache built during the usage so far
     */
    public function export()
    {
        return $this->_cache;
    }

    /**
     * Import a cache from another (previous) instance of this class. To prevent bashing the API
     * @param $cache the exported cache to be imported again
     */
    public function import($cache)
    {
        $this->_cache = $cache;
    }

    /**
     * Force authentication
     */
    public function forceAuth()
    {
        $this->_getAuthenticationDetails();
    }

    /**
     * Build the authentication url
     * @return string The authentication url
     */
    public function getAuthUrl()
    {
        return self::OAUTH_ENDPOINT . 'new?type=web_server&client_id=' . $this->_credentials['clientid'] . '&redirect_uri=' . $this->_credentials['authUrl'];

    }

    public function getMe($accountId = null, $forceFetch = false)
    {
        if ($accountId) {
            if (!isset($this->_cache['accounts'][$accountId]['identity']) || $forceFetch) {
                $this->_getIdentity($accountId);
            }
        } else {
            // 37signals identity
            if (!isset($this->_cache['identity']) || $forceFetch) {
                $this->_getIdentity();
            }
            return $this->_cache['identity'];

        }

    }

    private function _getIdentity($accountId = null)
    {
        $this->_getAuthenticationDetails();

        if ($accountId) {
            $endpoint = str_replace('{accountId}', $accountId, self::BASECAMP_ENDPOINT) . 'people/me.json';
            $key = $this->_cr->addRequest($endpoint,
                null,
                array(
                    'Authorization: Bearer ' . $this->_credentials['access_token'],
                    'Content-Type: application/json',
                    'User-Agent: Watchdog (marijn@sevenedge.be)'
                ), true,
                array()
            );
            $err = $this->_cr->execute();
            if ($err === 0) {
                $res = $this->_cr->getResponse($key);
                $this->_cr->clean();
                $response = json_decode($res['response'], 1);
                if ($res['http_code'] === 400) {
                    throw new UnauthorizedException("Authentication failed: " . $response['error']);
                }
                if (!isset($this->_cache['accounts'][$accountId])) {
                    $this->_cache['accounts'][$accountId] = [];
                }
                $this->_cache['accounts'][$accountId]['identity'] = $response;
                return $response;
            }
            // smthing went wrong. clean curl req object.
            $this->_cr->clean();
            return null;
        } else {
            $key = $this->_cr->addRequest(self::LAUNCHPAD_ENDPOINT,
                null,
                array(
                    'Authorization: Bearer ' . $this->_credentials['access_token'],
                    'Content-Type: application/json'

                ), true,
                array()
            );
            $err = $this->_cr->execute();
            if ($err === 0) {
                $res = $this->_cr->getResponse($key);
                $this->_cr->clean();
                $response = json_decode($res['response'], 1);
                if ($res['http_code'] === 400) {
                    throw new UnauthorizedException("Authentication failed: " . $response['error']);
                }
                foreach ($response['accounts'] as $account) {
                    $this->_cache['accounts'][$account['id']] = $account;
                }
                $this->_cache['identity'] = $response['identity'];
                return $response;
            }
            // smthing went wrong. clean curl req object.
            $this->_cr->clean();
            return null;
        }

    }

    public function getAccounts($forceFetch = false, $full = false)
    {
        if (empty($this->_cache['accounts']) || $forceFetch) {
            $this->_getIdentity();
        }
        if ($full) {
            foreach ($this->_cache['accounts'] as $index => $account) {
                if (!isset($account['identity'])) {
                    $this->getMe($index, $forceFetch);
                }
            }
        }
        return $this->_cache['accounts'];

    }


    public function getProjects($accountId = null)
    {
        if (is_null($accountId)) {
            $accounts = $this->getAccounts();
        } else {
            $accounts = [['id' => $accountId, 'href' => 'https://basecamp.com/' . $accountId . '/api/v1']];
        }
        $me = $this->getMe();

        $keys = [];


        foreach ($accounts as $index => $account) {
            $keys[$index] = $this->_cr->addRequest(
                $account['href'] . '/api/v1/projects.json',
                null,
                array(
                    'Authorization: Bearer ' . $this->_credentials['access_token'],
                    'Content-Type: application/json',
                    'User-Agent: Watchdog (marijn@sevenedge.be)'
                )
            );
        }
        $projects = [];
        $this->_cr->execute();
        foreach ($keys as $key) {
            $res = $this->_cr->getResponse($key);
            $response = json_decode($res['response'], 1);
            if ($res['http_code'] === 400) {
                continue;
            }
            $projects = array_merge($projects, $response);

        }
        $this->_cr->clean();
        return $projects;
    }

    public function doRawRequest($url, $postData = false, $forceFetch = false)
    {
        $hash = md5($url);
        // only do it when post data or force fetch when not in cache yet.
        if ($postData || !isset($this->_cache['urls'][$hash]) || $forceFetch) {

            $this->_getAuthenticationDetails();
            $curlOpts = array(
                CURLOPT_FOLLOWLOCATION => false,
            );
            if (!empty($postData)) {
                $postData = json_encode($postData);
            } else {
                $postData = null;
            }

            $key = $this->_cr->addRequest($url,
                $postData,
                array(
                    'Authorization: Bearer ' . $this->_credentials['access_token'],
                    'Content-Type: application/json',
                    'User-Agent: Watchdog (marijn@sevenedge.be)'
                ),
                true,
                $curlOpts
            );
            if (!empty($postData)) {
                $this->_cr->setRequestMethod(CurlRequest::METHOD_PUT);
            }
            $err = $this->_cr->execute();
            if ($err === 0) {
                $res = $this->_cr->getResponse($key);
                $this->_cr->clean();
                $response = json_decode($res['response'], 1);
                if ($res['http_code'] > 399) {
                    throw new UnauthorizedException("Request failed: " . $response['error']);
                }

                if (!empty($postData)) {
                    // if it's a post, we hava probably been updating data. We will try to do a fast clearing of cache based on the url. If not, we'll just delete the entire cache
                    if (preg_match('#https://basecamp.com/([0-9]+)(/api/v1/projects/(.*))$#', $url, $matches)) {
                        $accountId = $matches[1];
                        if (count($matches) > 3) {
                            $action = $matches[3];
                            if (stripos($action, 'todos') === false) {
                                if (isset($this->_cache['accounts'][$accountId]['todos'])) {
                                    unset($this->_cache['accounts'][$accountId]['todos']);
                                }
                            }
                        }
                    }
                    $this->_cache = array('urls' => array(), 'accounts' => array());

                    // don't cache this :-)
                    return $response;
                }

                $this->_cache['urls'][$hash] = $response;
            }
        }
        return $this->_cache['urls'][$hash];
    }


    public function getTodos($accountId = null, $forceFetch = false)
    {
        if (is_null($accountId)) {
            $accounts = $this->getAccounts(false, true);
        } else {
            $accounts = [['id' => $accountId, 'href' => 'https://basecamp.com/' . $accountId . '/api/v1']];
        }

        $keys = [];

        foreach ($accounts as $index => &$account) {
            if (!empty($account) && (!isset($account['todos']) || $forceFetch)) {
                $keys[$index] = $this->_cr->addRequest(
                    $account['href'] . '/people/' . $account['identity']['id'] . '/assigned_todos.json',
                    null,
                    array(
                        'Authorization: Bearer ' . $this->_credentials['access_token'],
                        'Content-Type: application/json',
                        'User-Agent: Watchdog (marijn@sevenedge.be)'
                    )
                );
            }

        }

        $this->_cr->execute();
        foreach ($keys as $accountId => $key) {
            $res = $this->_cr->getResponse($key);
            $response = json_decode($res['response'], 1);
            if ($res['http_code'] === 400) {
                continue;
            }
            if (!empty($response)) {
                $this->_cache['accounts'][$accountId]['todos'] = $response[0]['assigned_todos'];
            } else {
                $this->_cache['accounts'][$accountId]['todos'] = [];
            }

        }

        $todos = [];
        foreach ($this->_cache['accounts'] as $index => $account) {
            foreach ($account['todos'] as $todo) {
                $todos[$todo['id']] = $todo;
            }

        }

        $this->_cr->clean();
        return $todos;

    }

    /**
     * just getting those authenticationdetails so we can save the authenticationtoken etc.
     * @return an array containing the original authentication details, supplemented with the acces token etc.
     */
    public function getAuthenticationDetails()
    {
        $details = $this->_getAuthenticationDetails();
        return $details;
    }


    private function _getAuthenticationDetails()
    {
        if (isset($this->_credentials['access_token'])) {
            if (isset($this->_credentials['valid_until']) && $this->_credentials['valid_until'] > time()) {
                $this->_log(E_USER_NOTICE, "using the cached access token");
                return $this->_credentials;
            } else if (isset($this->_credentials['refresh_token'])) {
                // we have a refresh token, so let's try it that way!

                $key = $this->_cr->addRequest(
                    self::OAUTH_ENDPOINT . 'token',
                    array(
                        'type' => 'web_server',
                        'refresh_token' => $this->_credentials['refresh_token'],
                        'redirect_uri' => $this->_credentials['authUrl'],
                        'client_id' => $this->_credentials['clientid'],
                        'client_secret' => $this->_credentials['secret']
                    ),
                    array(//'Authorization: Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret)

                    ), true,
                    array(
                        CURLOPT_FOLLOWLOCATION => false,
                        CURLOPT_REFERER => $this->_credentials['authUrl'],
                        CURLOPT_HEADER => true,
                        CURLOPT_POST => true
                    )
                );
                $err = $this->_cr->execute();

                if ($err === 0) {
                    $res = $this->_cr->getResponse($key);
                    $this->_cr->clean();
                    $response = json_decode($res['response'], 1);
                    if ($res['http_code'] === 400) {
                        throw new UnauthorizedException("Authentication failed: " . $response['error']);
                    }
                    $res = json_decode($res['response'], 1);
                    $this->_credentials = array_merge($this->_credentials, $res);
                    $this->_credentials['valid_until'] = time() + $this->_credentials['expires_in'];
                    $this->_log(E_USER_NOTICE, "was able to get a new access token with the refresh token");
                    return $this->_credentials;
                }
                $this->_cr->clean();
            }
        }

        // one final attempt.
        if (isset($this->_credentials['access_code'])) {
            $this->_log(E_USER_WARNING, "current token expired and unable to get a new one with the refresh token. trying to log in");

            $key = $this->_cr->addRequest(
                self::OAUTH_ENDPOINT . 'token',
                array(
                    'type' => 'web_server',
                    'code' => $this->_credentials['access_code'],
                    'redirect_uri' => $this->_credentials['authUrl'],
                    'client_id' => $this->_credentials['clientid'],
                    'client_secret' => $this->_credentials['secret']
                ), array(), true,
                array(
                    CURLOPT_FOLLOWLOCATION => false,
                    CURLOPT_REFERER => $this->_credentials['authUrl'],
                    CURLOPT_HEADER => true,
                    CURLOPT_POST => true
                )
            );

            $err = $this->_cr->execute();
            $res = $this->_cr->getResponse($key);
            if ($err === 0) {
                $res = $this->_cr->getResponse($key);
                $this->_cr->clean();
                $response = json_decode($res['response'], 1);
                if ($res['http_code'] === 400) {
                    throw new UnauthorizedException("Authentication failed: " . $response['error']);
                }
                $this->_credentials['valid_until'] = time() + $response['expires_in'];
                $this->_credentials['refresh_token'] = $response['refresh_token'];
                $this->_credentials['access_token'] = $response['access_token'];
                return $this->_credentials;

            }


        }
        // if we go there, it means something went wrong along the road. debug yourself, i don't feel like catching all possible exceptions atm.
        $this->_cr->clean();

        $this->_log(E_USER_ERROR, "Failed to get an access token in any possible way");
        throw new UnauthorizedException("something went terribly, terribly wrong");
    }

    private function _log($level, $message)
    {
        call_user_func($this->logCallback, $level, $message);
    }


}
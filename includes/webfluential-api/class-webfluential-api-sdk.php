<?php

// namespace Webfluential\Api\WooCommerce;

/**
 * Webfluential SDK WooCommerce API class
 *
 * @author PD Philip
 * @since 19.03.2018
 * @copyright Webfluential.com
 * @version 1.0
 */
class Webfluential_API_SDK
{

    const API_URL = 'https://api.webfluential.com/wc';

    const API_OAUTH_URL = 'https://api.webfluential.com/o/oauth2/auth';

    const API_OAUTH_TOKEN_URL = 'https://api.webfluential.com/o/oauth2/token';
    
    private $_apiKey;
    
    private $_apiSecret;
    
    private $_callbackUrl;
    
    private $_clientToken;
    
    private $_accessToken;
    
    private $_scopes = ['woocommerce.search.public','woocommerce.search'];
    
    private $_signedHeader = false; //(TODO later)
    
    private $_xRateLimitRemaining; //(TODO later)
    
    public $searchParams = [
        'channel_1' => false,
        'channel_2' => false,
        'channel_3' => false,
        'channel_50' => false,
        'channel_51' => false,
        'countries' => [],
        'markets' => [],
        'age_groups' => [],
        
    ];
    
    
    /**
     * Webfluential constructor.
     *
     * @param null $config
     */
    public function __construct($config = null)
    {
        if (is_array($config)) {
            !empty($config['apiKey'])?$this->setApiKey($config['apiKey']):null;
            !empty($config['apiSecret'])?$this->setApiSecret($config['apiSecret']):null;
            !empty($config['apiCallback'])?$this->setApiCallback($config['apiCallback']):null;
        } elseif (is_string($config)) {
            $this->setApiKey($config);
        }
    }
    
    /**
     * @param      $scopes
     * @param null $companyName
     * @param null $email
     * @param null $name
     *
     * @return string
     * @throws \Exception(
     */
    public function getLoginUrl($scopes,$companyName = null, $email = null, $name = null)
    {
        if (is_array($scopes) && count(array_intersect($scopes, $this->_scopes)) === count($scopes)) {
            $gets = '?client_id=' . $this->getApiKey() . '&redirect_uri=' . urlencode($this->getApiCallback()) . '&scope=' . implode('+',
                    $scopes) . '&response_type=code';
            if (!empty($companyName)){
                $gets .= '&company='.$companyName;
            }
            if (!empty($email)){
                $gets .= '&email='.$email;
            }
            if (!empty($name)){
                $gets .= '&name='.$name;
            }
                
                return self::API_OAUTH_URL.$gets;
        }
        
        throw new \Exception("Error: getLoginUrl() - The parameter isn't an array or invalid scope permissions used.");
    }
    
    
     /**
     * @param int    $page
     * @param string $sort
     * @param bool   $simulateCap
     *
     * @return mixed
     * @throws \Exception
     */
    public function publicSearch($page = 1,$sort = 'rank', $simulateCap = false)
    {
        $params['query'] = $this->searchParams;
        $params['page'] = $page;
        $params['sort'] = $sort;
        if ($simulateCap){
            $params['simulate_limit'] = true;
        }
        
        return $this->_makeCall('search/public/', false, $params, 'POST');
    }
    
    /**
     * @param int    $page
     * @param string $sort
     *
     * @return mixed
     * @throws \Exception
     */
    public function search($page = 1,$sort = 'rank')
    {
        $params['query'] = $this->searchParams;
        $params['page'] = $page;
        $params['sort'] = $sort;
    
        return $this->_makeCall('search/', true, $params, 'POST');
    }
    
    
   
    /**
     * Get the value of X-RateLimit-Remaining header field.
     *
     * @return int X-RateLimit-Remaining API calls left within 1 hour
     */
    public function getRateLimit()
    {
        return $this->_xRateLimitRemaining;
    }
    
    
    /**
     * @param      $code
     * @param bool $token
     *
     * @return mixed
     * @throws \Exception(
     */
    public function getOAuthToken($code, $token = false)
    {
        $apiData = array(
            'grant_type' => 'auth_code',
            'client_id' => $this->getApiKey(),
            'client_secret' => $this->getApiSecret(),
            'redirect_uri' => $this->getApiCallback(),
            'code' => $code
        );
        
        $result = $this->_makeOAuthCall($apiData);
        
        return !$token ? $result : $result->access_token;
    }
    
    /**
     * @param        $endpoint
     * @param bool   $auth
     * @param null   $params
     * @param string $method
     *
     * @return mixed
     * @throws \Exception
     */
    protected function _makeCall($endpoint, $auth = false, $params = null, $method = 'GET')
    {
    
        $headerData = ['Accept: application/json'];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headerData);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
        curl_setopt($ch, CURLOPT_TIMEOUT, 90);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HEADER, true);
    
    
        $paramString = null;
        $authSet = [];
        if (!$auth) {
            if (!isset($this->_clientToken)) {
                throw new \Exception("Error: _makeCall() | $endpoint - This method requires a client token.");
            }
            $authSet['key'] = 'client_token';
            $authSet['value'] = $this->getClientToken();
            $paramString = '?client_token=' . $this->getClientToken();
        } else {
            if (!isset($this->_accessToken)) {
                throw new \Exception("Error: _makeCall() | $endpoint - This method requires an authenticated users access token.");
            }
            $authSet['key'] = 'access_token';
            $authSet['value'] = $this->getAccessToken();
            $paramString = '?access_token=' . $this->getAccessToken();
        }
        if (isset($params) && is_array($params)) {
            $paramString .= '&' . http_build_query($params);
        }
        
        switch ($method) {
            case 'GET':
                $apiCall = self::API_URL . '/' . $endpoint . $paramString;
                if ($this->_signedHeader) {
                    $apiCall .= (strstr($apiCall, '?') ? '&' : '?') . 'sig=' . $this->_signHeader($endpoint, '?'.$authSet['key'].'='.$authSet['value'], $params);
                }
                curl_setopt($ch, CURLOPT_URL, $apiCall);
                break;
            case 'POST':
                $apiCall = self::API_URL . '/' . $endpoint;
                $params[$authSet['key']] = $authSet['value'];
                curl_setopt($ch, CURLOPT_URL, $apiCall);
                curl_setopt($ch, CURLOPT_POST, count($params));
                curl_setopt($ch, CURLOPT_POSTFIELDS, ltrim($paramString, '?'));
                break;
            case 'DELETE':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
        }
        $jsonData = curl_exec($ch);
        // split header from JSON data
        // and assign each to a variable
        list($headerContent, $jsonData) = explode("\r\n\r\n", $jsonData, 2);
        
        // convert header content into an array
        $headers = $this->processHeaders($headerContent);
        
        // get the 'X-Ratelimit-Remaining' header value
        if(!empty($headers['X-Ratelimit-Remaining'])){
            $this->_xRateLimitRemaining = $headers['X-Ratelimit-Remaining'];
        }
        
        
        if (!$jsonData) {
            throw new \Exception('Error: _makeCall() - cURL error: ' . curl_error($ch));
        }
        
        curl_close($ch);
        
        return json_decode($jsonData);
    }
    
    /**
     * @param $apiData
     *
     * @return mixed
     * @throws \Exception(
     */
    private function _makeOAuthCall($apiData)
    {
        $apiHost = self::API_OAUTH_TOKEN_URL;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiHost);
        curl_setopt($ch, CURLOPT_POST, count($apiData));
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($apiData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 90);
        $jsonData = curl_exec($ch);
        if (!$jsonData) {
            throw new \Exception('Error: _makeOAuthCall() - cURL error: ' . curl_error($ch));
        }
        
        curl_close($ch);
        
        return json_decode($jsonData);
    }
    
    /**
     * @param $endpoint
     * @param $authMethod
     * @param $params
     *
     * @return string
     */
    private function _signHeader($endpoint, $authMethod, $params)
    {
        if (!is_array($params)) {
            $params = array();
        }
        if ($authMethod) {
            list($key, $value) = explode('=', substr($authMethod, 1), 2);
            $params[$key] = $value;
        }
        $baseString = '/' . $endpoint;
        ksort($params);
        foreach ($params as $key => $value) {
            $baseString .= '|' . $key . '=' . $value;
        }
        $signature = hash_hmac('sha256', $baseString, $this->_apiSecret, false);
        
        return $signature;
    }
    
    /**
     * @param $headerContent
     *
     * @return array
     */
    private function processHeaders($headerContent)
    {
        $headers = array();
        
        foreach (explode("\r\n", $headerContent) as $i => $line) {
            if ($i === 0) {
                $headers['http_code'] = $line;
                continue;
            }
            
            list($key, $value) = explode(':', $line);
            $headers[$key] = $value;
        }
        
        return $headers;
    }
    
    
    public function setClientToken($data)
    {
        $token = is_object($data) ? $data->client_token : $data;
        
        $this->_clientToken = $token;
    }
    
    public function getClientToken()
    {
        return $this->_clientToken;
    }
    /**
     * @param $data
     */
    public function setAccessToken($data)
    {
        $token = is_object($data) ? $data->access_token : $data;
        
        $this->_accessToken = $token;
    }
    
    /**
     * @return string
     */
    public function getAccessToken()
    {
        return $this->_accessToken;
    }
    
    /**
     * @param $apiKey
     */
    public function setApiKey($apiKey)
    {
        $this->_apiKey = $apiKey;
    }
    
    /**
     * @return string
     */
    public function getApiKey()
    {
        return $this->_apiKey;
    }
    
    /**
     * @param $apiSecret
     */
    public function setApiSecret($apiSecret)
    {
        $this->_apiSecret = $apiSecret;
    }
    
    /**
     * @return string
     */
    public function getApiSecret()
    {
        return $this->_apiSecret;
    }
    
    /**
     * @param $apiCallback
     */
    public function setApiCallback($apiCallback)
    {
        $this->_callbackUrl = $apiCallback;
    }
    
    /**
     * @return string
     */
    public function getApiCallback()
    {
        return $this->_callbackUrl;
    }
    
    /**
     * @param $signedHeader
     */
    public function setSignedHeader($signedHeader)
    {
        $this->_signedHeader = $signedHeader;
    }
    
}

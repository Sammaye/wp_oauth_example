<?php

class WordPressOauth
{
    public $consumerKey;
    public $consumerSecret;
    
    public $accessToken;
    
    public $urls = array(
        'auth' => 'http://www.nhbs.com/hoopoe/index.php/oauth/authorize',
        'request_token' => 'http://www.nhbs.com/hoopoe/index.php/oauth/request_token',
        'request_access' => 'http://www.nhbs.com/hoopoe/index.php/oauth/request_access'
    );
    
    public $options = array(
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 60,
    );

    public function __construct($config)
    {
    	foreach($config as $k => $v){
    		$this->$k = $v;
    	}
    }
    
    public function setUrl($key, $url)
    {
        $this->urls[$key] = $url;
    }
    
    public function post($url, $params = array())
    {
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, array_key_exists($url, $this->urls) ? $this->urls[$url] : $url);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        
        $headers = array( "Expect:" );
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        curl_setopt_array($ch, $this->options);
        
        $post_string = '';
        foreach ($params as $key => $value) {
            $post_string .= $key.'='.$value.'&';
        }
        $post_string = substr($post_string, 0, strlen($post_string)-1);
        
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_string);
        $data = curl_exec($ch);
        curl_close($ch);
        return json_decode($data);
    }
    
    public function get($url, $params = array())
    {
        $ch = curl_init();
        
        //$get_string = 'access_token='.$this->getAccessToken();
        if (sizeof($params) > 0) {
            foreach ($params as $key => $value) {
                $get_string .= $key.'='.$value.'&';
            }
            $get_string = substr($get_string, 0, strlen($get_string)-1);
        }
        
        curl_setopt($ch, CURLOPT_URL, (array_key_exists($url, $this->urls) ? $this->urls[$url] : $url).'?'.$get_string);
        
        $headers = array( "Expect:" );
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        curl_setopt_array($ch, $this->options);
        $data = curl_exec($ch);
        curl_close($ch);
        return json_decode($data);
    }
    
    public function authorise()
    {
    	$accessToken = $this->getAccessToken();
    	if(isset($accessToken->Error)){
    		if(isset($_REQUEST['code'])){
				$accessToken = $this->getAccessToken($_REQUEST['code'], true);
				
				if(isset($accessToken->error)){
					echo "We could not authorize at all";
				}else{
					$this->saveAccessToken($accessToken);
				}
    		}else{
	    		header("Location: ".$this->urls['auth']."?client_id={$this->consumerKey}&response_type=code&state=t");
    		}
    	}
    	
    	$request = $this->requestAccess();
    	if(isset($request->error) && preg_match('/has expired/', $request->error_description)){
    		$this->requestAccess($accessToken->refresh_token);
    	}
    }
    
    public function requestAccess($accessToken = null)
    {
    	if($accessToken !== null){
    		$accessToken = $accessToken;
    	}elseif($this->accessToken instanceof stdClass && isset($this->accessToken->access_token)){
    		$accessToken = $this->accessToken->access_token;
    	}else{
    		$accessToken = $accessToken;
    	}
    	
    	return $this->post($this->urls['request_access'], array('access_token' => $accessToken));
    }
    
    public function getAccessToken($code = 'ggg', $refresh = false)
    {
        if($this->accessToken === null || $refresh){
            if(!$accessToken = $this->loadAccessToken()){
                $accessToken = $this->createAccessToken($code);
            }
            $this->accessToken = $accessToken;
        }
        return $this->accessToken;
    }
    
    
    public function createAccessToken($code = 'ggg')
    {
        return $this->post($this->urls['request_token'], array(
        	'client_id' => $this->consumerKey,
        	'client_secret' => $this->consumerSecret,
        	'grant_type' => 'authorization_code',
        	'code' => $code
        ));
    }
    
    public function loadAccessToken()
    {
    	if(file_exists('./wpOauthToken')){
        	return unserialize(file_get_contents('./wpOauthToken'));
    	}
    	return false;
    }
    
    /**
     * Saves the access token fetched from WordPress.
     * 
     * Default is to pipe it to a file
     * @param unknown $accessToken
     */
    public function saveAccessToken($accessToken)
    {
        // This should write more than one byte as such any falsey value is a problem
        if (file_put_contents('./wpOauthToken', serialize($accessToken))) {
            return true;
        }else{
            return false;
        }
    }
    
}

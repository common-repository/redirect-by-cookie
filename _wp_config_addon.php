<?php

class RedirectByCookie_wpconfig
{
	public function __construct()
	{
		$json_file= __DIR__.'/redirects.json';
		if ( ! file_exists($json_file) ) return;

		$options = json_decode(file_get_contents($json_file), true);

		$enabled 		= $options['enabled'];
		$cookies_all	= $options['cookies_all'];

		for($i=0; $i<count($cookies_all['cookie_name']); $i++)
		{
			$cookie_name	= $cookies_all['cookie_name'][$i];
			$redirect_from	= $cookies_all['redirect_from'][$i];
			$redirect_to	= $cookies_all['redirect_to'][$i];
			$autoset_empty_to=$cookies_all['autoset_empty_to'][$i];
	
			if (! $enabled || empty( $cookie_name ) ) return;

			if ( !isset($_COOKIE[$cookie_name]) )
			{
				if( empty($autoset_empty_to) )
				{
					return;
				}
				else
				{
					$_COOKIE[$cookie_name] = $autoset_empty_to;
					$this->set_cookie($cookie_name, $autoset_empty_to, 86400 * 30 );
				}
			}
	
			if ( isset($_COOKIE[$cookie_name]) )
			{
				$cookie_value=$_COOKIE[$cookie_name];
				
				if (!empty($cookie_value))
				{		
					$url=  $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; 
					$current_url = $this->normalize_with_slashes( $this->remove_https_www( $url ) );
					if ($current_url==$redirect_from)
					{
						$target =  (empty($redirect_to) ? $current_url. $cookie_value : $redirect_to) . '/' ;
						$this->php_redirect( $this->get_https(). $target );
					}
				}
			}
		}
	}
	
	
	
	
	// ========== HELPERS from library ========== //
	public function remove_www($url) 	{ 
		return str_replace( ['://www.'], '://', $url ); 
	}
	
	public function remove_https_www($url){
		return str_replace( ['https://www.','http://www.','http://','https://'], '', $url ); 
	}

	public function normalize_with_slashes($url, $add_trailing_slash=true){ 
		return rtrim( $this->remove_extra_slashes($url), '/')  . ($add_trailing_slash ? '/' : '') ; 
	}

	public function remove_extra_slashes($url){
		$prefix='';
		if(substr($url,0,2)=='//'){
			$prefix = '//';
			$url=substr($url,2);
		}
		return $prefix.preg_replace( '/([^:])\/\//',  '$1/', $url);
	}
 
	public function get_https($with_scheme=true)	{ 
		$http_s = ''; 
		$http_s = !empty($http_s) ? $http_s : ( ( !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off' ) || $_SERVER['SERVER_PORT']==443 ? 'https' : '');
		$http_s = !empty($http_s) ? $http_s : ( ( !empty($_SERVER['REQUEST_SCHEME']) ) ? $_SERVER['REQUEST_SCHEME'] : ''); 
		$http_s = !empty($http_s) ? $http_s : 'http'; 
		if ($with_scheme) $http_s .= '://';
		return $http_s;
	}
	
	public function set_cookie($name, $val, $time_length = 86400, $path=false, $domain=false, $httponly=true){
		$site_urls = parse_url( (function_exists('home_url') ? home_url() : $_SERVER['SERVER_NAME']) );
		$real_domain = $site_urls["host"];
		$path = $path ? $path : ( (!empty($this) && property_exists($this,'home_FOLDER') ) ?  $this->home_FOLDER : '/');
		$domain = $domain ? $domain : (substr($real_domain, 0, 4) == "www." ? substr($real_domain, 4) : $real_domain);
		setcookie ( $name , $val , time()+$time_length, $path = $path, $domain = $domain,  $only_on_secure_https = FALSE,  $httponly  );
	}
	
	public function js_redirect($url=false, $echo=true){
		$str = '<script>document.body.style.opacity=0; window.location = "'. ( $url ?: $_SERVER['REQUEST_URI'] ) .'";</script>';
		if($echo) { exit($str); }  else { return $str; }
	}
	
	public function php_redirect($url=false, $code=302){
		header("location: ". ( $url ?: $_SERVER['REQUEST_URI'] ), true, $code); exit;
	}
}

new RedirectByCookie_wpconfig();
?>
<?php
	class http
	{
		public static $ua = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_2) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/44.0.2403.157 Safari/537.36';
		public static $cookieJar = null;

		public static function setUa($newUserAgent) {
			http::$ua = $newUserAgent;
		}

		public static function setCookieJar($jar) {
			http::$cookieJar = $jar;
		}

		public static function get($url, $headers = array(), $cookie = '') {
			$ch = curl_init();

			if($ch === FALSE) {
				return FALSE;
			}

			curl_setopt_array($ch, array(
				CURLOPT_URL 			=> $url,
				CURLOPT_RETURNTRANSFER 	=> 1,
				CURLOPT_FRESH_CONNECT 	=> 1,
				CURLOPT_FOLLOWLOCATION	=> 1,
				CURLOPT_SSL_VERIFYHOST 	=> 0,
				CURLOPT_SSL_VERIFYPEER 	=> 0,
				CURLOPT_USERAGENT 		=> http::$ua,
				CURLOPT_CONNECTTIMEOUT 	=> 0, // Disable timeout
				CURLOPT_TIMEOUT 		=> 400
			));

			if(http::$cookieJar !== null) {
				curl_setopt($ch, CURLOPT_COOKIEJAR, http::$cookieJar);
			}

			if(!empty($headers)) {
				curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			}

			$data = curl_exec($ch);

			curl_close($ch);

			return $data;
		}

		public static function post($url, $postData = '', $headers = array(), $cookie = '') {
			$ch = curl_init();

			if($ch === FALSE) {
				return FALSE;
			}

			curl_setopt_array($ch, array(
				CURLOPT_URL 			=> $url,
				CURLOPT_RETURNTRANSFER 	=> 1,
				CURLOPT_FRESH_CONNECT 	=> 1,
				CURLOPT_FOLLOWLOCATION	=> 1,
				CURLOPT_SSL_VERIFYHOST 	=> 0,
				CURLOPT_SSL_VERIFYPEER 	=> 0,
				CURLOPT_USERAGENT 		=> http::$ua,
				CURLOPT_CONNECTTIMEOUT 	=> 0, // Disable timeout
				CURLOPT_TIMEOUT 		=> 400,
				CURLOPT_POST 			=> 1,
				CURLOPT_POSTFIELDS 		=> $postData
			));

			if(http::$cookieJar !== null) {
				curl_setopt($ch, CURLOPT_COOKIEJAR, http::$cookieJar);
			}

			if(!empty($headers)) {
				curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			}

			$data = curl_exec($ch);

			curl_close($ch);

			return $data;
		}
	};
?>

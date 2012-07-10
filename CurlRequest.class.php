<?php
/* Example:
 * $req = new CurlRequest('http://google.com/');
 * $req->setParams(array(
 *   'foo'      => 'bar'
 *   'encoding' => 'json'
 * ));
 *
 * $results = $req->execute();
 * 
 * $results array(
 *   headers   => http headers
 *   body      => http response
 *   http_code => http status code
 *   url       => url called
 * )
 */
if(!function_exists('curl_init')) {
	throw new Exception('CurlRequest needs the CURL PHP extension.');
}

// Custom exceptions for phpunit test purposes
class InvalidURLException extends Exception {}
class HTTPException extends Exception {}

class CurlRequest {
	private $conn = false;
	private $urlFormatting = true;
	private $encoding = true;

	private $CURL_OPTS = array(
		CURLOPT_CONNECTTIMEOUT => 10,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_TIMEOUT        => 60,
		CURLOPT_USERAGENT      => 'php_curl',
		CURLOPT_HTTPGET        => true,
		CURLOPT_POST           => false,
		CURLOPT_HEADER         => true,
		CURLOPT_FOLLOWLOCATION => true
	);

	protected $params = array();
	protected $method = 'get';
	protected $url;

	public function __construct($url, $urlFormatting = true) {
		$this->urlFormatting = $urlFormatting;
		$this->setURL($url);
	}

	public function setURL($url) {
		if(!filter_var($url, FILTER_VALIDATE_URL)) {
			throw new InvalidURLException($url);
		}

		$this->url = $url;
	}

	public function getUrl() {
		return $this->url;
	}

	public function setOpt($option, $value) {
		$this->CURL_OPTS[$option] = $value;
	}

	// default method is GET
	public function setMethod($method) {
		if($method == 'post') {
			$this->setOpt(CURLOPT_POST, true);
			$this->setOpt(CURLOPT_HTTPGET, false);
		} elseif($method == 'get') {
			$this->setOpt(CURLOPT_POST, false);
			$this->setOpt(CURLOPT_HTTPGET, true);
		}

		$this->method = $method;
	}

	public function setParams($params, $encoding = true) {
		if(is_array($params)) {
			$this->params = $params;
		}

		$this->encoding = $encoding;
	}

	public function execute() {
		if(!is_resource($this->conn)) {
			$this->conn = curl_init();
		}

		// use default HTTP encoding
		if($this->encoding) {
			$query_string = http_build_query($this->params, null, '&');
		} else {
			$query_string = '';
			foreach($this->params as $k => $v) {
				$query_string .= "{$k}={$v}&";
			}
		}

		if($this->method == 'post') {
			$this->setOpt(CURLOPT_POSTFIELDS, $query_string);
		} else {
			if(!$this->urlFormatting || substr($this->url, -1) === '?') {
				$this->url .= $query_string;
			} else {
				$this->url .= "?{$query_string}";
			}
		}
		
		$this->setOpt(CURLOPT_URL, $this->url);
		curl_setopt_array($this->conn, $this->CURL_OPTS);

		$result = curl_exec($this->conn);
		$info = curl_getinfo($this->conn);

		// curl failed to execute call
		if($result === false) {
			throw new Exception('Curl failed with "' . curl_error($this->conn) . '"');

		// server side soft error, gcm services
		// } elseif(strstr($result, 'ERROR')) {
		// throw new Exception('Request to server failed with "' . $result . '"');

		// throw exception on non 2xx status
		} elseif($info['http_code'] < 200 || $info['http_code'] >= 300) {
			throw new HTTPException('Request failed with HTTP Status ' . $info['http_code']);
		}

		$return = array(
			'headers'    => substr($result, 0, $info['header_size']),
			'body'      => substr($result, $info['header_size']),
			'http_code' => $info['http_code'],
			'url'       => $info['url']
		);

		curl_close($this->conn);
		return $return;
	}
}

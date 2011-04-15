<?php

namespace Evergreen\Http;

use Evergreen\Common\ParameterStore;

/**
 * 
 */
class Request {
	protected $query = null;
	protected $post = null;
	protected $cookies = null;
	protected $files = null;
	protected $server = null;
	protected $headers = null;
	protected $method = 'GET';
	
	protected $pathInfo = null;
	protected $requestUri = null;
	protected $baseUrl = null;
	protected $basePath = null;
	
	protected $formats = null;
	
	public function __construct(array $query = array(), array $post = array(), array $cookies = array(), array $files = array(), array $server = array()) {
		
		$this->formats = new ParameterStore(array(
			'htm'  => array('text/html', 'application/xhtml+xml'),
			'html' => array('text/html', 'application/xhtml+xml'),
			'txt'  => array('text/plain'),
			'js'   => array('application/javascript', 'application/x-javascript', 'text/javascript'),
			'css'  => array('text/css'),
			'json' => array('application/json', 'application/x-json'),
			'xml'  => array('text/xml', 'application/xml', 'application/x-xml'),
			'rdf'  => array('application/rdf+xml'),
			'atom' => array('application/atom+xml'),
		));
		
		
		$this->setup($query, $post, $cookies, $files, $server);
	}
	
	public function setup(array $query = array(), array $post = array(), array $cookies = array(), array $files = array(), array $server = array()) {
		$this->query = $query;
		$this->post = $post;
		$this->cookies = $cookies;
		$this->files = $files;
		$this->server = new ParameterStore(array_replace(array(
			'HTTP_HOST'				=> null,
			'SERVER_NAME'			=> 'localhost',
			'SERVER_ADDR'			=> '::1',
			'SERVER_PORT'			=> '80',
			'HTTPS'					=> 'off',
			'REMOTE_ADDR'			=> '::1',
			'HTTP_USER_AGENT'		=> 'Evergreen/2.X',
			'HTTP_ACCEPT'			=> 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
			'HTTP_ACCEPT_LANGUAGE'	=> 'en-us,en;q=0.5',
			'HTTP_ACCEPT_CHARSET'	=> 'ISO-8859-1,utf-8;q=0.7,*;q=0.7',
			'SERVER_PROTOCOL'		=> 'HTTP/1.1',
			'REQUEST_METHOD'		=> 'GET',
			'QUERY_STRING'			=> null,
			'REQUEST_URI'			=> null,
			'SCRIPT_NAME'			=> null,
			'SCRIPT_FILENAME'		=> null,
		), $server));
	}
	
	static public function create($uri, $method = "GET", array $request = array(), array $cookies = array(), array $files = array(), array $server = array()) {
		$server = array();
		$uriParts = parse_url($uri);
		
		if (isset($uriParts['host'])) {
			$server['SERVER_NAME'] = $uriParts['host'];
			$server['HTTP_HOST'] = $uriParts['host'];
		}
		
		if(isset($uriParts['scheme'])) {
		    if ($uriParts['scheme'] == 'https') {
				$server['HTTPS'] = 'on';
				$server['SERVER_PORT'] = 443;
		    }
		}
		
		if (isset($uriParts['port'])) {
		    $server['SERVER_PORT'] = $uriParts['port'];
		    $server['HTTP_HOST'] = $server['HTTP_HOST'].':'.$uriParts['port'];
		}
		
		if (in_array(strtoupper($method), array('POST', 'PUT', 'DELETE'))) {
		    $post = $request;
		    $query = array();
		    $server['CONTENT_TYPE'] = 'application/x-www-form-urlencoded';
		} else {
		    $post = array();
		    $query = $request;
		}
		
		$queryString = isset($uriParts['query']) ? html_entity_decode($uriParts['query']) : '';
		parse_str($queryString, $qs);
		if (is_array($qs)) {
		    $query = array_merge($qs, $query);
		}
		
		$server['REQUEST_URI'] = strtoupper($method);
		$server['REQUEST_URI'] = $uriParts['path'] . ($queryString ? '?'.$queryString : '');
		$server['QUERY_STRING'] = http_build_query($query);
	
		return new static($query, $post, $cookies, $files, $server);
	}
	
	static public function createfromGlobals() {
		return new static($_GET, $_POST, $_COOKIE, $_FILES, $_SERVER);
	}
	
	public function getServer($key, $default = null) {
		return ($this->server->hasVal($key)) ? $this->server->get($key) : $default;
	}
	
	public function getPost($key, $default = null) {
		return ($this->post->hasVal($key)) ? $this->post->get($key) : $default;
	}
	
	public function getQuery($key, $default = null) {
		return ($this->query->hasVal($key)) ? $this->query->get($key) : $default;
	}
	
	public function getRequestUri() {
        if (null === $this->requestUri) {
            $this->requestUri = $this->prepareRequestUri();
        }

        return $this->requestUri;
    }
	
	public function getBasePath() {
		
	}
	
	public function getScheme() {
        return ($this->server->get('HTTPS') == 'on') ? 'https' : 'http';
    }
	
	public function getUri() {
        $qs = $this->getQueryString();
        if (null !== $qs) {
            $qs = '?'.$qs;
        }
		
        return $this->getScheme().'://'.$this->getHttpHost().$this->getBaseUrl().$this->getPathInfo().$qs;
    }
	
	public function getQueryString() {
        if (!$qs = $this->server->get('QUERY_STRING')) {
            return null;
        }

        $parts = array();
        $order = array();

        foreach (explode('&', $qs) as $segment) {
            if (false === strpos($segment, '=')) {
                $parts[] = $segment;
                $order[] = $segment;
            } else {
                $tmp = explode('=', urldecode($segment), 2);
                $parts[] = urlencode($tmp[0]).'='.urlencode($tmp[1]);
                $order[] = $tmp[0];
            }
        }
        array_multisort($order, SORT_ASC, $parts);

        return implode('&', $parts);
    }
	
	public function getHttpHost() {
        if ($this->server->hasVal('HTTP_HOST')) {
            return $this->server->get('HTTP_HOST');
        }

        $scheme = $this->getScheme();
        $name   = $this->server->get('SERVER_NAME');
        $port   = $this->getPort();

        if (('http' == $scheme && $port == 80) || ('https' == $scheme && $port == 443)) {
            return $name;
        }

        return $name.':'.$port;
    }
	
	public function getPort() {
        return $this->server->get('SERVER_PORT');
    }
	
	public function getBaseUrl() {
        if (null === $this->baseUrl) {
            $this->baseUrl = $this->prepareBaseUrl();
        }

        return $this->baseUrl;
    }
	
	protected function prepareRequestUri()
    {
        $requestUri = '';

        if ($this->headers->has('X_REWRITE_URL')) {
            // check this first so IIS will catch
            $requestUri = $this->headers->get('X_REWRITE_URL');
        } elseif ($this->server->get('IIS_WasUrlRewritten') == '1' && $this->server->get('UNENCODED_URL') != '') {
            // IIS7 with URL Rewrite: make sure we get the unencoded url (double slash problem)
            $requestUri = $this->server->get('UNENCODED_URL');
        } elseif ($this->server->has('REQUEST_URI')) {
            $requestUri = $this->server->get('REQUEST_URI');
            // HTTP proxy reqs setup request uri with scheme and host [and port] + the url path, only use url path
            $schemeAndHttpHost = $this->getScheme().'://'.$this->getHttpHost();
            if (strpos($requestUri, $schemeAndHttpHost) === 0) {
                $requestUri = substr($requestUri, strlen($schemeAndHttpHost));
            }
        } elseif ($this->server->has('ORIG_PATH_INFO')) {
            // IIS 5.0, PHP as CGI
            $requestUri = $this->server->get('ORIG_PATH_INFO');
            if ($this->server->get('QUERY_STRING')) {
                $requestUri .= '?'.$this->server->get('QUERY_STRING');
            }
        }

        return $requestUri;
    }
	
	public function __toString() {
		return "Hello World";
	}
}

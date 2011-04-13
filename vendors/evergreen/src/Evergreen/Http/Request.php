<?php

namespace Evergreen\Http;

/**
 * 
 */
class Request {
	protected $query = array();
	protected $post = array();
	protected $cookies = array();
	protected $files = array();
	protected $server = array(
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
	);
	protected $headers = array();
	protected $method = 'GET';
	
	protected $pathInfo = null;
	protected $requestUri = null;
	protected $baseUrl = null;
	protected $basePath = null;
	
	protected $formats = array(
		'htm'  => array('text/html'),
		'html' => array('text/html'),
		'txt'  => array('text/plain'),
		'js'   => array('application/javascript', 'application/x-javascript', 'text/javascript'),
		'css'  => array('text/css'),
		'json' => array('application/json', 'application/x-json'),
		'xml'  => array('text/xml', 'application/xml', 'application/x-xml'),
		'rdf'  => array('application/rdf+xml'),
		'atom' => array('application/atom+xml'),
	);
	
	public function __construct(array $query = array(), array $post = array(), array $cookies = array(), array $files = array(), array $server = array()) {
		$this->setup($query, $post, $cookies, $files, $server);
	}
	
	public function setup(array $query = array(), array $post = array(), array $cookies = array(), array $files = array(), array $server = array()) {
		$this->query = $query;
		$this->post = $post;
		$this->cookies = $cookies;
		$this->files = $files;
		$this->server = array_replace($this->server, array_intersect_key($server, $this->server));
		
		if (!empty($this->server['REQUEST_METHOD'])) {
			$this->method = $this->server['REQUEST_METHOD'];
		}
		
		if (!empty($this->server['REQUEST_URI'])) {
			$this->requestUri = $this->server['REQUEST_URI'];
		}
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
		return (!empty($this->server[$key])) ? $this->server[$key] : $default;
	}
	
	public function getPost($key, $default = null) {
		return (!empty($this->post[$key])) ? $this->post[$key] : $default;
	}
	
	public function getQuery($key, $default = null) {
		return (!empty($this->query[$key])) ? $this->query[$key] : $default;
	}
	
	public function getRequestURI() {
		
	}
	
	public function getBasePath() {
		
	}
}

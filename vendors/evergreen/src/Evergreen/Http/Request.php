<?php

namespace Evergreen\Http;

/**
 * 
 */
class Request {
	protected $get;
	protected $post;
	protected $attributes;
	protected $cookies;
	protected $files;
	protected $server;
	protected $method;
	
	protected $pathInfo;
	protected $requestUri;
	protected $baseUrl;
	protected $basePath;
	
	public function __construct(array $get = array(), array $post = array(), array $attributes = array(), array $cookies = array(), array $files = array(), array $server = array()) {
		$this->setup($get, $post, $attributes, $cookies, $files, $server);
	}
	
	public function setup(array $get = array(), array $post = array(), array $attributes = array(), array $cookies = array(), array $files = array(), array $server = array()) {
		$this->get = $get;
		$this->post = $post;
		$this->attributes = $attributes;
		$this->cookies = $cookies;
		$this->files = $files;
		$this->server = $server;
	}
	
	public function create(string $uri, string $method = "GET", array $request = array(), array $cookies = array(), array $files = array(), array $server = array()) {
		$uriParts = parse_url($uri);
		
	}
	
	static public function createfromGlobals() {
		return new static($_GET, $_POST, array(), $_COOKIE, $_FILES, $_SERVER);
	}
}

<?php

namespace Evergreen\Http;

/**
 * 
 */
class Request {
	protected $attributes;
	protected $get;
	protected $post;
	protected $server;
	protected $files;
	protected $cookies;
	protected $headers;
	protected $pathInfo;
	protected $requestUri;
	protected $baseUrl;
	protected $basePath;
	protected $method;
	
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
	
	public function create($uri) {
		$uriParts = parse_url($uri);
	}
	
	static public function createfromGlobals() {
		return new static($_GET, $_POST, array(), $_COOKIE, $_FILES, $_SERVER);
	}
}

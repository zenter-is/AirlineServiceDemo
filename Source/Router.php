<?php

namespace AirlineServiceDemo;

class Router
{
	private $headers;
	private $get;
	private $post;
	private $cookie;

	public function __construct($headers, $get, $post, $cookie)
	{
		$this->headers = $headers;
		$this->get = $get;
		$this->post = $post;
		$this->cookie = $cookie;
	}


	public function execute():string
	{
		echo "Hello world";
	}
}

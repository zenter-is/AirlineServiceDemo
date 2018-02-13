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
		switch($this->headers['REQUEST_URI'])
		{
			case 'delay':
				$service = new Services\Delay(['flightId' => '186']);
				$service->execute();
				break;
			case 'landed':
				$service = new Services\Landed(['flightId' => 'F221']);
				$service->execute();
				break;
			default:
				http_response_code(404);
				return "The thing you were looking for could not be found";
				break;
		}
	}
}

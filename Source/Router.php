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


	public function execute()
	{
		switch($this->headers['REQUEST_URI'])
		{
			case '/delay':
				$service = new Services\Delay();
				$service->execute(['flightId' => '186']);
				break;
			case '/landed':
				$service = new Services\Landed();
				$service->execute(['flightId' => 'F221']);
				break;
			default:
				http_response_code(404);
				var_dump($this->headers['REQUEST_URI']);
				return "The thing you were looking for could not be found";
				break;
		}
	}
}

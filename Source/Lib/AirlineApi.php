<?php

namespace AirlineServiceDemo\Lib;

class AirlineApi
{
	private $endpoint;

	public function __construct($endpoint)
	{
		$this->endpoint = $endpoint;
	}

	public function getPassengersForFlight($flightId)
	{
		//Mock Data
		switch ($flightId) {
			case '186':
				return json_decode(file_get_contents(__DIR__ . '/../AirlineApiMockData/Flight186.json'))
				break;
			case 'F221':
				return json_decode(file_get_contents(__DIR__ . '/../AirlineApiMockData/FlightF221.json'))
				break;
			default:
				throw new \Exception(sprintf('No flight by the id of %s found', $flightId));1
		}
	}
}
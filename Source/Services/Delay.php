<?php

namespace AirlineServiceDemo\Services;

use AirlineServiceDemo\Lib\AirlineApi;

class Delay implements IService
{
	public function __construct()
	{
	}


	public function execute($data):bool
	{
		$airlineApi = new AirlineApi('http://some_endpoint');
		//I get stuff from server
		$passangers = $airlineApi->getPassengersForFlight($data['flightId']);

		$baseRecipients = [];
		foreach($passangers as $passanger)
		{
			if($passanger->email !== '' && strpos($passanger->email, '@') !== false)
			{
				$baseRecipients[] = $passanger;
			}
		}

		$zenterRecipients = [];
		//@TODO: Check if recipients in Zenter. Preferably in a batch

		$recipientIds = array_map(function($recipient) { return $recipient->id }, $zenterRecipients);

		//@TODO: create a list

		$list; //Should exist by now.

		//@TODO: Add recipients to list

		//------------------------------------------------

		//@TODO: Create a job

		$job; //Should exist by now


		$articles = [
			[
				'type' => 1,
				'title' => "Flight XXYY is delays",
				'content' => "Dear {$recipient->name}, we are sorry to announce that your flight XXYY has beend delayed"
			],
			[
				'type' => 2,
				'content' => 'New flight time is 22:77'
			]
		];

		//@TODO: Add articles to job

		//@TODO: Add list to job

		//@TODO: Send the job
		
		return true;
	}
}

<?php

namespace AirlineServiceDemo\Services;

use AirlineServiceDemo\Lib\AirlineApi;
use AirlineServiceDemo\Lib\GraphqApiClient;

class Delay implements IService
{
	public function __construct()
	{
	}


	public function execute(array $data):bool
	{

		GraphqApiClient::initialize("http://zenter.local/Api/V2ea1");
		GraphqApiClient::getVersion();

		$token = GraphqApiClient::login("483_api@zenter.is", "f3ad7732fcfa98059a84aae21536bdfa");

		if (!$token)
		{
			throw new Exception("Could not login");
		}

		GraphqApiClient::initialize("http://zenter.local/Api/V2ea1?token={$token}");
		if (!GraphqApiClient::IsPriviliged())
		{
			throw new Exception("Login attempt failed");
		}

		$flightId = $data['flightId'];
		$airlineApi = new AirlineApi('http://some_endpoint');
		//I get stuff from server
		$passangers = $airlineApi->getPassengersForFlight($flightId);

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

		$recipientIds = array_map(function($recipient) { return $recipient->id; }, $zenterRecipients);

		//@TODO: create a list
		$list = GraphqApiClient::createList("List-{$flightId}");

		if ($list)
		{
			die("Creating list was successful");
		}
		die("Creating list was not successful");

		//@TODO: Add recipients to list

		//------------------------------------------------

		//@TODO: Create a job
		$jobTitle = "Flight{$flightId}";
		$job =  GraphqApiClient::createJob($jobTitle); // this might need more details
		$jobId = array_key_exists("id", $job)?$job['id']:null;

		if (!$jobId)
		{
			throw new Exception("Unable to create Job");
		}

		$articles = [
			[
				'type' => 1,
				'title' => "Flight XXYY is delays",
				'content' => "Dear {$recipient->name}, we are sorry to announce that your flight XXYY has beend delayed"
			],
			[
				'type' => 2,
				'content' => 'New flight time is DD-MM-YYYY, 22:77'
			]
		];

		//@TODO: Add articles to job

		//@TODO: Add list to job

		//@TODO: Send the job
		return true;
	}
}

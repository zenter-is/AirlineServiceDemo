<?php

namespace AirlineServiceDemo\Services;

use AirlineServiceDemo\Lib\GraphqApiClient;
use AirlineServiceDemo\Lib\AirlineApi;

class Landed implements IService
{
	public function __construct()
	{
	}


	public function execute(array $data):bool
	{
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

		$recipientIds = array_map(function($recipient) { return $recipient->id }, $zenterRecipients);

		//@TODO: create a list
		$list = GraphqApiClient::createList("List-{$flightId}");

		if ($list === null)
		{
			// Panic or handle error!!
		}

		$list; //Should exist by now.

		//@TODO: Add recipients to list
		$list;

		//------------------------------------------------

		//@TODO: Create a job
		$jobTitle = "";

		$job =  GraphqApiClient::createJob($jobTitle); // this might need more details

		$jobId = array_key_exists("id", $job)?$job['id']:null;

		if (!$jobId)
		{
			throw new Exception("Unable to create Job");
		}

		$articles = [
			[
				'type' => 1,
				'title' => "Thank you for flying with airline XXX",
				'content' => "Dear {$recipient->name}, we say a big thank you for flying with airline XXX."
			],
			[
				'type' => 2,
				'content' => 'As a way of saying thank you, we offer you this coupon for some snacks while you wait for your luggage'
			]
		];

		//@TODO: Add articles to job

		//@TODO: Add list to job

		//@TODO: Send the job
		$job =  GraphqApiClient::createJob($job['id']);
	}
}

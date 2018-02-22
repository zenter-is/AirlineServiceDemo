<?php

namespace AirlineServiceDemo\Services;

class Landed implements IService
{
	public function __construct()
	{
	}


	public function execute():bool
	{

		//I get stuff from server
		$passangers = [];

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
	}
}

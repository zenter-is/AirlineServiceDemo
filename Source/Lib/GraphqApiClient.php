<?php

namespace AirlineServiceDemo\Lib;

use Exception;


function idx($array, $key, $default = null)
{
	if (array_key_exists($key, $array))
	{
		return $array[$key];
	}

	return $default;
}

/**
 * Asynchronous communication service to the Ivory system
 * Remember to use \HH\Asio\join(<Awaitable>) to get the data
 */
class GraphqApiClient
{
	const ASSET_QUERY = 'id,title,sha1,size,content_type,ref_sha1,date_created,date_modified';
	const DEBUGGING = false;

	static $failure = 0;

	private static $endpoint = null;

	public function __construct()
	{

	}

	public static function getVersion():string
	{
		$query = [
			"query" => file_get_contents(__DIR__ . "/../ZenterApiQueries/GetVersion.graphql"),
		];
		$data = self::getDataFromQuery($query);

		return idx($data, "version", "");
	}

	public static function IsPriviliged():bool
	{
		$query = [
			"query" => file_get_contents(__DIR__ . "/../ZenterApiQueries/IsPriviliged.graphql"),
		];
		$data = self::getDataFromQuery($query);

		if(!$data) return false;

		return idx($data, "isPriviliged", false);
	}

	public static function login(string $ApiToken, string $ApiPassphrase):string
	{
		$query = [
			"query" => file_get_contents(__DIR__ . "/../ZenterApiQueries/Login.graphql"),
			"variables" =>
			[
				'ApiToken' => $ApiToken,
				'ApiPassphrase' => $ApiPassphrase
			]
		];
		$data = self::getDataFromQuery($query);

		if(!$data) return "";

		return idx($data, "loginApiUser", "");
	}

	public static function createList(string $title)
	{
		$query = [
			"query" => file_get_contents(__DIR__ . "/../ZenterApiQueries/CreateList.graphql"),
			"variables" =>
			[
				'title' => $title
			]
		];
		$data = self::getDataFromQuery($query);

		if(!$data) return null;

		return idx($data, "CreateList");
	}

	public static function createJob(string $title)
	{
		$query = [
			"query" => file_get_contents(__DIR__ . "/../ZenterApiQueries/CreateEmailJob.graphql"),
			"variables" =>
			[
				'title' => $title
			]
		];
		$data = self::getDataFromQuery($query);

		if(!$data) return null;

		return idx($data, "CreateJob");
	}

	public static function createTemplate(string $title)
	{
		$query = [
			"query" => file_get_contents(__DIR__ . "/../ZenterApiQueries/CreateTemplate.graphql"),
			"variables" =>
			[
				'title' => $title,
				'body' => file_get_contents(__DIR__ . "/../ZenterData/Email.html"),
			]
		];
		$data = self::getDataFromQuery($query);

		if(!$data) return null;

		return idx($data, "CreateTemplate");
	}

	public static function sendJob(int $id)
	{
		$query = [
			"query" => file_get_contents(__DIR__ . "/../ZenterApiQueries/SendJob.graphql"),
			"variables" =>
			[
				'id' => $id
			]
		];

		$data = self::getDataFromQuery($query);

		if(!$data) return null;

		return idx($data, 'SendJob');
	}

	public static function AddRecipientsToList(int $listId, array $recipientIds)
	{
		$query = [
			"query" => file_get_contents(__DIR__ . "/../ZenterApiQueries/SendJob.graphql"),
			"variables" =>
			[
				'id' => $id
			]
		];

		$data = self::getDataFromQuery($query);

		if(!$data) return null;

		return idx($data, 'SendJob');
	}

	public static function initialize(string $endpoint)
	{
		self::$endpoint = $endpoint;
	}

	public static function getEndpoint():string
	{
		if(self::$endpoint === null)
		{
			throw new \Exception('Ivory service has not bee initialized');
		}
		return self::$endpoint;
	}

	public static function getDataFromQuery(array $query)
	{
		if(self::$endpoint === null)
		{
			throw new \Exception('Ivory service has not bee initialized');
		}

		$data = self::getGraphQlResponse($query);
		if(!$data)
		{
			return null;
		}

		if($errors = idx($data, 'errors'))
		{
			error_log(print_r($errors, true));
			throw new Exception('GraphQl Errors! (' . print_r($errors, true) . ')');
		}
		return $data['data'];
	}

	public static function getGraphQlResponse(array $query)
	{
		$query = json_encode($query);
		if($query === false)
		{
			$error = json_last_error_msg();
			throw new Exception('Json Encode Error: '.$error);
		}
		$dataString = self::callEndpointWithCurl(self::getEndpoint(),$query);

		$data = json_decode($dataString, true);
		if($data === false)
		{
			throw new Exception("Invalid data returned from server. (false)");
		}

		return $data;
	}

	public static function callEndpointWithCurl(string $endpoint, string $query, int $tries = 1)
	{
		if($tries > 10)
		{
			throw new Exception('Too many tries.');
		}

		if($tries === 1 && self::DEBUGGING)
		{
			print($query . PHP_EOL);
		}


		$handle = curl_init($endpoint);
		curl_setopt($handle, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($handle, CURLOPT_POSTFIELDS, $query);
		curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);

		//Limit to resolving only ipv4
		curl_setopt($handle, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4 );

		//Setting ssome dns settings to hopefully help resolve hosts
		curl_setopt($handle, CURLOPT_DNS_CACHE_TIMEOUT, 2 );

		curl_setopt($handle, CURLOPT_HTTPHEADER, array(
		    'Content-Type: application/json',
		    'Content-Length: ' . strlen($query))
		);

		$incommingData = curl_exec($handle);

		$curlError = curl_error($handle);
		$http_code = curl_getinfo($handle, CURLINFO_HTTP_CODE);

		curl_close($handle);


		if($curlError || $http_code !== 200 || $incommingData == "" || $incommingData === null || trim($incommingData) == '')
		{
			error_log("--------START IVORY CURL ERROR--------");
			error_log('Endpoint: ' . $endpoint);
			error_log('HttpCode: ' . $http_code);
			error_log('Query: ' . $query);
			error_log('CurlError: ' . $curlError);
			error_log("IncommingData:\n" . $incommingData);

			if($http_code === 500)
			{
				error_log(PHP_EOL . '     -------  500  -------' . PHP_EOL);
				error_log('Checking again!!! Failure #' . self::$failure++ . " Try: " . $tries);
				error_log("--------END IVORY CURL ERROR--------\n");
				return self::callEndpointWithCurl($endpoint, $query, $tries++);
			}


			if($http_code === 502)
			{
				error_log('-------  502 -------');
				error_log('Checking again!!! Failure #' . self::$failure++ . " Try: " . $tries);
				error_log("--------END IVORY CURL ERROR--------\n");

				return self::callEndpointWithCurl($endpoint, $query, $tries++);
			}

			switch ($curlError)
			{
				case 'Empty reply from server':
				case 'Recv failure: Connection reset by peer':
					error_log('Checking again!!! Failure #' . self::$failure++ . " Try: " . $tries);
					error_log("--------END IVORY CURL ERROR--------\n");
					return self::callEndpointWithCurl($endpoint, $query, $tries++);
					break;

				default:
					break;
			}
		}

		if(!is_string($incommingData))
		{
			throw new \Exception('Non string returned from curl');
		}

		//Donno why the fuck this is not catched earlier but ... it is
		if($incommingData == "")
		{
			error_log("--------START IVORY CURL ERROR--------");
			error_log('Endpoint: ' . $endpoint);
			error_log('HttpCode: ' . $http_code);
			error_log('Query: ' . $query);
			error_log('CurlError: ' . $curlError);
			error_log("IncommingData:\n" . $incommingData);
			error_log('Checking again!!! Failure #' . self::$failure++ . " Try: " . $tries);
			error_log("--------END IVORY CURL ERROR--------\n");
			return self::callEndpointWithCurl($endpoint, $query, $tries++);
		}
		if(self::DEBUGGING)
		{
			print($incommingData . PHP_EOL);
		}
  		return $incommingData;
	}
}

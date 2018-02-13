<?hh // strict

namespace AirlineServiceDemo\Lib;

use Exception;

type Data = shape();

type GraphQlError = shape(
	"message" => string,
	"locations" => shape(
		"line" => int,
		"column" => int,
		)
	);

type GraphQlData = shape(
	'data' => Data,
	'errors' => array<GraphQlError>
);

/**
 * Asynchronous communication service to the Ivory system
 * Remember to use \HH\Asio\join(<Awaitable>) to get the data
 */
class WebRequest
{
	const int MAX_CONNECTIONS = 80;


	const string ASSET_QUERY = 'id,title,sha1,size,content_type,ref_sha1,date_created,date_modified';
	const string CACHE_ADVERTISEMENT_QUERY = 'id,asset_id,comment';
	const string IMPRESSION_QUERY = 'id,advertisement_id,publication_id,page,subPage,placement,appearance_time,type,user_id,comment';
	const string PUBLICATION_QUERY = 'id,publish_date,asset_id,publisher_id,added_by_user_id,category_id,pages';

	const bool DEBUGGING = false;


	static int $failure = 0;
	static int $connections = 0;

	private static ?string $endpoint = null;

	public function __construct()
	{

	}

	public static function initialize(string $endpoint):void
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



	public static async function getDataFromQuery(array<string,mixed> $query):Awaitable<?Data>
	{
		$data = await self::getGraphQlResponse($query);
		if(!$data)
		{
			return null;
		}
		if($errors = Shapes::idx($data, 'errors'))
		{
			error_log(print_r($errors, true));
			throw new Exception('GraphQl Errors! (' . print_r($errors, true) . ')');
		}
		return $data['data'];
	}

	public static async function getGraphQlResponse(array<string,mixed> $query):Awaitable<?GraphQlData>
	{
		$query = json_encode($query);
		if($query === false)
		{
			$error = json_last_error_msg();
			throw new Exception('Json Encode Error: '.$error);
		}
		$dataString = await self::callEndpointWithCurl(self::getEndpoint(),$query);

		$data = json_decode($dataString, true);
		if($data === false)
		{
			throw new Exception("Invalid data returned from server. (false)");
		}

		return $data;
	}

	public static async function callEndpointWithCurl(string $endpoint, string $query, int $tries = 1):Awaitable<string>
	{
		if($tries > 10)
		{
			throw new Exception('Too many tries.');
		}

		if($tries === 1 && self::DEBUGGING)
		{
			print($query . PHP_EOL);
		}

		//Sleep while we have to many connections
		while(self::$connections > self::MAX_CONNECTIONS)
		{
			await \HH\Asio\usleep(3000);
		}

		self::$connections++;

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
		$incommingData = await \HH\Asio\curl_exec($handle);

		$curlError = curl_error($handle);
		$http_code = curl_getinfo($handle, CURLINFO_HTTP_CODE);

		curl_close($handle);

		self::$connections--;


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
				await \HH\Asio\usleep(3000);
				return await self::callEndpointWithCurl($endpoint, $query, $tries++);
			}


			if($http_code === 502)
			{
				error_log('-------  502 -------');
				error_log('Checking again!!! Failure #' . self::$failure++ . " Try: " . $tries);
				error_log("--------END IVORY CURL ERROR--------\n");
				await \HH\Asio\usleep(3000);
				return await self::callEndpointWithCurl($endpoint, $query, $tries++);
			}

			switch ($curlError)
			{
				case 'Empty reply from server':
				case 'Recv failure: Connection reset by peer':
					error_log('Checking again!!! Failure #' . self::$failure++ . " Try: " . $tries);
					error_log("--------END IVORY CURL ERROR--------\n");
					await \HH\Asio\usleep(3000);
					return await self::callEndpointWithCurl($endpoint, $query, $tries++);
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
			await \HH\Asio\usleep(3000);
			return await self::callEndpointWithCurl($endpoint, $query, $tries++);
		}
		if(self::DEBUGGING)
		{
			print($incommingData . PHP_EOL);
		}
  		return $incommingData;
	}
}

<?hh

require_once __DIR__ . '/../vendor/autoload.php';

use AirlineServiceDemo\Router;

$router = new Router($_SERVER, $_GET, $_POST, $_COOKIE);

echo $router->execute();
